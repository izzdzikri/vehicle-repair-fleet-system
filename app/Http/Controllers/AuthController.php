<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    if (Auth::attempt($credentials)) {
        // Check if account is active
        if (Auth::user()->status === 'inactive') {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact the administrator.'
            ]);
        }

        $request->session()->regenerate();
        return match(Auth::user()->role) {
            'admin'     => redirect('/admin/dashboard'),
            'staff'     => redirect('/staff/dashboard'),
            'corporate' => redirect('/client/dashboard'),
            default     => redirect('/customer/dashboard'),
        };
    }

    return back()->withErrors(['email' => 'Invalid credentials.']);
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
}