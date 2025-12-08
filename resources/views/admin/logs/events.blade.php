@extends('layouts.app')

@section('title', 'Admin · Event Logs')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Event logs</h1>
        <p class="lead">Recent login, purchase, and change events.</p>
    </div>
    <a class="link" href="{{ route('admin.home') }}">Back to admin</a>
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
