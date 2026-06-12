<?php

namespace App\Modules\License\Services;

class HardwareFingerprint
{
    /**
     * Generate a stable SHA-256 hardware fingerprint for this server.
     * Combines machine UUID, primary MAC address, and disk serial number.
     * Sorted before hashing so output is stable regardless of collection order.
     */
    public function generate(): string
    {
        $components = array_filter([
            $this->getMachineId(),
            $this->getPrimaryMac(),
            $this->getDiskSerial(),
        ]);

        sort($components);

        return hash('sha256', implode('|', $components));
    }

    public function getHostname(): string
    {
        return gethostname() ?: 'unknown';
    }

    /** SHA-256 of the machine UUID alone — used for tiered license validation. */
    public function getMachineIdHash(): string
    {
        $id = $this->getMachineId();

        return $id !== '' ? hash('sha256', $id) : '';
    }

    /** SHA-256 of the primary MAC address alone — used for tiered license validation. */
    public function getMacHash(): string
    {
        $mac = $this->getPrimaryMac();

        return $mac !== '' ? hash('sha256', $mac) : '';
    }

    /** SHA-256 of the disk serial number alone — used for tiered license validation. */
    public function getDiskSerialHash(): string
    {
        $serial = $this->getDiskSerial();

        return $serial !== '' ? hash('sha256', $serial) : '';
    }

    private function getMachineId(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Try WMIC first (deprecated in Win 10 21H1, removed in Win 11 24H2)
            $output = shell_exec('wmic csproduct get uuid 2>nul');
            if ($output && preg_match('/([A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12})/i', $output, $m)) {
                return strtoupper($m[1]);
            }

            // Fall back to PowerShell / CIM
            $output = shell_exec('powershell -NoProfile -Command "(Get-CimInstance Win32_ComputerSystemProduct).UUID" 2>nul');
            if ($output && preg_match('/([A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12})/i', $output, $m)) {
                return strtoupper($m[1]);
            }

            return '';
        }

        foreach (['/etc/machine-id', '/var/lib/dbus/machine-id'] as $path) {
            if (is_readable($path)) {
                return trim((string) file_get_contents($path));
            }
        }

        return '';
    }

    private function getPrimaryMac(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Try WMIC first
            $output = shell_exec('wmic nic where "PhysicalAdapter=True" get MACAddress 2>nul');
            if ($output) {
                preg_match_all('/([0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2})/i', $output, $m);
                $macs = $this->filterVirtualMacs(array_map('strtoupper', $m[1] ?? []));
                if (! empty($macs)) {
                    return $macs[0];
                }
            }

            // Fall back to PowerShell / CIM
            $output = shell_exec('powershell -NoProfile -Command "Get-CimInstance Win32_NetworkAdapter | Where-Object { $_.PhysicalAdapter } | Select-Object -ExpandProperty MACAddress" 2>nul');
            if ($output) {
                preg_match_all('/([0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2})/i', $output, $m);
                $macs = $this->filterVirtualMacs(array_map('strtoupper', $m[1] ?? []));
                if (! empty($macs)) {
                    return $macs[0];
                }
            }

            return '';
        }

        $macs = [];
        $netDir = '/sys/class/net/';

        if (is_dir($netDir)) {
            foreach (scandir($netDir) as $iface) {
                if ($iface === '.' || $iface === '..' || $iface === 'lo') {
                    continue;
                }

                $addrFile = $netDir.$iface.'/address';
                if (is_readable($addrFile)) {
                    $mac = strtoupper(trim((string) file_get_contents($addrFile)));
                    if ($mac && $mac !== '00:00:00:00:00:00') {
                        $macs[] = $mac;
                    }
                }
            }
        }

        $macs = $this->filterVirtualMacs($macs);
        sort($macs);

        return $macs[0] ?? '';
    }

    /**
     * Remove MACs whose OUI belongs to known virtual/software adapters.
     * WMI marks Hyper-V and other virtual NICs as PhysicalAdapter=True, which is misleading.
     *
     * @param  string[]  $macs  Uppercase MACs in XX:XX:XX:XX:XX:XX format
     * @return string[]
     */
    private function filterVirtualMacs(array $macs): array
    {
        // OUI prefixes (first 8 chars of XX:XX:XX format) for common virtual adapters
        $virtualOuis = [
            '00:15:5D', // Microsoft Hyper-V
            '00:50:56', // VMware
            '00:0C:29', // VMware (workstation)
            '00:05:69', // VMware (old)
            '08:00:27', // VirtualBox
            '52:54:00', // QEMU / KVM (libvirt default)
            '00:16:3E', // Xen
            '00:1C:42', // Parallels
            '00:03:FF', // Microsoft Virtual PC
        ];

        $real = array_filter($macs, function (string $mac) use ($virtualOuis): bool {
            $oui = substr($mac, 0, 8);

            return ! in_array($oui, $virtualOuis, strict: true);
        });

        sort($real);

        // Fall back to all MACs (sorted) if every adapter is virtual
        if (empty($real)) {
            sort($macs);

            return $macs;
        }

        return array_values($real);
    }

    private function getDiskSerial(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Try WMIC first
            $output = shell_exec('wmic diskdrive get serialnumber /format:list 2>nul');
            if ($output) {
                $serials = [];
                foreach (explode("\n", $output) as $line) {
                    if (preg_match('/^SerialNumber=(.+)$/i', trim($line), $m)) {
                        $serial = trim($m[1]);
                        if ($serial !== '') {
                            $serials[] = strtoupper($serial);
                        }
                    }
                }
                sort($serials);

                if (! empty($serials)) {
                    return $serials[0];
                }
            }

            // Fall back to PowerShell / CIM
            $output = shell_exec('powershell -NoProfile -Command "Get-CimInstance Win32_DiskDrive | Select-Object -ExpandProperty SerialNumber" 2>nul');
            if ($output) {
                $serials = [];
                foreach (explode("\n", $output) as $line) {
                    $serial = trim($line);
                    if ($serial !== '') {
                        $serials[] = strtoupper($serial);
                    }
                }
                sort($serials);

                if (! empty($serials)) {
                    return $serials[0];
                }
            }

            return '';
        }

        $serials = [];
        foreach (glob('/sys/block/*/device/serial') ?: [] as $path) {
            if (is_readable($path)) {
                $serial = trim((string) file_get_contents($path));
                if ($serial !== '') {
                    $serials[] = strtoupper($serial);
                }
            }
        }
        sort($serials);

        return $serials[0] ?? '';
    }
}
