<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLicenseRequest;
use App\Http\Requests\UpdateLicenseRequest;
use App\Models\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function index(): View
    {
        return view('admin.licenses.index', [
            'licenses' => License::orderBy('name')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.licenses.create');
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
        return view('admin.licenses.edit', compact('license'));
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
