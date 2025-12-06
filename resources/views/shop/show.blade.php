@extends('layouts.app')

@section('title', 'Shop · ' . $product->name)

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Shop</p>
        <h1>{{ $product->name }}</h1>
        <p class="lead">{{ $product->description ?: 'No marketing copy available yet.' }}</p>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
        <a class="link" href="{{ route('shop') }}">&larr; Back to catalog</a>
        <a class="link" style="font-weight:600;" href="{{ route('login') }}">Purchase in dashboard →</a>
    </div>
</header>

<section class="card" style="display:grid;gap:1.5rem;">
    <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;">
        <span style="font-size:3rem;font-weight:700;">${{ number_format($product->price, 2) }}<span style="font-size:1rem;font-weight:500;color:var(--muted);">/seat</span></span>
        <span style="font-weight:600;color:var(--muted);">{{ $product->duration_months }}-month term</span>
        <span style="font-family:monospace;color:var(--muted);">Code: {{ $product->product_code }}</span>
    </div>
    <dl class="details">
        <div>
            <dt>Vendor</dt>
            <dd>{{ $product->vendor ?? '—' }}</dd>
        </div>
        <div>
            <dt>Category</dt>
            <dd>{{ $product->category ?? '—' }}</dd>
        </div>
    </dl>
    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
        <a class="link" style="font-weight:600;" href="{{ route('register') }}">Need an account? Sign up →</a>
        <a class="link" href="{{ route('api.lab') }}">Validate via API Lab</a>
    </div>
</section>

@auth
    <section class="card" style="margin-top:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
            <div>
                <p class="eyebrow" style="margin-bottom:0.35rem;">Purchase</p>
                <h2 style="margin:0;">Buy seats for {{ $product->name }}</h2>
            </div>
            <span style="font-size:0.9rem;color:var(--muted);">Charged in dashboard currency (USD)</span>
        </div>

        <form method="POST" action="{{ route('licenses.store') }}" style="display:grid;gap:1rem;margin-top:1.25rem;" id="shop-purchase-form">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <label>
                <span>Seats needed</span>
                <input type="number" name="seats_total" id="shop-seats-input" min="1" value="{{ old('seats_total', 1) }}" required>
            </label>
            <label>
                <span>Primary domain (optional)</span>
                <input type="text" name="domain" placeholder="acme.com" value="{{ old('domain') }}">
            </label>
            <div style="padding:0.75rem 1rem;background:var(--bg);border-radius:0.75rem;font-weight:600;display:flex;justify-content:space-between;align-items:center;">
                <span>
                    Estimated total
                    <small style="display:block;font-weight:400;color:var(--muted);">Renews every {{ $product->duration_months }} months</small>
                </span>
                <span id="shop-purchase-total">$0.00</span>
            </div>
            <label>
                <span>Cardholder name</span>
                <input type="text" name="card_name" value="{{ old('card_name', auth()->user()->name) }}" required>
            </label>
            <label>
                <span>Card number</span>
                <input type="text" name="card_number" inputmode="numeric" placeholder="4242 4242 4242 4242" value="{{ old('card_number') }}" required>
            </label>
            <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:1rem;">
                <label>
                    <span>Exp. month</span>
                    <input type="number" name="card_exp_month" min="1" max="12" value="{{ old('card_exp_month') }}" required>
                </label>
                <label>
                    <span>Exp. year</span>
                    <input type="number" name="card_exp_year" min="{{ now()->year }}" max="{{ now()->year + 10 }}" value="{{ old('card_exp_year') }}" required>
                </label>
                <label>
                    <span>CVC</span>
                    <input type="number" name="card_cvc" inputmode="numeric" value="{{ old('card_cvc') }}" required>
                </label>
            </div>
            @error('payment')
                <p style="color:var(--error);font-weight:600;">{{ $message }}</p>
            @enderror
            <button type="submit">Purchase license</button>
        </form>
    </section>
@else
    <section class="card" style="margin-top:1.5rem;">
        <h2 style="margin-top:0;">Sign in to purchase</h2>
        <p style="color:var(--muted);">Create an account or log in to buy seats for this product from your dashboard.</p>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
            <a class="link" style="font-weight:600;" href="{{ route('login') }}">Log in →</a>
            <a class="link" href="{{ route('register') }}">Register</a>
        </div>
    </section>
@endauth
@endsection

@auth
    @push('scripts')
    <script>
    (function () {
        const seatsInput = document.getElementById('shop-seats-input');
        const totalEl = document.getElementById('shop-purchase-total');
        const pricePerSeat = {{ number_format($product->price, 2, '.', '') }};
        if (!seatsInput || !totalEl) {
            return;
        }

        const update = () => {
            const seats = parseInt(seatsInput.value, 10) || 0;
            const total = seats * pricePerSeat;
            totalEl.textContent = total > 0 ? `$${total.toFixed(2)}` : '$0.00';
        };

        seatsInput.addEventListener('input', update);
        update();
    })();
    </script>
    @endpush
@endauth
