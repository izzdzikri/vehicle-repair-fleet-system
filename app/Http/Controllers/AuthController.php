<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin() {
        return view('auth.login');
    }

public function login(Request $request) {
    $credentials = $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

    if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        return back()->withErrors([
            'email' => "Too many login attempts. Please try again in {$seconds} seconds."
        ])->onlyInput('email');
    }

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        RateLimiter::clear($throttleKey);

        $user = Auth::user();

        // Check if account is active
        if ($user->status === 'inactive') {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact the administrator.'
            ]);
        }

        $request->session()->regenerate();

        // Two-factor users don't get a real session yet — stash the
        // pending user id and sign them back out. The real login only
        // completes once TwoFactorChallengeController verifies a code.
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('two_factor.login_id', $user->id);
            Auth::logout();
            return redirect('/two-factor-challenge');
        }

        return match(Auth::user()->role) {
            'admin'       => redirect('/admin/dashboard'),
            'staff'       => redirect('/staff/dashboard'),
            'coordinator' => redirect('/coordinator/dashboard'),
            'corporate'   => redirect('/client/dashboard'),
            default       => redirect('/customer/dashboard'),
        };
    }

    RateLimiter::hit($throttleKey, 60);

    return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
}

    public function showRegister() {
        return view('auth.register');
    }

    public function register(Request $request) {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role'     => 'required|in:corporate,individual',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        Auth::login($user);

        return match($user->role) {
            'corporate' => redirect('/client/dashboard'),
            default     => redirect('/customer/dashboard'),
        };
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    // ----------------------------------------------------------------
    // Forgot / Reset Password
    // ----------------------------------------------------------------

    public function showForgotPassword() {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request) {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', 'A password reset link has been sent to your email.')
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token) {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request) {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect('/login')->with('success', 'Password reset successfully. Please log in.')
            : back()->withErrors(['email' => [__($status)]]);
    }
}