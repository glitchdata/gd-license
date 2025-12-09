@extends('layouts.app')

@section('title', 'Admin · Products')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Product catalog</h1>
        <p class="lead">Manage the master list of software products that licenses attach to.</p>
    </div>
    <div class="admin-nav">
        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
        <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Users</a>
        <a class="{{ request()->routeIs('admin.licenses.*') ? 'active' : '' }}" href="{{ route('admin.licenses.index') }}">Licenses</a>
        <a class="active" href="{{ route('admin.products.index') }}">Products</a>
        <a href="{{ route('admin.products.create') }}" class="{{ request()->routeIs('admin.products.create') ? 'active' : '' }}">+ New product</a>
    </div>
</header>

@if (session('status'))
    <div class="banner success">
        {{ session('status') }}
    </div>
@endif

<div class="card">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:separate;border-spacing:0 0.5rem;">
            <thead>
                <tr style="text-align:left;color:var(--muted);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.1em;">
                    <th style="padding:0 0.75rem;">Name</th>
                    <th style="padding:0 0.75rem;">Code</th>
                    <th style="padding:0 0.75rem;">Price</th>
                    <th style="padding:0 0.75rem;">Duration</th>
                    <th style="padding:0 0.75rem;">Vendor</th>
                    <th style="padding:0 0.75rem;">Category</th>
                    <th style="padding:0 0.75rem;">Description</th>
                    <th style="padding:0 0.75rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr style="background:var(--bg);">
                        <td style="padding:0.9rem 0.75rem;font-weight:600;">{{ $product->name }}</td>
                        <td style="padding:0.9rem 0.75rem;font-family:monospace;">{{ $product->product_code }}</td>
                        <td style="padding:0.9rem 0.75rem;">${{ number_format($product->price, 2) }}</td>
                        <td style="padding:0.9rem 0.75rem;">{{ $product->duration_months }} mo</td>
                        <td style="padding:0.9rem 0.75rem;">{{ $product->vendor ?? '—' }}</td>
                        <td style="padding:0.9rem 0.75rem;">{{ $product->category ?? '—' }}</td>
                        <td style="padding:0.9rem 0.75rem;max-width:320px;">{{ $product->description ? \Illuminate\Support\Str::limit($product->description, 120) : '—' }}</td>
                        <td style="padding:0.9rem 0.75rem;display:flex;gap:0.5rem;flex-wrap:wrap;">
                            <a class="link" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:none;border:none;color:var(--error);cursor:pointer;padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:1rem 0.75rem;text-align:center;color:var(--muted);">No products yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">
        {{ $products->links() }}
    </div>
</div>
@endsection
