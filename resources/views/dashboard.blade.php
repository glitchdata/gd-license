@extends('layouts.app')

@section('title', 'Dashboard · GD Login')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Session active</p>
        <h1>Welcome, {{ $user->name }}!</h1>
        <p class="lead">You are signed in with Laravel sessions. Manage your account below.</p>
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Log out</button>
    </form>
</header>

@if (session('status'))
    <div class="banner success">
        {{ session('status') }}
    </div>
@endif

@if ($user->is_admin)
    <section class="card" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div>
            <p class="eyebrow" style="margin-bottom:0.35rem;">Admin tools</p>
            <h2 style="margin:0;">Control users & licenses</h2>
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <a class="link" style="font-weight:600;" href="{{ route('admin.users.index') }}">Manage users →</a>
            <a class="link" style="font-weight:600;" href="{{ route('admin.products.index') }}">Manage products →</a>
            <a class="link" style="font-weight:600;" href="{{ route('admin.licenses.index') }}">Manage licenses →</a>
            <a class="link" style="font-weight:600;" href="{{ route('admin.tools.license-validation') }}">Test API →</a>
            <a class="link" style="font-weight:600;" href="{{ route('email.test') }}">Send test email →</a>
        </div>
    </section>
@endif

<section class="card">
    <h2>Account details</h2>
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
            <dt>Admin contact</dt>
            <dd>{{ $user->admin_email ?? '—' }}</dd>
        </div>
        <div>
            <dt>Member since</dt>
            <dd>{{ $user->created_at->format('F j, Y') }}</dd>
        </div>
    </dl>
</section>

<section class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div>
            <p class="eyebrow" style="margin-bottom:0.35rem;">Purchase access</p>
            <h2 style="margin:0;">Add another license</h2>
        </div>
        <span style="font-size:0.9rem;color:var(--muted);">Licenses assign directly to your account</span>
    </div>

    @if ($errors->any())
        <div class="banner error" style="margin-top:1rem;">
            <ul style="margin:0;padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($products->isEmpty())
        <p style="margin-top:1.25rem;color:var(--muted);">No products are available for purchase right now. Please check back later.</p>
    @else
        <form method="POST" action="{{ route('licenses.store') }}" style="display:grid;gap:1rem;margin-top:1.5rem;" id="purchase-form">
            @csrf
            <label>
                <span>Product</span>
                <select name="product_id" id="product-select" required style="width:100%;border:1px solid rgba(15,23,42,0.15);border-radius:0.9rem;padding:0.85rem 1rem;font-size:1rem;">
                    <option value="" disabled {{ old('product_id') ? '' : 'selected' }}>Choose a product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}"
                                data-price="{{ number_format($product->price, 2, '.', '') }}"
                                data-duration="{{ $product->duration_months }}"
                                {{ (int) old('product_id') === $product->id ? 'selected' : '' }}>
                            {{ $product->name }} ({{ $product->product_code }}) · ${{ number_format($product->price, 2) }}/seat
                        </option>
                    @endforeach
                </select>
            </label>
            <input type="hidden" name="seats_total" id="seats-input" value="1">
            <label>
                <span>Primary domain (optional)</span>
                <input type="text" name="domain" placeholder="acme.com" value="{{ old('domain') }}">
            </label>
            <div style="padding:0.75rem 1rem;background:var(--bg);border-radius:0.75rem;font-weight:600;display:flex;justify-content:space-between;align-items:center;">
                <span>
                    Estimated total
                    <small style="display:block;font-weight:400;color:var(--muted);">Renews every <span id="duration-text">0</span> months</small>
                </span>
                <span id="purchase-total">$0.00</span>
            </div>
            <input type="hidden" name="paypal_order_id" id="paypal-order-id">
            <p style="margin:0;color:var(--muted);font-size:0.95rem;">Checkout is powered by PayPal. Each purchase provides a single-seat license tied to your account.</p>
            <div id="paypal-buttons-dashboard"></div>
            <p id="paypal-errors-dashboard" style="display:none;color:var(--error);font-weight:600;"></p>
            @error('payment')
                <p style="color:var(--error);font-weight:600;">{{ $message }}</p>
            @enderror
            @if (! $paypalClientId)
                <p style="color:var(--error);font-weight:600;">Set PAYPAL_CLIENT_ID and PAYPAL_SECRET in your environment file to enable checkout.</p>
            @endif
        </form>
    @endif
</section>

