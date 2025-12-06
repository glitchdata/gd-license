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
            'licenses' => License::with(['product', 'user', 'domains'])->latest()->paginate(10),
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
        $payload = $request->safe()->except('domains');
        $license = License::create($payload);
        $this->syncDomains($license, $request->input('domains'));

        return redirect()
            ->route('admin.licenses.index')
            ->with('status', 'License created successfully.');
    }

    public function edit(License $license): View
    {
        return view('admin.licenses.edit', [
            'license' => $license->load(['product', 'user', 'domains']),
            'products' => Product::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateLicenseRequest $request, License $license): RedirectResponse
    {
        $payload = $request->safe()->except('domains');
        $license->update($payload);
        $this->syncDomains($license, $request->input('domains'));

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

    private function syncDomains(License $license, ?string $domainsInput): void
    {
        $domains = collect(preg_split('/[,\n]+/', (string) $domainsInput))
            ->map(fn ($domain) => strtolower(trim($domain)))
            ->filter()
            ->unique()
            ->take(50)
            ->values();

        $license->domains()->delete();

        if ($domains->isEmpty()) {
            return;
        }

        $license->domains()->createMany(
            $domains->map(fn ($domain) => ['domain' => $domain])->all()
        );
    }
}
