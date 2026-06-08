<?php

namespace Tests\Feature\License;

use App\Modules\License\Services\HardwareFingerprint;
use App\Modules\License\Services\LicenseFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HardwareLicenseTest extends TestCase
{
    use RefreshDatabase;

    // RSA-2048 test key pair — used only in tests, never in production
    private const TEST_PRIVATE_KEY = <<<'PEM'
-----BEGIN RSA PRIVATE KEY-----
MIIEpQIBAAKCAQEAydsTNPP8SfTNrbwwPeD3XsPXFdCfrMqZOsJ4rUHOsyX42DIB
/F2k3hgfrWhPV3l7qz880B7dDncMonsD2Qd4sYYHk56E5p4qkRH2WCChPMgGq0wT
nMEI3jqdpgtviJmiyubPcGJCcrjMjnD60m5AgUTwmQcHVZlU1yol3xx0s7cRojPh
Uqk71iV8vMvU2u3H1U0TXrySR1dHNfhaGXBK4EzrHn/bNablewvZoLEjHQFAGD1t
6yGBEEUK1KalYBIwnuGjNTHkRFA1PV+3sSd8732FvJdiDCpBimxUmteMWaM4j6xa
TBCBJWI3CYp/ZX9qrACxAUVKAYXGIibNz77ycwIDAQABAoIBAQCBYGXmGb+JU1uE
0EqHTqqeVZ2FAOtm34/opn6cZcJq9pqRTzQQoaCrLQEYrvOzmEyYjPaYWj/LJqi1
d20W7IACIGq4xfBes9+Kjd27zGYrw/TqU996KAtcDGvA4jGBdx9tNxSb7o6yYxnQ
7j8FIt87O4mOO4AK8DT9l6kgYGe4SyfFFJDCVm5ybxZg2xoVWFfsdT/zGE6bFXPg
zZtmNz2/3+sou9Nt3fLOYW2vBOc8fKrN4r0I/HMGE4nptYgxU2glnAvvwTxP4LvK
sbYuKFKrltcHzbD/+Zr3xYohoD7qgfPSm19E2hRAiuuG7Q8Yxd/ahLaLYCPe8C1E
5K/2++iRAoGBAP5p0m/0KxjYG1cM5UpsuVZvpTs2fBUQJqaFqmiGgiyfu71HAuE8
N3sYVJzYl8al92vC/DJax4ynlV1StazKyEpHUtptIWyKUk/eek2IXZkwazK3oIFH
byZXa72O/kOkO1Bh9DBOjX8xquoAX4eAg1gkqWZluZ2NdUNXoYqLvBlbAoGBAMsd
V+TLLlxffNW7ybt7hM22Pgj44LD5UW9HZxtN3PyY/QJSrTnJKIUlBQPkieBj+bB7
UuzvJHmuNxFC1HSVPgsGWOGQILo5GtEDLUBgS1zNXb8HTN3emTDNTmGHhC1MCFY9
+OaT18zJKb/VPsBAqiJJZJVLmYZkhfBPHn95sT7JAoGBAPN6UurRwm0EZSXqMYYI
h1cxgfApNJbz3gl/4pYmJG6QcBu02gy0lGQ55MA2iQqTyzZWZQXL2y3BNDEb9sJn
T0fG+oQP7Ozzc9L8GBAbcXgp3m2rTvBqbPVEtNe1AaG6iwlitU+F3eTco7VOph4H
36t7aqOUtw8RUiggtXjiQiIBAoGAbyg94852LsNrNCUzP9FXWqUzygE6JmArevTe
roisOekX/d91Qbye2k7qMbku44iTK0wyk8xEb/sx09Z5ItxSOgJdPgR8hK2fyWct
qp+IXt9dQKAkJwAdYiHZlXJumcOUhG4h+z5JyZ8FpCB12Q0Kjv0PeM0I1g3JZzxF
eooEwFECgYEAjOAQkuddozEmowV8Xa3BOCYMHKioeDpzYa/b8tr5CFtonNdF2mGN
oMU0guz3IESNUbuBEDe0GeS1XmeejbF3oHBLWf/cs1C3vSOBzNPav7eY/1LRWPtJ
bbsG9s7Os1Ib8dhsKH7tUQ2GumVmLox2mnDSYD6gZZZZwobLtC6WqZU=
-----END RSA PRIVATE KEY-----
PEM;

    private const TEST_PUBLIC_KEY = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAydsTNPP8SfTNrbwwPeD3
XsPXFdCfrMqZOsJ4rUHOsyX42DIB/F2k3hgfrWhPV3l7qz880B7dDncMonsD2Qd4
sYYHk56E5p4qkRH2WCChPMgGq0wTnMEI3jqdpgtviJmiyubPcGJCcrjMjnD60m5A
gUTwmQcHVZlU1yol3xx0s7cRojPhUqk71iV8vMvU2u3H1U0TXrySR1dHNfhaGXBK
4EzrHn/bNablewvZoLEjHQFAGD1t6yGBEEUK1KalYBIwnuGjNTHkRFA1PV+3sSd8
732FvJdiDCpBimxUmteMWaM4j6xaTBCBJWI3CYp/ZX9qrACxAUVKAYXGIibNz77y
cwIDAQAB
-----END PUBLIC KEY-----
PEM;

    private string $tempLicenseFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->tempLicenseFile = sys_get_temp_dir().'/test_license_'.uniqid().'.lic';
        Config::set('license.file_path', $this->tempLicenseFile);
        Config::set('license.public_key', self::TEST_PUBLIC_KEY);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempLicenseFile)) {
            unlink($this->tempLicenseFile);
        }
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // HardwareFingerprint
    // -------------------------------------------------------------------------

    public function test_hardware_fingerprint_returns_64_char_hex_string(): void
    {
        $fp = app(HardwareFingerprint::class);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $fp->generate());
    }

    public function test_hardware_fingerprint_is_stable_across_calls(): void
    {
        $fp = app(HardwareFingerprint::class);

        $this->assertSame($fp->generate(), $fp->generate());
    }

    // -------------------------------------------------------------------------
    // LicenseFile — read / write
    // -------------------------------------------------------------------------

    public function test_license_file_does_not_exist_initially(): void
    {
        $licenseFile = app(LicenseFile::class);

        $this->assertFalse($licenseFile->exists());
        $this->assertNull($licenseFile->read());
    }

    public function test_write_and_read_round_trip(): void
    {
        $licenseFile = app(LicenseFile::class);

        // write() accepts the raw base64 blob from the server
        $data = ['license_key' => 'TEST-0001', 'hardware_hash' => 'abc', 'signature' => 'xyz'];
        $blob = base64_encode(json_encode($data));

        $licenseFile->write($blob);

        $this->assertTrue($licenseFile->exists());
        $this->assertEquals($data, $licenseFile->read());
    }

    // -------------------------------------------------------------------------
    // LicenseFile — signature verification
    // -------------------------------------------------------------------------

    public function test_valid_signature_passes_verification(): void
    {
        $licenseFile = app(LicenseFile::class);
        $payload = $this->buildSignedPayload(['license_key' => 'KEY-001', 'hardware_hash' => 'hash1', 'expires_at' => '2099-01-01T00:00:00Z']);

        $this->assertTrue($licenseFile->verifySignature($payload));
    }

    public function test_tampered_payload_fails_verification(): void
    {
        $licenseFile = app(LicenseFile::class);
        $payload = $this->buildSignedPayload(['license_key' => 'KEY-001', 'hardware_hash' => 'hash1', 'expires_at' => '2099-01-01T00:00:00Z']);

        $payload['license_key'] = 'KEY-TAMPERED';

        $this->assertFalse($licenseFile->verifySignature($payload));
    }

    public function test_missing_signature_field_fails_verification(): void
    {
        $licenseFile = app(LicenseFile::class);

        $this->assertFalse($licenseFile->verifySignature(['license_key' => 'KEY-001']));
    }

    // -------------------------------------------------------------------------
    // LicenseFile — hardware check
    // -------------------------------------------------------------------------

    public function test_hardware_matches_when_hash_equals_current_machine(): void
    {
        $fp = app(HardwareFingerprint::class);
        $licenseFile = app(LicenseFile::class);

        $this->assertTrue($licenseFile->hardwareMatches(['hardware_hash' => $fp->generate()]));
    }

    public function test_hardware_does_not_match_different_hash(): void
    {
        $licenseFile = app(LicenseFile::class);

        $this->assertFalse($licenseFile->hardwareMatches(['hardware_hash' => 'wrong-hash-value']));
    }

    // -------------------------------------------------------------------------
    // LicenseFile — expiry
    // -------------------------------------------------------------------------

    public function test_future_expiry_is_not_expired(): void
    {
        $licenseFile = app(LicenseFile::class);

        $this->assertFalse($licenseFile->isExpired(['expires_at' => '2099-12-31T23:59:59Z']));
    }

    public function test_past_expiry_is_expired(): void
    {
        $licenseFile = app(LicenseFile::class);

        $this->assertTrue($licenseFile->isExpired(['expires_at' => '2000-01-01T00:00:00Z']));
    }

    public function test_lifetime_string_is_not_expired(): void
    {
        $licenseFile = app(LicenseFile::class);

        $this->assertFalse($licenseFile->isExpired(['expires_at' => 'lifetime']));
    }

    public function test_missing_or_null_expiry_is_treated_as_expired(): void
    {
        $licenseFile = app(LicenseFile::class);

        $this->assertTrue($licenseFile->isExpired([]));
        $this->assertTrue($licenseFile->isExpired(['expires_at' => null]));
    }

    // -------------------------------------------------------------------------
    // LicenseFile — isValid (combined)
    // -------------------------------------------------------------------------

    public function test_valid_license_file_returns_is_valid_true(): void
    {
        $fp = app(HardwareFingerprint::class);
        $licenseFile = app(LicenseFile::class);

        $blob = $this->buildSignedBlob([
            'license_key' => 'KEY-VALID',
            'hardware_hash' => $fp->generate(),
            'company' => 'Test Corp',
            'issued_at' => now()->toISOString(),
            'expires_at' => '2099-12-31T23:59:59Z',
        ]);

        $licenseFile->write($blob);

        $this->assertTrue($licenseFile->isValid());
    }

    public function test_expired_license_returns_is_valid_false(): void
    {
        $fp = app(HardwareFingerprint::class);
        $licenseFile = app(LicenseFile::class);

        $blob = $this->buildSignedBlob([
            'license_key' => 'KEY-EXPIRED',
            'hardware_hash' => $fp->generate(),
            'company' => 'Test Corp',
            'issued_at' => now()->toISOString(),
            'expires_at' => '2000-01-01T00:00:00Z',
        ]);

        $licenseFile->write($blob);

        $this->assertFalse($licenseFile->isValid());
    }

    public function test_wrong_machine_hash_returns_is_valid_false(): void
    {
        $licenseFile = app(LicenseFile::class);

        $blob = $this->buildSignedBlob([
            'license_key' => 'KEY-WRONG-HW',
            'hardware_hash' => 'not-this-machine',
            'company' => 'Test Corp',
            'issued_at' => now()->toISOString(),
            'expires_at' => '2099-12-31T23:59:59Z',
        ]);

        $licenseFile->write($blob);

        $this->assertFalse($licenseFile->isValid());
    }

    // -------------------------------------------------------------------------
    // Activation page
    // -------------------------------------------------------------------------

    public function test_activation_page_renders_when_no_license_exists(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyHardwareLicense::class)
            ->get('/license/activate')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Licenses/Activate')
                ->has('hardwareHash')
                ->has('hostname')
                ->where('licenseStatus', null)
            );
    }

    // -------------------------------------------------------------------------
    // Activation POST — error paths
    // -------------------------------------------------------------------------

    public function test_activate_requires_license_key(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyHardwareLicense::class)
            ->post('/license/activate', [])
            ->assertSessionHasErrors('license_key');
    }

    public function test_activate_shows_error_when_server_unreachable(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'));

        $this->withoutMiddleware(\App\Http\Middleware\VerifyHardwareLicense::class)
            ->post('/license/activate', ['license_key' => 'TEST-KEY-1234'])
            ->assertSessionHasErrors('license_key');
    }

    public function test_activate_shows_error_on_server_rejection(): void
    {
        Http::fake(['*' => Http::response(['success' => false, 'message' => 'Unknown license key.'], 422)]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyHardwareLicense::class)
            ->post('/license/activate', ['license_key' => 'INVALID-KEY'])
            ->assertSessionHasErrors('license_key');
    }

    public function test_activate_rejects_tampered_server_response(): void
    {
        // Build a blob then tamper with the inner payload
        $fp = app(HardwareFingerprint::class);
        $blob = $this->buildSignedBlob([
            'license_key' => 'KEY',
            'hardware_hash' => $fp->generate(),
            'company' => 'Test',
            'issued_at' => now()->toISOString(),
            'expires_at' => '2099-01-01T00:00:00Z',
        ]);

        // Decode, tamper, re-encode without re-signing
        $payload = json_decode(base64_decode($blob), true);
        $payload['license_key'] = 'TAMPERED';
        $tamperedBlob = base64_encode(json_encode($payload));

        Http::fake(['*' => Http::response(['success' => true, 'license' => $tamperedBlob], 200)]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyHardwareLicense::class)
            ->post('/license/activate', ['license_key' => 'KEY'])
            ->assertSessionHasErrors('license_key');
    }

    // -------------------------------------------------------------------------
    // Activation POST — happy path
    // -------------------------------------------------------------------------

    public function test_activate_saves_license_and_redirects_to_login(): void
    {
        $fp = app(HardwareFingerprint::class);

        $blob = $this->buildSignedBlob([
            'license_key' => 'VALID-KEY-001',
            'hardware_hash' => $fp->generate(),
            'company' => 'Test Corp',
            'issued_at' => now()->toISOString(),
            'expires_at' => '2099-12-31T23:59:59Z',
        ]);

        Http::fake(['*' => Http::response(['success' => true, 'license' => $blob], 200)]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyHardwareLicense::class)
            ->post('/license/activate', ['license_key' => 'VALID-KEY-001'])
            ->assertRedirect(route('login'));

        $this->assertTrue(app(LicenseFile::class)->isValid());
    }

    // -------------------------------------------------------------------------
    // Middleware redirect
    // -------------------------------------------------------------------------

    public function test_middleware_redirects_to_activate_when_no_license(): void
    {
        Config::set('license.enforce_in_tests', true);

        $this->app->instance(LicenseFile::class, new class(app(HardwareFingerprint::class)) extends LicenseFile
        {
            public function isValid(): bool
            {
                return false;
            }
        });

        $this->get('/')->assertRedirect(route('license.activate'));
    }

    public function test_middleware_allows_access_to_activate_route(): void
    {
        Config::set('license.enforce_in_tests', true);

        $this->app->instance(LicenseFile::class, new class(app(HardwareFingerprint::class)) extends LicenseFile
        {
            public function isValid(): bool
            {
                return false;
            }
        });

        $this->get('/license/activate')->assertOk();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a signed base64 blob matching the exact format the license server produces.
     * Signs $basePayload in insertion order (no key sorting), then appends the signature.
     *
     * @param  array<string, mixed>  $basePayload
     */
    private function buildSignedBlob(array $basePayload): string
    {
        $payloadJson = json_encode($basePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $privateKey = openssl_get_privatekey(self::TEST_PRIVATE_KEY);
        openssl_sign($payloadJson, $rawSig, $privateKey, OPENSSL_ALGO_SHA256);
        $basePayload['signature'] = base64_encode($rawSig);

        return base64_encode(json_encode($basePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Build a signed payload array (decoded from the blob) for use in verifySignature() tests.
     *
     * @param  array<string, mixed>  $basePayload
     * @return array<string, mixed>
     */
    private function buildSignedPayload(array $basePayload): array
    {
        $blob = $this->buildSignedBlob($basePayload);

        return json_decode(base64_decode($blob), true);
    }
}