<section class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div>
            <p class="eyebrow" style="margin-bottom:0.35rem;">Your licenses</p>
            <h2 style="margin:0;">Assigned entitlements</h2>
        </div>
        <span style="font-weight:600;color:var(--primary);">{{ $licenses->count() }} active</span>
    </div>

    <div style="overflow-x:auto;margin-top:1.5rem;">
        <table style="width:100%;border-collapse:separate;border-spacing:0 0.5rem;">
            <thead>
                <tr style="text-align:left;color:var(--muted);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.1em;">
                    <th style="padding:0 0.75rem;">License</th>
                    <th style="padding:0 0.75rem;">Domain</th>
                    <th style="padding:0 0.75rem;">Identifier</th>
                    <th style="padding:0 0.75rem;">Expires</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($licenses as $license)
                    <tr style="background:var(--bg);">
                        <td style="padding:0.9rem 0.75rem;font-weight:600;color:var(--text);">
                            <a href="{{ $license->inspect_uri }}" style="color:inherit;text-decoration:none;display:flex;flex-direction:column;gap:0.2rem;">
                                <span>License #{{ $license->id }}</span>
                                <span style="font-size:0.8rem;color:var(--muted);">View details →</span>
                            </a>
                        </td>
                        <td style="padding:0.9rem 0.75rem;">
                            @php
                                $domains = $license->domains->pluck('domain')->filter()->take(2);
                            @endphp
                            {{ $domains->isNotEmpty() ? $domains->join(', ') : '—' }}
                            @if ($license->domains->count() > 2)
                                <span style="display:block;font-size:0.75rem;color:var(--muted);">+{{ $license->domains->count() - 2 }} more</span>
                            @endif
                        </td>
                        <td style="padding:0.9rem 0.75rem;font-family:monospace;">
                            {{ $license->identifier ?? '—' }}
                        </td>
                        <td style="padding:0.9rem 0.75rem;">
                            {{ $license->expires_at ? $license->expires_at->format('M j, Y') : 'No expiry' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding:1rem 0.75rem;text-align:center;color:var(--muted);">
                            No licenses have been assigned to you yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
@if ($paypalClientId)
    <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $paypalCurrency ?? 'USD' }}" data-sdk-integration-source="button-factory"></script>
@endif
<script>
(function () {
    const select = document.getElementById('product-select');
    const seats = document.getElementById('seats-input');
    const total = document.getElementById('purchase-total');
    const durationText = document.getElementById('duration-text');
    const form = document.getElementById('purchase-form');
    const domainInput = form ? form.querySelector('input[name="domain"]') : null;
    const orderInput = document.getElementById('paypal-order-id');
    const paypalErrors = document.getElementById('paypal-errors-dashboard');
    const paypalEnabled = {{ $paypalClientId ? 'true' : 'false' }};

    const clearOrder = () => {
        if (orderInput) {
            orderInput.value = '';
        }
        if (form) {
            form.dataset.paypalReady = 'false';
        }
    };

    const updateTotal = () => {
        if (!select || !total || !durationText) {
            return;
        }

        const price = parseFloat(select.selectedOptions[0]?.dataset.price || '0');
        const duration = parseInt(select.selectedOptions[0]?.dataset.duration || '0', 10);
        const amount = price;
        total.textContent = amount > 0 ? `$${amount.toFixed(2)}` : '$0.00';
        durationText.textContent = duration > 0 ? duration : '—';
    };

    if (select) {
        select.addEventListener('change', () => {
            clearOrder();
            updateTotal();
        });
    }

    updateTotal();

    const showError = (message) => {
        if (!paypalErrors) {
            return;
        }

        paypalErrors.textContent = message;
        paypalErrors.style.display = 'block';
    };

    if (form) {
        form.dataset.paypalReady = 'false';
        form.addEventListener('submit', (event) => {
            if (form.dataset.paypalReady !== 'true') {
                event.preventDefault();
            }
        });
    }

    const renderButtons = () => {
        const container = document.getElementById('paypal-buttons-dashboard');
        if (!container || !paypalEnabled) {
            return;
        }

        if (!window.paypal) {
            showError('Unable to load the PayPal SDK. Verify PAYPAL_CLIENT_ID.');
            return;
        }

        paypal.Buttons({
            style: {
                layout: 'horizontal',
                label: 'pay',
                color: 'gold',
                shape: 'rect',
            },
            createOrder: async () => {
                clearOrder();
                if (paypalErrors) {
                    paypalErrors.style.display = 'none';
                }

                if (!select || !select.value) {
                    throw new Error('Select a product before checking out.');
                }

                const payload = {
                    product_id: select.value,
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

                if (orderInput) {
                    orderInput.value = data.order_id;
                }

                return data.order_id;
            },
            onApprove: (data) => {
                if (!form) {
                    return;
                }

                if (orderInput) {
                    orderInput.value = data.orderID;
                }

                if (paypalErrors) {
                    paypalErrors.style.display = 'none';
                }
                form.dataset.paypalReady = 'true';
                form.submit();
            },
            onCancel: () => {
                showError('PayPal checkout was cancelled.');
                clearOrder();
            },
            onError: (err) => {
                showError(err?.message || 'PayPal reported an unexpected error.');
                clearOrder();
            },
        }).render('#paypal-buttons-dashboard');
    };

    renderButtons();
})();
</script>
@endpush
