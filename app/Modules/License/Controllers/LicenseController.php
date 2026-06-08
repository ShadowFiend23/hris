<?php

namespace App\Modules\License\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\License;
use App\Modules\Core\Models\Module;
use App\Modules\License\Services\HardwareFingerprint;
use App\Modules\License\Services\LicenseFile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LicenseController extends Controller
{
    public function __construct(
        private readonly LicenseFile $licenseFile,
        private readonly HardwareFingerprint $fingerprint,
    ) {}

    /**
     * Display the hardware license status and details.
     */
    public function index(): \Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->company_id) {
            return redirect()->route('home');
        }

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

        return Inertia::render('Licenses/Licenses', [
            'licenseStatus' => $status,
            'companyName' => $licenseData['company'] ?? null,
            'licenseKey' => $licenseData['license_key'] ?? null,
            'issuedAt' => $licenseData['issued_at'] ?? null,
            'expiresAt' => $licenseData['expires_at'] ?? null,
            'hardwareHash' => $this->fingerprint->generate(),
            'hostname' => $this->fingerprint->getHostname(),
        ]);
    }

    /**
     * Display details of a specific license
     */
    public function show(License $license)
    {
        $user = auth()->user();
        if (! $user || $license->company_id !== $user->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $license->load(['modules', 'company']);

        return Inertia::render('Licenses/LicenseDetail', [
            'license' => $license,
            'availableModules' => Module::where('is_active', true)->get(),
        ]);
    }

    /**
     * Create a new license
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (! $user || ! $user->company_id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check authorization - only admins can create licenses
        if (! $user->roles->contains('slug', 'admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'license_key' => 'required|string|unique:licenses',
            'type' => 'required|in:standard,professional,enterprise',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'user_limit' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive,expired',
            'notes' => 'nullable|string',
        ]);

        $license = License::create([
            ...$validated,
            'company_id' => $user->company_id,
        ]);

        return response()->json([
            'message' => 'License created successfully',
            'license' => $license,
        ], 201);
    }

    /**
     * Update a license
     */
    public function update(Request $request, License $license)
    {
        $user = auth()->user();
        if (! $user || $license->company_id !== $user->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check authorization
        if (! $user->roles->contains('slug', 'admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:standard,professional,enterprise',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'user_limit' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive,expired',
            'notes' => 'nullable|string',
        ]);

        $license->update($validated);

        return response()->json([
            'message' => 'License updated successfully',
            'license' => $license,
        ]);
    }

    /**
     * Attach a module to a license
     */
    public function attachModule(Request $request, License $license)
    {
        $user = auth()->user();
        if (! $user || $license->company_id !== $user->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check authorization
        if (! $user->roles->contains('slug', 'admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'module_id' => 'required|exists:modules,id',
        ]);

        $license->modules()->attach($validated['module_id']);

        return response()->json([
            'message' => 'Module attached successfully',
        ]);
    }

    /**
     * Detach a module from a license
     */
    public function detachModule(Request $request, License $license, Module $module)
    {
        $user = auth()->user();
        if (! $user || $license->company_id !== $user->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check authorization
        if (! $user->roles->contains('slug', 'admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $license->modules()->detach($module->id);

        return response()->json([
            'message' => 'Module detached successfully',
        ]);
    }
}
