@extends('layouts.app')

@section('title', 'Shop · GD Login')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Shop</p>
        <h1>License plans built for growing teams</h1>
        <p class="lead">Browse the Glitchdata catalog, compare per-seat pricing, and start provisioning access in minutes.</p>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
        <a class="link" style="font-weight:600;" href="{{ route('login') }}">Sign in to purchase →</a>
        <a class="link" href="{{ route('register') }}">Create an account</a>
    </div>
</header>

<section class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Available products</h2>
    <p style="margin-bottom:1.5rem;color:var(--muted);">All prices are in USD and renew automatically at the end of each license duration. Purchasing occurs inside the secure dashboard.</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;">
        @forelse ($products as $product)
            <article style="border:1px solid rgba(15,23,42,0.08);border-radius:1rem;padding:1.25rem;display:flex;flex-direction:column;gap:0.75rem;background:var(--bg);">
                <div>
                    <p class="eyebrow" style="margin-bottom:0.25rem;color:var(--muted);">{{ $product->category ?? 'Software' }}</p>
                    <h3 style="margin:0;">{{ $product->name }}</h3>
                    <p style="margin:0;color:var(--muted);font-family:monospace;">{{ $product->product_code }}</p>
                </div>
                <p style="margin:0;">{{ $product->description ?: 'No marketing copy provided yet.' }}</p>
                <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center;">
                    <span style="font-size:2rem;font-weight:700;">${{ number_format($product->price, 2) }}<span style="font-size:1rem;font-weight:500;color:var(--muted);">/seat</span></span>
                    <span style="color:var(--muted);">{{ $product->duration_months }}-month term</span>
                </div>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:auto;">
                    <a class="link" style="font-weight:600;" href="{{ route('login') }}">Purchase in dashboard →</a>
                    <a class="link" href="{{ route('register') }}">Need an account?</a>
                </div>
            </article>
        @empty
            <p style="color:var(--muted);">No products are available yet. Please check back soon.</p>
        @endforelse
    </div>
</section>
@endsection
