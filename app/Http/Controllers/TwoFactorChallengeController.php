<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request) {
        if (!$request->session()->has('two_factor.login_id')) {
            return redirect('/login');
        }
        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request) {
        $userId = $request->session()->get('two_factor.login_id');

        if (!$userId) {
            return redirect('/login')->withErrors(['code' => 'Your session expired. Please log in again.']);
        }

        $user = User::find($userId);

        if (!$user || !$user->hasTwoFactorEnabled()) {
            $request->session()->forget('two_factor.login_id');
            return redirect('/login');
        }

        // Throttle by user + IP so a stolen session cookie alone can't
        // be brute-forced against the 6-digit code.
        $throttleKey = 'two-factor:' . $userId . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors(['code' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $request->validate(['code' => 'required|string']);

        $code     = trim($request->code);
        $verified = false;

        // Recovery codes are 9 characters (XXXX-XXXX) — longer than a
        // 6-digit TOTP code, which is enough to distinguish the two
        // without asking the user to pick a mode explicitly.
        if (strlen($code) > 6) {
            $recoveryCodes = $user->two_factor_recovery_codes ?? [];

            if (in_array($code, $recoveryCodes, true)) {
                $verified = true;
                // One-time use — remove it once spent.
                $remaining = array_values(array_diff($recoveryCodes, [$code]));
                $user->forceFill(['two_factor_recovery_codes' => $remaining])->save();
            }
        } else {
            $google2fa = new Google2FA();
            $verified  = $google2fa->verifyKey($user->two_factor_secret, $code);
        }

        if (!$verified) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->forget('two_factor.login_id');

        Auth::login($user);
        $request->session()->regenerate();

        return match($user->role) {
            'admin'       => redirect('/admin/dashboard'),
            'staff'       => redirect('/staff/dashboard'),
            'coordinator' => redirect('/coordinator/dashboard'),
            'corporate'   => redirect('/client/dashboard'),
            default       => redirect('/customer/dashboard'),
        };
    }
}