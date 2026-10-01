<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorSettingsController extends Controller
{
    /**
     * Step 1: generate a new, unconfirmed secret. The QR/secret pair is
     * flashed to the session so it can be shown exactly once on the
     * next page load — it is never re-displayed on subsequent reloads,
     * so refreshing the profile page after this doesn't leak it again.
     */
    public function enable(Request $request) {
        $user = auth()->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Two-factor authentication is already enabled.');
        }

        $google2fa = new Google2FA();
        $secret    = $google2fa->generateSecretKey();

        $user->forceFill(['two_factor_secret' => $secret])->save();

        $otpauthUrl = $google2fa->getQRCodeUrl(
            config('app.name', 'Vehicle Repair System'),
            $user->email,
            $secret
        );

        return back()->with('two_factor_setup', [
            'secret'      => $secret,
            'otpauth_url' => $otpauthUrl,
        ]);
    }

    /**
     * Step 2: user proves the secret was scanned correctly by entering
     * a live code. On success, 2FA becomes fully enabled and one-time
     * recovery codes are issued.
     */
    public function confirm(Request $request) {
        $request->validate(['code' => 'required|string']);

        $user = auth()->user();

        if (!$user->two_factor_secret) {
            return back()->with('error', 'No pending two-factor setup found. Please start again.');
        }

        $google2fa = new Google2FA();

        if (!$google2fa->verifyKey($user->two_factor_secret, trim($request->code))) {
            return back()->with('error', 'Invalid code. Please try again.')->with('two_factor_setup', [
                'secret'      => $user->two_factor_secret,
                'otpauth_url' => $google2fa->getQRCodeUrl(
                    config('app.name', 'Vehicle Repair System'),
                    $user->email,
                    $user->two_factor_secret
                ),
            ]);
        }

        $recoveryCodes = collect(range(1, 8))
            ->map(fn() => Str::upper(Str::random(4) . '-' . Str::random(4)))
            ->all();

        $user->forceFill([
            'two_factor_confirmed_at'   => now(),
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        return back()
            ->with('two_factor_recovery_codes', $recoveryCodes)
            ->with('success', 'Two-factor authentication is now enabled.');
    }

    /**
     * Disable 2FA entirely. Requires the current password since this
     * weakens account security.
     */
    public function disable(Request $request) {
        $request->validate(['password' => 'required|string']);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Incorrect password.');
        }

        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();

        return back()->with('success', 'Two-factor authentication has been disabled.');
    }

    /**
     * Regenerate recovery codes, invalidating the previous set.
     * Requires the current password since it silently revokes old
     * codes the user may not realise they still need.
     */
    public function regenerateRecoveryCodes(Request $request) {
        $request->validate(['password' => 'required|string']);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Incorrect password.');
        }

        if (!$user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Two-factor authentication is not enabled.');
        }

        $recoveryCodes = collect(range(1, 8))
            ->map(fn() => Str::upper(Str::random(4) . '-' . Str::random(4)))
            ->all();

        $user->forceFill(['two_factor_recovery_codes' => $recoveryCodes])->save();

        return back()
            ->with('two_factor_recovery_codes', $recoveryCodes)
            ->with('success', 'New recovery codes generated. Your old codes no longer work.');
    }
}