<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $demoUsers = [
            'head_office' => User::where('email', 'admin@gmail.com')->first() ?? User::where('role', 'head_office')->first(),
            'dhaka_operator' => User::where('email', 'dhaka.depot@aci-bd.com')->first(),
            'jashore_operator' => User::where('email', 'jashore.depot@aci-bd.com')->first(),
            'auditor' => User::where('role', 'auditor')->first(),
        ];

        return view('auth.login', compact('demoUsers'));
    }

    public function login(Request $request)
    {
        $loginInput = trim((string) ($request->input('login') ?? $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $request->merge(['login' => $loginInput]);

        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Please enter your Corporate Email or Employee ID.',
            'password.required' => 'Please enter your access password.',
        ]);

        // Showroom / Depot users can sign in with either their corporate email or employee ID
        $isEmail = str_contains($loginInput, '@');
        $identityField = $isEmail ? 'email' : 'employee_id';
        $identityValue = $isEmail ? strtolower($loginInput) : strtoupper($loginInput);

        if (Auth::attempt([$identityField => $identityValue, 'password' => $password], $request->boolean('remember'))
            || Auth::attempt([$identityField => $loginInput, 'password' => $password], $request->boolean('remember'))) {
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'login' => 'Invalid credentials. Please verify your Email or Employee ID and password.',
        ])->onlyInput('login');
    }

    public function switchRole(Request $request)
    {
        // Demo-only tool: strictly blocked outside local/demo environments so it
        // can never be abused in production to impersonate another account.
        abort_unless(app()->isLocal() || config('app.demo_switcher'), 403, 'Demo role switching is disabled.');

        $user = User::findOrFail($request->user_id);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', "Switched active session to: {$user->name} ({$user->roles->pluck('name')->first()})");
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
