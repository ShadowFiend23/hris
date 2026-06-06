<?php

namespace App\Http\Middleware;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Services\LicenseService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $licenseService = app(LicenseService::class);
        $moduleData = [];
        $licenseInfo = [];

        $loansEnabled = true;
        $leaveEnabled = true;
        $otEnabled = true;

        if ($request->user() && $request->user()->company_id) {
            $companyId = $request->user()->company_id;
            $activeModuleCodes = $licenseService->getActiveModuleCodesForCompany($companyId);
            $company = Company::find($companyId);
            $loansEnabled = (bool) ($company?->loans_enabled ?? true);
            $leaveEnabled = (bool) ($company?->leave_enabled ?? true);
            $otEnabled = (bool) ($company?->ot_enabled ?? true);

            // Get all active modules from database
            $allModules = \App\Modules\Core\Models\Module::where('is_active', true)
                ->orderBy('order')
                ->get();

            // Transform to include enabled status
            $moduleData = $allModules->map(fn ($module) => [
                'code' => $module->code,
                'name' => $module->name,
                'icon' => $module->icon,
                'enabled' => in_array($module->code, $activeModuleCodes),
            ])->toArray();

            // Get the latest company license (regardless of expiry status)
            $license = $licenseService->getCompanyActiveLicense($companyId);
            if ($license) {
                $daysRemaining = $licenseService->daysUntilExpiration($license);
                // Always compute isExpiringSoon even for expired licenses (negative/zero days)
                $licenseInfo = [
                    'status' => $license->status,
                    'validUntil' => $license->valid_until,
                    'daysRemaining' => $daysRemaining,
                    'isExpiringSoon' => $daysRemaining <= 30,
                ];
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user()
                    ? $request->user()->load('roles.permissions')->getPermissionSlugs()
                    : [],
                'isAdmin' => $request->user() ? $request->user()->hasRole('admin') : false,
                'isManager' => $request->user() ? $request->user()->hasRole('manager') : false,
                'employeeId' => $request->user()
                    ? Employee::where('user_id', $request->user()->id)->value('id')
                    : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'modules' => $moduleData,
            'licenseInfo' => $licenseInfo,
            'loansEnabled' => $loansEnabled,
            'leaveEnabled' => $leaveEnabled,
            'otEnabled' => $otEnabled,
        ];
    }
}
