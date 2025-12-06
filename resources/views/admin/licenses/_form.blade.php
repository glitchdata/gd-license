@csrf
<div class="stack" style="display:grid;gap:1rem;">
    <label>
        <span>Name</span>
        <input type="text" name="name" value="{{ old('name', $license->name ?? '') }}" required>
    </label>
    <label>
        <span>Product code</span>
        <input type="text" name="product_code" value="{{ old('product_code', $license->product_code ?? '') }}" required>
    </label>
    <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
        <label>
            <span>Total seats</span>
            <input type="number" name="seats_total" min="1" value="{{ old('seats_total', $license->seats_total ?? 1) }}" required>
        </label>
        <label>
            <span>Seats in use</span>
            <input type="number" name="seats_used" min="0" value="{{ old('seats_used', $license->seats_used ?? 0) }}">
        </label>
        <label>
            <span>Expires on</span>
            <input type="date" name="expires_at" value="{{ old('expires_at', optional($license->expires_at ?? null)->format('Y-m-d')) }}">
        </label>
    </div>
</div>

<div style="margin-top:1.5rem;display:flex;gap:1rem;flex-wrap:wrap;">
    <button type="submit">{{ $submitLabel }}</button>
    <a class="link" href="{{ route('admin.licenses.index') }}">Cancel</a>
</div>
