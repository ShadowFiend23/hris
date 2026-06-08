<?php

namespace App\Modules\License\Services;

class HardwareFingerprint
{
    /**
     * Generate a stable SHA-256 hardware fingerprint for this server.
     * Combines machine UUID, primary MAC address, and hostname.
     * Sorted before hashing so output is stable regardless of collection order.
     */
    public function generate(): string
    {
        $components = array_filter([
            $this->getMachineId(),
            $this->getPrimaryMac(),
            gethostname() ?: '',
        ]);

        sort($components);

        return hash('sha256', implode('|', $components));
    }

    public function getHostname(): string
    {
        return gethostname() ?: 'unknown';
    }

    private function getMachineId(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('wmic csproduct get uuid 2>nul');
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
            $output = shell_exec('wmic nic where "PhysicalAdapter=True" get MACAddress 2>nul');
            if ($output) {
                preg_match_all('/([0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2})/i', $output, $m);
                if (! empty($m[1])) {
                    $macs = array_map('strtoupper', $m[1]);
                    sort($macs);

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

        sort($macs);

        return $macs[0] ?? '';
    }
}
