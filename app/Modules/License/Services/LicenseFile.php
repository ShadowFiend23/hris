<?php

namespace App\Modules\License\Services;

class LicenseFile
{
    private string $filePath;

    public function __construct(private readonly HardwareFingerprint $fingerprint)
    {
        $this->filePath = config('license.file_path', storage_path('license/license.lic'));
    }

    public function exists(): bool
    {
        return file_exists($this->filePath);
    }

    /**
     * Read and decode the stored license blob. Returns null if missing or malformed.
     *
     * The file stores the raw base64 blob returned by the license server.
     * Decoding: base64 → JSON string → array (includes signature field).
     *
     * @return array<string, mixed>|null
     */
    public function read(): ?array
    {
        if (! $this->exists()) {
            return null;
        }

        $blob = file_get_contents($this->filePath);
        if ($blob === false) {
            return null;
        }

        $decoded = base64_decode(trim($blob), strict: true);
        if ($decoded === false) {
            return null;
        }

        $data = json_decode($decoded, associative: true);

        return \is_array($data) ? $data : null;
    }

    /**
     * Write the raw base64 license blob from the server directly to disk.
     */
    public function write(string $licenseBlob): void
    {
        $dir = dirname($this->filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, recursive: true);
        }

        file_put_contents($this->filePath, $licenseBlob);
    }

    /**
     * Verify the RSA-SHA256 signature on a decoded license payload.
     *
     * The signing server signs json_encode($payload_without_signature) in insertion order
     * (no key sorting). We must reconstruct the same string to verify.
     *
     * @param  array<string, mixed>  $data
     */
    public function verifySignature(array $data): bool
    {
        if (empty($data['signature'])) {
            return false;
        }

        $signature = base64_decode($data['signature'], strict: true);
        if ($signature === false) {
            return false;
        }

        $publicKeyPem = config('license.public_key');
        $key = openssl_get_publickey($publicKeyPem);
        if ($key === false) {
            return false;
        }

        return openssl_verify($this->buildSignablePayload($data), $signature, $key, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * Check that the hardware_hash in the license matches the current machine.
     *
     * @param  array<string, mixed>  $data
     */
    public function hardwareMatches(array $data): bool
    {
        return isset($data['hardware_hash'])
            && hash_equals($data['hardware_hash'], $this->fingerprint->generate());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function isExpired(array $data): bool
    {
        if (empty($data['expires_at'])) {
            return true;
        }

        if ($data['expires_at'] === 'lifetime') {
            return false;
        }

        return now()->isAfter($data['expires_at']);
    }

    /**
     * Full validity check: file exists, signature valid, hardware matches, not expired.
     */
    public function isValid(): bool
    {
        $data = $this->read();

        if ($data === null) {
            return false;
        }

        return $this->verifySignature($data)
            && $this->hardwareMatches($data)
            && ! $this->isExpired($data);
    }

    /**
     * Build the string that was signed by the license server.
     *
     * The server signs json_encode($payload) BEFORE adding the signature field,
     * preserving original key insertion order. We reconstruct that by removing
     * 'signature' without reordering any other keys.
     *
     * @param  array<string, mixed>  $data
     */
    public function buildSignablePayload(array $data): string
    {
        unset($data['signature']);

        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
