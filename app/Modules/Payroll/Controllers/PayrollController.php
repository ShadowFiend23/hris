<?php

namespace App\Modules\Payroll\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;

class PayrollController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Payroll/Payroll', []);
    }
}
