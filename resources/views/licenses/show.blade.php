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

@if (session('status'))
    <div class="banner success">
        {{ session('status') }}
    </div>
@endif

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
            <dt>License identifier</dt>
            <dd style="font-family:monospace;">{{ $license->identifier }}</dd>
        </div>
        <div>
            <dt>Inspect URI</dt>
            <dd>
                <a href="{{ $license->inspect_uri }}" target="_blank" rel="noopener" style="font-family:monospace;color:inherit;">
                    {{ $license->inspect_uri }}
                </a>
            </dd>
        </div>
        <div>
            <dt>Public validator</dt>
            <dd style="font-family:monospace;">
                <a href="{{ $license->public_validator_uri }}" target="_blank" rel="noopener" style="color:inherit;">
                    {{ $license->public_validator_uri }}
                </a>
            </dd>
        </div>
        <div>
            <dt>Per-seat rate</dt>
            <dd>${{ number_format($license->product->price ?? 0, 2) }}</dd>
        </div>
        <div>
            <dt>Duration</dt>
            <dd>{{ $license->product->duration_months ?? '—' }} months</dd>
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
            <dt>Purchase total</dt>
            <dd>${{ number_format(($license->product->price ?? 0) * $license->seats_total, 2) }}</dd>
        </div>
        <div>
            <dt>Expires</dt>
            <dd>{{ $license->expires_at ? $license->expires_at->format('F j, Y') : 'No expiry' }}</dd>
        </div>
        <div>
            <dt>Assigned email</dt>
            <dd>{{ $license->user->email ?? auth()->user()->email }}</dd>
        </div>
        <div style="grid-column:1 / -1;">
            <dt>Allowed domains</dt>
            <dd style="margin-top:0.5rem;">
                @if ($license->domains->isEmpty())
                    <span style="color:var(--muted);">No domain restrictions configured.</span>
                @else
                    <div style="display:flex;flex-wrap:wrap;gap:0.35rem;">
                        @foreach ($license->domains as $domain)
                            <span style="background:rgba(15,23,42,0.08);border-radius:999px;padding:0.2rem 0.65rem;font-size:0.85rem;">{{ $domain->domain }}</span>
                        @endforeach
                    </div>
                @endif
            </dd>
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

    <form id="license-test-form" data-license-code="{{ $license->identifier }}" style="display:grid;gap:1rem;margin-top:1.25rem;">
        <label>
            <span>License code</span>
            <input type="text" id="license-code" value="{{ $license->identifier }}" readonly>
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
    const licenseCodeInput = document.getElementById('license-code');
    // const seatsRequestedInput = document.getElementById('seats-requested');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!licenseCodeInput.value || licenseCodeInput.disabled) {
            return;
        }

        resultCard.style.display = 'none';
        errorCard.style.display = 'none';

        const payload = {
            license_code: licenseCodeInput.value,
        };

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
