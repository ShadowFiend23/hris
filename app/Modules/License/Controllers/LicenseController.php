<?php

namespace App\Modules\License\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\License;
use App\Modules\Core\Models\Module;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LicenseController extends Controller
{
    /**
     * Display all licenses for the authenticated user's company
     */
    public function index()
    {
        $user = auth()->user();
        if (! $user || ! $user->company_id) {
            return redirect()->route('home');
        }

        $licenses = License::where('company_id', $user->company_id)
            ->with('modules')
            ->paginate(10);

        return Inertia::render('Licenses/Licenses', [
            'licenses' => $licenses,
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
