<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\User;
use App\Services\EventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialLoginController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        $driver = $this->driver($provider);

        return $driver->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        try {
            $socialUser = $this->driver($provider)->stateless()->user();
        } catch (Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Unable to sign in with '.ucfirst($provider).': '.$e->getMessage(),
            ]);
        }

        if (! $socialUser->getEmail()) {
            return redirect()->route('login')->withErrors([
                'email' => ucfirst($provider).' did not return an email address.',
            ]);
        }

        $user = User::where('provider_name', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $user) {
            $user = User::where('email', $socialUser->getEmail())->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: ucfirst($provider).' User',
                'email' => $socialUser->getEmail(),
                'admin_email' => null,
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(40)),
            ]);
        } else {
            $user->forceFill([
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
            ])->save();
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        EventLogger::log(EventLog::TYPE_LOGIN, $user->id, [
            'provider' => $provider,
            'email' => $user->email,
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
        ]);

        return redirect()->intended(route('dashboard'))->with('status', 'Signed in with '.ucfirst($provider).'.');
    }

    private function driver(string $provider)
    {
        $provider = strtolower($provider);

        if (! in_array($provider, ['google', 'meta', 'facebook'], true)) {
            abort(404);
        }

        $scopes = match ($provider) {
            'google' => ['openid', 'profile', 'email'],
            default => ['public_profile', 'email'],
        };

        $driverName = $provider === 'meta' ? 'facebook' : $provider;

        return Socialite::driver($driverName)->scopes($scopes);
    }
}
