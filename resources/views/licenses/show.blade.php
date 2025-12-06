@extends('layouts.app')

@section('title', 'License · ' . ($license->product->name ?? 'Unassigned product'))

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">License detail</p>
        <h1>{{ $license->product->name ?? 'License #' . $license->id }}</h1>
        <p class="lead">Review entitlement specifics and validate its current status against the API.</p>
    </div>
    <a class="link" href="{{ route('dashboard') }}" style="font-weight:600;">&larr; Back to dashboard</a>
</header>

<section class="card">
    <h2 style="margin-top:0;">Entitlement snapshot</h2>
    <dl class="details">
        <div>
            <dt>Product</dt>
            <dd>{{ $license->product->name ?? '—' }}</dd>
        </div>
        <div>
            <dt>Product code</dt>
            <dd style="font-family:monospace;">{{ $license->product->product_code ?? '—' }}</dd>
        </div>
        <div>
            <dt>License ID</dt>
            <dd>#{{ $license->id }}</dd>
        </div>
        <div>
            <dt>Total seats</dt>
            <dd>{{ $license->seats_total }}</dd>
        </div>
        <div>
            <dt>Seats in use</dt>
            <dd>{{ $license->seats_used }}</dd>
        </div>
        <div>
            <dt>Seats available</dt>
            <dd style="color:{{ $license->seats_available > 0 ? 'var(--success)' : 'var(--error)' }};">{{ $license->seats_available }}</dd>
        </div>
        <div>
            <dt>Expires</dt>
            <dd>{{ $license->expires_at ? $license->expires_at->format('F j, Y') : 'No expiry' }}</dd>
        </div>
        <div>
            <dt>Assigned email</dt>
            <dd>{{ $license->user->email ?? auth()->user()->email }}</dd>
        </div>
    </dl>
</section>

<section class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div>
            <p class="eyebrow" style="margin-bottom:0.35rem;">API diagnostics</p>
            <h2 style="margin:0;">Test this license</h2>
        </div>
        <span style="font-size:0.9rem;color:var(--muted);">POST /api/licenses/validate</span>
    </div>

    <form id="license-test-form" data-product-code="{{ $license->product->product_code ?? '' }}" style="display:grid;gap:1rem;margin-top:1.25rem;">
        <label>
            <span>Product code</span>
            <input type="text" id="product-code" value="{{ $license->product->product_code ?? 'Unavailable' }}" {{ $license->product ? 'readonly' : 'disabled' }}>
        </label>
        <label>
            <span>Seats requested (optional)</span>
            <input type="number" id="seats-requested" name="seats_requested" min="1" placeholder="1">
        </label>
        <button type="submit" {{ $license->product ? '' : 'disabled' }}>Validate license</button>
    </form>
</section>

<section class="card" id="result-card" style="display:none;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
        <h2 style="margin:0;">Response</h2>
        <span id="status-pill" style="border-radius:999px;padding:0.25rem 0.75rem;font-weight:600;"></span>
    </div>
    <pre id="result-json" style="margin-top:1rem;background:var(--bg);padding:1rem;border-radius:0.75rem;overflow:auto;"></pre>
</section>

<section class="card" id="error-card" style="display:none;">
    <h2 style="margin-top:0;">Error</h2>
    <p id="error-message" style="color:var(--error);"></p>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('license-test-form');
    if (!form) {
        return;
    }

    const resultCard = document.getElementById('result-card');
    const errorCard = document.getElementById('error-card');
    const statusPill = document.getElementById('status-pill');
    const resultJson = document.getElementById('result-json');
    const errorMessage = document.getElementById('error-message');
    const productCodeInput = document.getElementById('product-code');
    const seatsRequestedInput = document.getElementById('seats-requested');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!productCodeInput.value || productCodeInput.disabled) {
            return;
        }

        resultCard.style.display = 'none';
        errorCard.style.display = 'none';

        const payload = {
            product_code: productCodeInput.value,
        };
        const seatsValue = seatsRequestedInput.value;
        if (seatsValue) {
            payload.seats_requested = Number(seatsValue);
        }

        try {
            const response = await fetch('{{ url('/api/licenses/validate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const json = await response.json();
            statusPill.textContent = json.valid ? 'VALID' : 'INVALID';
            statusPill.style.background = json.valid ? 'rgba(22, 163, 74, 0.15)' : 'rgba(220, 38, 38, 0.15)';
            statusPill.style.color = json.valid ? 'var(--success)' : 'var(--error)';
            resultJson.textContent = JSON.stringify(json, null, 2);
            resultCard.style.display = 'block';
        } catch (error) {
            errorMessage.textContent = error.message || 'Unable to reach the API.';
            errorCard.style.display = 'block';
        }
    });
})();
</script>
@endpush
