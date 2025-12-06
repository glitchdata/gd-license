@extends('layouts.app')

@section('title', 'Admin · Edit License')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Edit license</h1>
        <p class="lead">Update allocation numbers or metadata for {{ $license->name }}.</p>
    </div>
    <a class="link" href="{{ route('admin.licenses.index') }}">Back to list</a>
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
    <form method="POST" action="{{ route('admin.licenses.update', $license) }}">
        @method('PUT')
        @include('admin.licenses._form', ['license' => $license, 'submitLabel' => 'Save changes', 'products' => $products, 'users' => $users])
    </form>
</div>
@endsection
