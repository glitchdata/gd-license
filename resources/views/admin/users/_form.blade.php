@csrf
<div class="stack" style="display:grid;gap:1rem;">
    <label>
        <span>Name</span>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required>
    </label>
    <label>
        <span>Email</span>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
    </label>
    <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
        <label>
            <span>Password {{ isset($user) && $user->exists ? '(leave blank to keep current)' : '' }}</span>
            <input type="password" name="password" {{ isset($user) && $user->exists ? '' : 'required' }}>
        </label>
        <label>
            <span>Confirm password</span>
            <input type="password" name="password_confirmation" {{ isset($user) && $user->exists ? '' : 'required' }}>
        </label>
    </div>
    <label style="display:flex;align-items:center;gap:0.5rem;">
        <input type="checkbox" name="is_admin" value="1" {{ old('is_admin', $user->is_admin ?? false) ? 'checked' : '' }}>
        <span>Grant admin access</span>
    </label>
</div>

<div style="margin-top:1.5rem;display:flex;gap:1rem;flex-wrap:wrap;">
    <button type="submit">{{ $submitLabel }}</button>
    <a class="link" href="{{ route('admin.users.index') }}">Cancel</a>
</div>
