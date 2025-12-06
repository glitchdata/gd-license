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
            <label>
                <span>Seats needed</span>
                <input type="number" name="seats_total" id="seats-input" min="1" value="{{ old('seats_total', 1) }}" required>
            </label>
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
            <label>
                <span>Cardholder name</span>
                <input type="text" name="card_name" value="{{ old('card_name', $user->name) }}" required>
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
                    <th style="padding:0 0.75rem;">Product</th>
                    <th style="padding:0 0.75rem;">Code</th>
                    <th style="padding:0 0.75rem;">Seats</th>
                    <th style="padding:0 0.75rem;">Available</th>
                    <th style="padding:0 0.75rem;">Expires</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($licenses as $license)
                    <tr style="background:var(--bg);">
                        <td style="padding:0.9rem 0.75rem;font-weight:600;color:var(--text);">
                            <a href="{{ route('licenses.show', $license) }}" style="color:inherit;text-decoration:none;display:flex;flex-direction:column;gap:0.2rem;">
                                <span>{{ $license->product->name ?? '—' }}</span>
                                <span style="font-size:0.8rem;color:var(--muted);">View details →</span>
                            </a>
                        </td>
                        <td style="padding:0.9rem 0.75rem;font-family:monospace;">{{ $license->product->product_code ?? '—' }}</td>
                        <td style="padding:0.9rem 0.75rem;">{{ $license->seats_used }} / {{ $license->seats_total }}</td>
                        <td style="padding:0.9rem 0.75rem;color:{{ $license->seats_available > 0 ? 'var(--success)' : 'var(--error)' }};">
                            {{ $license->seats_available }}
                        </td>
                        <td style="padding:0.9rem 0.75rem;">
                            {{ $license->expires_at ? $license->expires_at->format('M j, Y') : 'No expiry' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding:1rem 0.75rem;text-align:center;color:var(--muted);">
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
<script>
(function () {
    const select = document.getElementById('product-select');
    const seats = document.getElementById('seats-input');
    const total = document.getElementById('purchase-total');
    const durationText = document.getElementById('duration-text');
    if (!select || !seats || !total || !durationText) {
        return;
    }

    const updateTotal = () => {
        const price = parseFloat(select.selectedOptions[0]?.dataset.price || '0');
        const duration = parseInt(select.selectedOptions[0]?.dataset.duration || '0', 10);
        const seatCount = parseInt(seats.value, 10) || 0;
        const amount = price * seatCount;
        total.textContent = amount > 0 ? `$${amount.toFixed(2)}` : '$0.00';
        durationText.textContent = duration > 0 ? duration : '—';
    };

    select.addEventListener('change', updateTotal);
    seats.addEventListener('input', updateTotal);
    updateTotal();
})();
</script>
@endpush
