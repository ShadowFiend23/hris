<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Models\PayrollSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocsController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = $request->user()?->company_id;
        $setting = $companyId
            ? PayrollSetting::where('company_id', $companyId)->first()
            : null;

        return Inertia::render('Docs', [
            'nightDifferentialRate' => (float) ($setting?->night_differential_rate ?? 0.10),
        ]);
    }
}
