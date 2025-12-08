@extends('layouts.app')

@section('title', 'Shop · ' . $product->name)

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Shop</p>
        <h1>{{ $product->name }}</h1>
        <p class="lead">{{ $product->description ?: 'No marketing copy available yet.' }}</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0.5rem;align-items:center;">
        <a class="link" href="{{ route('shop') }}" style="display:block;text-align:center;padding:0.65rem 0.9rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);font-weight:600;">← Back to catalog</a>
        <a class="link" href="{{ route('login') }}" style="display:block;text-align:center;padding:0.65rem 0.9rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);font-weight:600;">Purchase in dashboard</a>
    </div>
</header>

<section class="card" style="display:grid;gap:1.25rem;">
    <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;">
        <div style="display:flex;align-items:flex-end;gap:0.5rem;">
            <span style="font-size:3rem;font-weight:700;">${{ number_format($product->price, 2) }}</span>
            <span style="font-size:1rem;font-weight:600;color:var(--muted);">/seat</span>
        </div>
        <span style="font-weight:600;color:var(--muted);">{{ $product->duration_months }}-month term</span>
        <span style="font-family:monospace;color:var(--muted);">Code: {{ $product->product_code }}</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
        <div style="background:var(--bg);padding:0.9rem 1rem;border-radius:0.85rem;">
            <p style="margin:0;font-size:0.8rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);">Vendor</p>
            <p style="margin:0.2rem 0 0;font-weight:600;">{{ $product->vendor ?? '—' }}</p>
        </div>
        <div style="background:var(--bg);padding:0.9rem 1rem;border-radius:0.85rem;">
            <p style="margin:0;font-size:0.8rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);">Category</p>
            <p style="margin:0.2rem 0 0;font-weight:600;">{{ $product->category ?? '—' }}</p>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0.5rem;">
        <a class="link" style="display:block;text-align:center;padding:0.65rem 0.9rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);font-weight:600;" href="{{ route('register') }}">Need an account? Sign up</a>
        <a class="link" style="display:block;text-align:center;padding:0.65rem 0.9rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);font-weight:600;" href="{{ route('api.lab') }}">Validate via API Lab</a>
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
            <input type="hidden" name="seats_total" id="shop-seats-input" value="1">
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
            <input type="hidden" name="paypal_order_id" id="shop-paypal-order">
            <p style="margin:0;color:var(--muted);font-size:0.95rem;">Checkout is powered by PayPal. Each purchase provides one license seat—approve the popup to finish.</p>
            <div style="padding:0.5rem;border:1px solid rgba(15,23,42,0.08);border-radius:0.85rem;background:#fff;box-shadow:0 8px 20px rgba(15,23,42,0.06);">
                <div id="paypal-buttons-shop"></div>
                <p style="margin:0.35rem 0 0;color:var(--muted);font-size:0.9rem;">Pay with PayPal</p>
            </div>
            <p id="paypal-errors-shop" style="display:none;color:var(--error);font-weight:600;"></p>
            @error('payment')
                <p style="color:var(--error);font-weight:600;">{{ $message }}</p>
            @enderror
            @if (! $paypalClientId)
                <p style="color:var(--error);font-weight:600;">Set PAYPAL_CLIENT_ID and PAYPAL_SECRET in your environment file to enable checkout.</p>
            @endif
        </form>
    </section>
@endauth

@endsection

@push('scripts')
@if ($paypalClientId)
    <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $paypalCurrency ?? 'USD' }}" data-sdk-integration-source="button-factory"></script>
@endif
<script>
(function () {
    const form = document.getElementById('shop-purchase-form');
    const total = document.getElementById('shop-purchase-total');
    const domainInput = form ? form.querySelector('input[name="domain"]') : null;
    const paypalOrderInput = document.getElementById('shop-paypal-order');
    const paypalErrors = document.getElementById('paypal-errors-shop');
    const paypalEnabled = {{ $paypalClientId ? 'true' : 'false' }};
    const price = parseFloat('{{ number_format($product->price, 2, '.', '') }}');

    const showError = (message) => {
        if (paypalErrors) {
            paypalErrors.textContent = message;
            paypalErrors.style.display = 'block';
        }
    };

    const clearError = () => {
        if (paypalErrors) {
            paypalErrors.textContent = '';
            paypalErrors.style.display = 'none';
        }
    };

    const clearOrder = () => {
        if (paypalOrderInput) {
            paypalOrderInput.value = '';
        }
        if (form) {
            form.dataset.paypalReady = 'false';
        }
    };

    const updateTotal = () => {
        if (total && Number.isFinite(price)) {
            total.textContent = `$${price.toFixed(2)}`;
        }
    };

    if (form) {
        form.addEventListener('submit', (event) => {
            if (paypalEnabled && form.dataset.paypalReady !== 'true') {
                event.preventDefault();
                showError('Please approve the PayPal popup to complete checkout.');
            }
        });
    }

    updateTotal();

    if (paypalEnabled) {
        const renderButtons = () => {
            const paypalContainer = document.getElementById('paypal-buttons-shop');

            if (!window.paypal) {
                if (paypalContainer) {
                    showError('PayPal SDK is not available.');
                }
                return;
            }

            const options = {
                style: {
                    layout: 'vertical',
                    color: 'gold',
                    shape: 'rect',
                },
                createOrder: async () => {
                    clearError();
                    if (!form) {
                        throw new Error('Form not ready.');
                    }
                    const payload = {
                        product_id: form.querySelector('input[name="product_id"]').value,
                        seats_total: 1,
                        domain: domainInput ? domainInput.value : null,
                    };
                    const response = await fetch('{{ route('paypal.orders.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(payload),
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to create a PayPal order.');
                    }
                    paypalOrderInput.value = data.order_id;
                    return data.order_id;
                },
                onApprove: (data) => {
                    clearError();
                    if (paypalOrderInput) {
                        paypalOrderInput.value = data.orderID;
                    }
                    if (form) {
                        form.dataset.paypalReady = 'true';
                        form.submit();
                    }
                },
                onCancel: () => {
                    showError('Checkout was cancelled.');
                    clearOrder();
                },
                onError: (err) => {
                    showError(err && err.message ? err.message : 'Payment reported an error.');
                    clearOrder();
                }
            };

            if (paypalContainer) {
                window.paypal.Buttons(options).render('#paypal-buttons-shop');
            }
        };

        renderButtons();
    } else if (!window.paypal && document.getElementById('paypal-buttons-shop')) {
        showError('PayPal SDK is not available.');
    }
})();
</script>
@endpush
