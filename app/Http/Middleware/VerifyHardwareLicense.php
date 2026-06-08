<?php

namespace App\Http\Middleware;

use App\Modules\License\Services\LicenseFile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyHardwareLicense
{
    public function __construct(private readonly LicenseFile $licenseFile) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing') && ! config('license.enforce_in_tests', false)) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        if (! $this->licenseFile->isValid()) {
            return redirect()->route('license.activate');
        }

        return $next($request);
    }

    private function shouldBypass(Request $request): bool
    {
        return $request->is(
            'license/activate',
            'license/activate/*',
            'up',
            '_debugbar/*',
            '@vite/*',
        );
    }
}
