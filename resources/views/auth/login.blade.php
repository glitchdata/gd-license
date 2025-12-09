@extends('layouts.app')

@section('title', 'Sign in · GD Login')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Welcome back</p>
        <h1>Access your workspace</h1>
        <p class="lead">Sign in to reach your personalized dashboard.</p>
    </div>
</header>

<div class="grid">
    <section class="card">
        <h2>Sign in</h2>
        @if (session('status'))
            <div class="banner success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="banner error">
                <ul style="margin:0;padding-left:1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required>
            </label>
            <label style="display:flex;align-items:center;gap:0.5rem;">
                <input type="checkbox" name="remember" value="1" style="width:auto;"> Remember me
            </label>
            <button type="submit">Sign in</button>
            <button type="button" onclick="window.location='{{ route('oauth.redirect', ['provider' => 'google']) }}'" style="width:100%;display:flex;align-items:center;justify-content:center;gap:0.5rem;background:#fff;color:#0f172a;border:1px solid rgba(15,23,42,0.15);box-shadow:0 8px 18px rgba(15,23,42,0.08);">
                <span style="font-weight:700;">Continue with Google</span>
            </button>
            <button type="button" onclick="window.location='{{ route('oauth.redirect', ['provider' => 'meta']) }}'" style="width:100%;display:flex;align-items:center;justify-content:center;gap:0.5rem;background:#fff;color:#0f172a;border:1px solid rgba(15,23,42,0.15);box-shadow:0 8px 18px rgba(15,23,42,0.08);">
                <span style="font-weight:700;">Continue with Meta</span>
            </button>
            <p class="hint">Need an account? <a class="link" href="{{ route('register') }}">Create one</a>.</p>
        </form>
    </section>
</div>
@endsection
