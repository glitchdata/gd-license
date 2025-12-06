<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLicenseRequest;
use App\Http\Requests\UpdateLicenseRequest;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function index(): View
    {
        return view('admin.licenses.index', [
            'licenses' => License::with(['product', 'user'])->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.licenses.create', [
            'products' => Product::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(StoreLicenseRequest $request): RedirectResponse
    {
        License::create($request->validated());

        return redirect()
            ->route('admin.licenses.index')
            ->with('status', 'License created successfully.');
    }

    public function edit(License $license): View
    {
        return view('admin.licenses.edit', [
            'license' => $license->load(['product', 'user']),
            'products' => Product::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateLicenseRequest $request, License $license): RedirectResponse
    {
        $license->update($request->validated());

        return redirect()
            ->route('admin.licenses.index')
            ->with('status', 'License updated successfully.');
    }

    public function destroy(License $license): RedirectResponse
    {
        $license->delete();

        return redirect()
            ->route('admin.licenses.index')
            ->with('status', 'License removed.');
    }
}
