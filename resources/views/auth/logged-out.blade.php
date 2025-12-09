@extends('layouts.app')

@section('title', 'Signed out · GD Login')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Session ended</p>
        <h1>You have been logged out</h1>
        <p class="lead">For security, we ended your session. Sign in again to continue.</p>
    </div>
    <a class="link" href="{{ route('home') }}">Return home</a>
</header>

<section class="card" style="max-width:720px;">
    <p style="margin-top:0;color:var(--muted);">If this wasn’t you, consider changing your password and enabling two-factor authentication.</p>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-top:1rem;">
        <a href="{{ route('login') }}" class="link" style="display:inline-flex;align-items:center;gap:0.35rem;font-weight:700;">
            <span>Sign in again</span>
        </a>
        <a href="{{ route('register') }}" class="link" style="display:inline-flex;align-items:center;gap:0.35rem;">
            <span>Create an account</span>
        </a>
    </div>
</section>
@endsection
