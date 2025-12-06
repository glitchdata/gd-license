<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserLicenseController extends Controller
{
    public function show(License $license): View
    {
        abort_unless($license->user_id === Auth::id(), 404);

        return view('licenses.show', [
            'license' => $license->load(['product', 'user']),
        ]);
    }
}
