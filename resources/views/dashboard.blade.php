@extends('layouts.app')

@section('title', 'Dashboard · GD Login')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Session active</p>
        <h1>Welcome, {{ $user->name }}!</h1>
        <p class="lead">You are signed in with Laravel sessions. Manage your account below.</p>
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Log out</button>
    </form>
</header>

@if ($user->is_admin)
    <section class="card" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div>
            <p class="eyebrow" style="margin-bottom:0.35rem;">Admin tools</p>
            <h2 style="margin:0;">Control users & licenses</h2>
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <a class="link" style="font-weight:600;" href="{{ route('admin.users.index') }}">Manage users →</a>
            <a class="link" style="font-weight:600;" href="{{ route('admin.products.index') }}">Manage products →</a>
            <a class="link" style="font-weight:600;" href="{{ route('admin.licenses.index') }}">Manage licenses →</a>
            <a class="link" style="font-weight:600;" href="{{ route('admin.tools.license-validation') }}">Test API →</a>
        </div>
    </section>
@endif

<section class="card">
    <h2>Account details</h2>
    <dl class="details">
        <div>
            <dt>Name</dt>
            <dd>{{ $user->name }}</dd>
        </div>
        <div>
            <dt>Email</dt>
            <dd>{{ $user->email }}</dd>
        </div>
        <div>
            <dt>Member since</dt>
            <dd>{{ $user->created_at->format('F j, Y') }}</dd>
        </div>
    </dl>
</section>

<section class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div>
            <p class="eyebrow" style="margin-bottom:0.35rem;">Your licenses</p>
            <h2 style="margin:0;">Assigned entitlements</h2>
        </div>
        <span style="font-weight:600;color:var(--primary);">{{ $licenses->count() }} active</span>
    </div>

    <div style="overflow-x:auto;margin-top:1.5rem;">
        <table style="width:100%;border-collapse:separate;border-spacing:0 0.5rem;">
            <thead>
                <tr style="text-align:left;color:var(--muted);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.1em;">
                    <th style="padding:0 0.75rem;">Product</th>
                    <th style="padding:0 0.75rem;">Code</th>
                    <th style="padding:0 0.75rem;">Seats</th>
                    <th style="padding:0 0.75rem;">Available</th>
                    <th style="padding:0 0.75rem;">Expires</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($licenses as $license)
                    <tr style="background:var(--bg);">
                        <td style="padding:0.9rem 0.75rem;font-weight:600;color:var(--text);">
                            <a href="{{ route('licenses.show', $license) }}" style="color:inherit;text-decoration:none;display:flex;flex-direction:column;gap:0.2rem;">
                                <span>{{ $license->product->name ?? '—' }}</span>
                                <span style="font-size:0.8rem;color:var(--muted);">View details →</span>
                            </a>
                        </td>
                        <td style="padding:0.9rem 0.75rem;font-family:monospace;">{{ $license->product->product_code ?? '—' }}</td>
                        <td style="padding:0.9rem 0.75rem;">{{ $license->seats_used }} / {{ $license->seats_total }}</td>
                        <td style="padding:0.9rem 0.75rem;color:{{ $license->seats_available > 0 ? 'var(--success)' : 'var(--error)' }};">
                            {{ $license->seats_available }}
                        </td>
                        <td style="padding:0.9rem 0.75rem;">
                            {{ $license->expires_at ? $license->expires_at->format('M j, Y') : 'No expiry' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding:1rem 0.75rem;text-align:center;color:var(--muted);">
                            No licenses have been assigned to you yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
