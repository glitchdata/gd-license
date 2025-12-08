@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Account</p>
        <h1>Your profile</h1>
        <p class="lead">Update your credentials and keep your account secure.</p>
    </div>
    <a class="link" href="{{ route('dashboard') }}">Back to dashboard</a>
</header>

@if (session('status'))
    <div class="banner success">{{ session('status') }}</div>
@endif

<div class="card">
    <h2 style="margin-top:0;">Account info</h2>
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
            <dt>Role</dt>
            <dd>{{ $user->is_admin ? 'Admin' : 'User' }}</dd>
        </div>
    </dl>
</div>

<div class="card">
    <h2 style="margin-top:0;">Change password</h2>
    <form method="POST" action="{{ route('profile.password.update') }}" class="stack">
        @csrf
        <label>
            <span>Current password</span>
            <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        <label>
            <span>New password</span>
            <input type="password" name="password" required autocomplete="new-password">
        </label>
        <label>
            <span>Confirm new password</span>
            <input type="password" name="password_confirmation" required autocomplete="new-password">
        </label>
        @if ($errors->any())
            <div class="banner error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <button type="submit">Update password</button>
    </form>
</div>
@endsection
