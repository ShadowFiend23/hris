<?php

namespace App\Http\Middleware;

use App\Modules\Core\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    public function __construct(protected LicenseService $licenseService) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $moduleCode): Response
    {
        if (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            return redirect()->route('login');
        }

        if (! $request->user()->company_id) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'User not assigned to a company'], 403);
            }
            abort(403, 'User not assigned to a company');
        }

        if (! $this->licenseService->hasModuleAccess($request->user()->company_id, $moduleCode)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Your company does not have access to the {$moduleCode} module",
                ], 403);
            }
            abort(403, "Your company does not have access to the {$moduleCode} module");
        }

        return $next($request);
    }
}
