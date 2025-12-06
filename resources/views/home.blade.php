@extends('layouts.app')

@section('title', 'Glitchdata · Identity & Licensing')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Glitchdata Platform</p>
        <h1>Identity, licensing, and validation in one simple portal.</h1>
        <p class="lead">Launch a secure dashboard for your team, grant software seats, and verify entitlements through a clean API toolkit.</p>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-top:1rem;">
            <a class="link" style="font-weight:600;" href="{{ route('shop') }}">Explore the shop →</a>
            <a class="link" href="{{ route('api.lab') }}">Test the API →</a>
            <a class="link" href="{{ route('register') }}">Create an account</a>
        </div>
    </div>
</header>

<section class="card">
    <div class="grid">
        <article>
            <p class="eyebrow" style="margin-bottom:0.35rem;">01 · Self-serve licenses</p>
            <h2 style="margin-top:0;">Purchase seats from the catalog</h2>
            <p>Browse curated products, preview pricing and duration, then assign licenses to yourself or your team members directly from the dashboard.</p>
            <a class="link" href="{{ route('shop') }}">Visit the shop</a>
        </article>
        <article>
            <p class="eyebrow" style="margin-bottom:0.35rem;">02 · API validation</p>
            <h2 style="margin-top:0;">Verify entitlements programmatically</h2>
            <p>Use the hosted API Lab to post product codes and seat counts, mirroring how your backend can confirm availability in production.</p>
            <a class="link" href="{{ route('api.lab') }}">Open the API Lab</a>
        </article>
        <article>
            <p class="eyebrow" style="margin-bottom:0.35rem;">03 · Admin tooling</p>
            <h2 style="margin-top:0;">Manage users, products, and licenses</h2>
            <p>Admins gain a polished console to edit products, onboard users, and audit allocations—no extra front-end build pipeline required.</p>
            <a class="link" href="{{ route('login') }}">Sign in as admin</a>
        </article>
    </div>
</section>

<section class="card alt">
    <div style="display:flex;flex-direction:column;gap:1rem;">
        <div>
            <p class="eyebrow" style="color:rgba(255,255,255,0.7);">API quickstart</p>
            <h2 style="margin:0;">`POST /api/licenses/validate`</h2>
            <p style="margin:0;color:rgba(255,255,255,0.8);">Send a product code plus requested seats to confirm availability, expiration, and seat counts—all responses structured for easy automation.</p>
        </div>
        <pre style="margin:0;background:rgba(0,0,0,0.3);padding:1rem;border-radius:0.9rem;color:#fff;font-family:monospace;overflow:auto;">{
  "product_code": "LIC-ANL-01",
  "seats_requested": 3
}</pre>
        <div>
            <a class="link" style="color:#fff;font-weight:700;" href="{{ route('api.lab') }}">Send a sample request →</a>
        </div>
    </div>
</section>
@endsection
