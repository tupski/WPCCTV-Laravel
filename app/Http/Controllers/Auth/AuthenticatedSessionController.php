<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, RateLimiter $limiter): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $emailKey = 'login:email:'.hash('sha256', mb_strtolower(trim($credentials['email'])));
        $ipKey = 'login:ip:'.hash('sha256', (string) $request->ip());
        $genericError = 'Email atau kata sandi tidak sesuai.';

        if ($limiter->tooManyAttempts($emailKey, 5) || $limiter->tooManyAttempts($ipKey, 30)) {
            throw ValidationException::withMessages(['email' => $genericError]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $limiter->hit($emailKey, 60);
            $limiter->hit($ipKey, 60);

            throw ValidationException::withMessages(['email' => $genericError]);
        }

        $limiter->clear($emailKey);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
