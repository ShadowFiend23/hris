<?php

namespace App\Modules\Timekeeping\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;

class TimekeepingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Timekeeping/Timekeeping', []);
    }
}
