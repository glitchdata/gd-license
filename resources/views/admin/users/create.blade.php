@extends('layouts.app')

@section('title', 'Admin · New User')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Create user</h1>
        <p class="lead">Invite a teammate with name, email, and role.</p>
    </div>
    <a class="link" href="{{ route('admin.users.index') }}">Back to users</a>
</header>

@if ($errors->any())
    <div class="banner error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @include('admin.users._form', ['user' => new \App\Models\User(), 'submitLabel' => 'Create user'])
    </form>
</div>
@endsection
