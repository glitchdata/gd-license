@extends('layouts.app')

@section('title', 'Admin · New License')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Create license</h1>
        <p class="lead">Add a new software entitlement to the catalog.</p>
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

@if ($products->isEmpty())
    <div class="banner error">
        <p>Please add a product to the catalog before creating licenses.</p>
    </div>
@endif

<div class="card">
    <form method="POST" action="{{ route('admin.licenses.store') }}">
        @include('admin.licenses._form', ['license' => new \App\Models\License(), 'submitLabel' => 'Create license', 'products' => $products, 'users' => $users])
    </form>
</div>
@endsection
