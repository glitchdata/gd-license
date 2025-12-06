@extends('layouts.app')

@section('title', 'Create account · GD Login')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">New here?</p>
        <h1>Create your GD Login account</h1>
        <p class="lead">Fill out the details below to unlock your dashboard instantly.</p>
    </div>
</header>

<div class="grid">
    <section class="card alt">
        <h2>Create account</h2>
        <form method="POST" action="{{ route('register') }}">
            @csrf
            @if ($errors->any())
                <div class="banner error">
                    <ul style="margin:0;padding-left:1.25rem;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <label>
                <span>Name</span>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required>
            </label>
            <label>
                <span>Confirm password</span>
                <input type="password" name="password_confirmation" required>
            </label>
            <label>
                <span>Solve this to prove you're human: {{ $captchaQuestion ?? session('captcha.challenge') ?? '1 + 1' }}</span>
                <input type="number" name="captcha" inputmode="numeric" min="0" required>
            </label>
            <button type="submit">Create account</button>
            <p class="hint">Already have access? <a class="link" href="{{ route('login') }}">Sign in</a>.</p>
        </form>
    </section>
</div>
@endsection
