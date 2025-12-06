@extends('layouts.app')

@section('title', 'Admin · Licenses')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>License management</h1>
        <p class="lead">Review, edit, and retire license allocations across the organization.</p>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
        <a class="link" href="{{ route('dashboard') }}">Back to dashboard</a>
        <a href="{{ route('admin.licenses.create') }}" class="link" style="font-weight:600;">+ New license</a>
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
                    <th style="padding:0 0.75rem;">Product</th>
                    <th style="padding:0 0.75rem;">Code</th>
                    <th style="padding:0 0.75rem;">Usage</th>
                    <th style="padding:0 0.75rem;">Expires</th>
                    <th style="padding:0 0.75rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($licenses as $license)
                    <tr style="background:var(--bg);">
                        <td style="padding:0.9rem 0.75rem;font-weight:600;">{{ $license->name }}</td>
                        <td style="padding:0.9rem 0.75rem;font-family:monospace;">{{ $license->product_code }}</td>
                        <td style="padding:0.9rem 0.75rem;">{{ $license->seats_used }} / {{ $license->seats_total }} ({{ $license->seats_available }} available)</td>
                        <td style="padding:0.9rem 0.75rem;">{{ $license->expires_at ? $license->expires_at->format('M j, Y') : 'No expiry' }}</td>
                        <td style="padding:0.9rem 0.75rem; display:flex; gap:0.5rem;">
                            <a class="link" href="{{ route('admin.licenses.edit', $license) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.licenses.destroy', $license) }}" onsubmit="return confirm('Delete this license?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:none;border:none;color:var(--error);cursor:pointer;padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding:1rem 0.75rem;text-align:center;color:var(--muted);">
                            No licenses available yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem;">
        {{ $licenses->links() }}
    </div>
</div>
@endsection
