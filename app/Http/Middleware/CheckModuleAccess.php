<?php

namespace App\Http\Middleware;

use App\Modules\Core\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    public function __construct(protected LicenseService $licenseService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $moduleCode): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$request->user()->company_id) {
            return response()->json(['message' => 'User not assigned to a company'], 403);
        }

        if (!$this->licenseService->hasModuleAccess($request->user()->company_id, $moduleCode)) {
            return response()->json([
                'message' => "Your company does not have access to the {$moduleCode} module",
            ], 403);
        }

        return $next($request);
    }
}
