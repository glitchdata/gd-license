@extends('layouts.app')

@section('title', 'API Lab · GD Login')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">API Lab</p>
        <h1>Validate licenses without leaving the browser.</h1>
        <p class="lead">Experiment with the `POST /api/licenses/validate` endpoint, then wire the same payloads into your own services.</p>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
        <a class="link" href="{{ route('shop') }}">Browse products</a>
        <a class="link" href="{{ route('register') }}">Create dashboard account</a>
    </div>
</header>

<section class="card">
    <form id="license-test-form" style="display:grid;gap:1rem;">
        <label>
            <span>Product code</span>
            <input type="text" name="product_code" placeholder="LIC-ANL-01" required>
        </label>
        <label>
            <span>Seats requested (optional)</span>
            <input type="number" name="seats_requested" min="1" placeholder="1">
        </label>
        <button type="submit">Validate license</button>
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
    const resultCard = document.getElementById('result-card');
    const errorCard = document.getElementById('error-card');
    const statusPill = document.getElementById('status-pill');
    const resultJson = document.getElementById('result-json');
    const errorMessage = document.getElementById('error-message');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorCard.style.display = 'none';
        resultCard.style.display = 'none';
        const formData = new FormData(form);
        const payload = {
            product_code: formData.get('product_code'),
        };
        const seats = formData.get('seats_requested');
        if (seats) {
            payload.seats_requested = Number(seats);
        }

        try {
            const response = await fetch('{{ url('/api/licenses/validate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error('Request failed with status ' + response.status);
            }

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
