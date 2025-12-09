@extends('layouts.app')

@section('title', 'Admin · Logs')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Logs</h1>
        <p class="lead">Recent login, purchase, and change events.</p>
    </div>
    <div class="admin-nav">
        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
        <a class="{{ request()->routeIs('admin.licenses.*') ? 'active' : '' }}" href="{{ route('admin.licenses.index') }}">Licenses</a>
        <a class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">Products</a>
        <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Users</a>
        <a class="active" href="{{ route('admin.event-logs.index') }}">Logs</a>
        <a class="{{ request()->routeIs('admin.tools.license-validation') ? 'active' : '' }}" href="{{ route('admin.tools.license-validation') }}">License Validation</a>
    </div>
</header>

@if ($logs->isEmpty())
    <div class="banner">No event logs yet.</div>
@else
    <div class="card" style="overflow:auto;">
        <table class="table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="text-align:left;padding:0.5rem;">When</th>
                    <th style="text-align:left;padding:0.5rem;">Type</th>
                    <th style="text-align:left;padding:0.5rem;">User</th>
                    <th style="text-align:left;padding:0.5rem;">Context</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td style="padding:0.5rem;white-space:nowrap;">{{ $log->created_at->toDateTimeString() }}</td>
                        <td style="padding:0.5rem;">{{ $log->type }}</td>
                        <td style="padding:0.5rem;">{{ $log->user_id ?? '—' }}</td>
                        <td style="padding:0.5rem;font-family:monospace;white-space:pre-wrap;word-break:break-word;">{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">
        {{ $logs->links() }}
    </div>
@endif
@endsection
