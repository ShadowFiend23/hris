<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocsController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Docs');
    }
}
