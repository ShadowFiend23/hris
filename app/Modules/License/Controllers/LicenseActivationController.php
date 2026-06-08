<?php

namespace App\Modules\License\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\License\Services\HardwareFingerprint;
use App\Modules\License\Services\LicenseFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class LicenseActivationController extends Controller
{
    public function __construct(
        private readonly HardwareFingerprint $fingerprint,
        private readonly LicenseFile $licenseFile,
    ) {}

    public function show(): Response
    {
        $licenseData = $this->licenseFile->read();

        $status = null;
        if ($licenseData !== null) {
            if (! $this->licenseFile->verifySignature($licenseData)) {
                $status = 'tampered';
            } elseif (! $this->licenseFile->hardwareMatches($licenseData)) {
                $status = 'wrong_machine';
            } elseif ($this->licenseFile->isExpired($licenseData)) {
                $status = 'expired';
            } else {
                $status = 'active';
            }
        }

        return Inertia::render('Licenses/Activate', [
            'hardwareHash' => $this->fingerprint->generate(),
            'hostname' => $this->fingerprint->getHostname(),
            'licenseStatus' => $status,
            'expiresAt' => $licenseData['expires_at'] ?? null,
            'companyName' => $licenseData['company'] ?? null,
        ]);
    }

    public function activate(Request $request): RedirectResponse
    {
        $request->validate([
            'license_key' => ['required', 'string', 'min:8'],
        ]);

        $hardwareHash = $this->fingerprint->generate();
        $serverUrl = rtrim((string) config('license.server_url'), '/');
        $endpoint = $serverUrl.config('license.activate_endpoint', '/api/activate');

        try {
            $response = Http::withoutVerifying()->timeout(15)->post($endpoint, [
                'license_key' => $request->string('license_key')->toString(),
                'hardware_hash' => $hardwareHash,
                'hostname' => $this->fingerprint->getHostname(),
            ]);
        } catch (\Exception) {
            return back()->withErrors([
                'license_key' => 'Could not reach the license server. Ensure the server is online and LICENSE_SERVER_URL is correct.',
            ]);
        }

        if (! $response->successful()) {
            $message = $response->json('message', 'Invalid license key or server error.');

            return back()->withErrors(['license_key' => $message]);
        }

        // The server returns the license as a base64-encoded signed blob
        $licenseBlob = $response->json('license');

        if (! \is_string($licenseBlob)) {
            return back()->withErrors(['license_key' => 'Invalid response from license server.']);
        }

        // Decode the blob to verify it before saving
        $decoded = base64_decode($licenseBlob, strict: true);
        if ($decoded === false) {
            return back()->withErrors(['license_key' => 'Malformed license data received from server.']);
        }

        $licenseData = json_decode($decoded, true);
        if (! \is_array($licenseData)) {
            return back()->withErrors(['license_key' => 'Malformed license data received from server.']);
        }

        if (! $this->licenseFile->verifySignature($licenseData)) {
            return back()->withErrors(['license_key' => 'License signature verification failed. The response may have been tampered with.']);
        }

        if (($licenseData['hardware_hash'] ?? '') !== $hardwareHash) {
            return back()->withErrors(['license_key' => 'This license key is bound to a different server.']);
        }

        $this->licenseFile->write($licenseBlob);

        return redirect()->route('login')->with('status', 'License activated successfully. Please log in.');
    }
}
