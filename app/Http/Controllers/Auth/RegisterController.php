<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create(): View
    {
        return view('auth.register', [
            'captchaQuestion' => $this->captchaChallenge(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'captcha' => ['required', function ($attribute, $value, $fail) {
                $expected = session('captcha.answer');

                if ($expected === null) {
                    $fail('Captcha session expired. Please try again.');
                    return;
                }

                if ((int) $value !== (int) $expected) {
                    $fail('Captcha answer is incorrect.');
                }
            }],
        ]);

        session()->forget(['captcha.answer', 'captcha.challenge']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard')->with('status', 'Account created. Welcome aboard!');
    }

    private function captchaChallenge(): string
    {
        $existing = session('captcha.challenge');
        if ($existing) {
            return $existing;
        }

        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $question = $a.' + '.$b;

        session([
            'captcha.challenge' => $question,
            'captcha.answer' => $a + $b,
        ]);

        return $question;
    }
}
