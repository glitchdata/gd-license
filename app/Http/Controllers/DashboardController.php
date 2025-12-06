<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'user' => Auth::user(),
            'licenses' => License::orderBy('name')->get(),
        ]);
    }
}
