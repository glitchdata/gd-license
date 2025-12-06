@csrf
<div class="stack" style="display:grid;gap:1rem;">
    <label>
        <span>Product</span>
        <select name="product_id" {{ $products->isEmpty() ? 'disabled' : '' }} required style="width:100%;border:1px solid rgba(15,23,42,0.15);border-radius:0.9rem;padding:0.85rem 1rem;font-size:1rem;">
            <option value="" disabled {{ old('product_id', $license->product_id ?? '') ? '' : 'selected' }}>Select a product</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" {{ (int) old('product_id', $license->product_id ?? '') === $product->id ? 'selected' : '' }}>
                    {{ $product->name }} ({{ $product->product_code }})
                </option>
            @endforeach
        </select>
    </label>
    <label>
        <span>Assigned user</span>
        <select name="user_id" style="width:100%;border:1px solid rgba(15,23,42,0.15);border-radius:0.9rem;padding:0.85rem 1rem;font-size:1rem;">
            <option value="" {{ old('user_id', $license->user_id ?? '') ? '' : 'selected' }}>Unassigned</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" {{ (int) old('user_id', $license->user_id ?? '') === $user->id ? 'selected' : '' }}>
                    {{ $user->name }} ({{ $user->email }})
                </option>
            @endforeach
        </select>
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
