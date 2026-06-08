<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class IdentitySettingsController extends Controller
{
    private function logoUrl(?string $path): ?string
    {
        return $path ? asset('storage/'.$path) : null;
    }

    public function index(Request $request): Response
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $company = Company::findOrFail($request->user()->company_id);

        return Inertia::render('HRSettings/IdentitySettings', [
            'company' => [
                'name' => $company->name,
                'logoLogin' => $this->logoUrl($company->logo_login),
                'logoNav' => $this->logoUrl($company->logo_nav),
                'favicon' => $this->logoUrl($company->favicon),
            ],
        ]);
    }

    public function updateName(Request $request): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $company = Company::findOrFail($request->user()->company_id);
        $company->update(['name' => $request->name]);

        return redirect()->route('app-settings.identity-settings.index')
            ->with('success', 'Company name updated.');
    }

    public function uploadLogo(Request $request, string $type): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $column = match ($type) {
            'login-logo' => 'logo_login',
            'nav-logo' => 'logo_nav',
            'favicon' => 'favicon',
            default => abort(404),
        };

        $mimes = $type === 'favicon' ? 'ico,png,svg,gif,jpg,jpeg' : 'jpg,jpeg,png,svg,gif,webp';

        $request->validate([
            'file' => ['required', 'file', "mimes:{$mimes}", 'max:2048'],
        ]);

        $company = Company::findOrFail($request->user()->company_id);

        if ($company->$column) {
            Storage::disk('public')->delete($company->$column);
        }

        $path = $request->file('file')->store("branding/{$company->id}", 'public');
        $company->update([$column => $path]);

        return redirect()->route('app-settings.identity-settings.index')
            ->with('success', 'Logo updated successfully.');
    }

    public function removeLogo(Request $request, string $type): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $column = match ($type) {
            'login-logo' => 'logo_login',
            'nav-logo' => 'logo_nav',
            'favicon' => 'favicon',
            default => abort(404),
        };

        $company = Company::findOrFail($request->user()->company_id);

        if ($company->$column) {
            Storage::disk('public')->delete($company->$column);
            $company->update([$column => null]);
        }

        return redirect()->route('app-settings.identity-settings.index')
            ->with('success', 'Logo removed.');
    }
}
