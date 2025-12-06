@extends('layouts.app')

@section('title', 'Admin · Edit User')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Edit user</h1>
        <p class="lead">Update profile details or admin access for {{ $user->name }}.</p>
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
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @method('PUT')
        @include('admin.users._form', ['user' => $user, 'submitLabel' => 'Save changes'])
    </form>
</div>
@endsection
