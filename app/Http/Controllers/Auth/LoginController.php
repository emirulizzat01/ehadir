<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle user authentication.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Sila masukkan alamat emel.',
            'email.email' => 'Format emel tidak sah.',
            'password.required' => 'Sila masukkan kata laluan.',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password_hash)) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Maklumat log masuk tidak sepadan dengan rekod kami.',
            ]);
        }

        if (! $user->aktif) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Akaun anda telah dinyahaktifkan. Sila hubungi Pentadbir Sistem.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));

        $user->update(['last_login' => now()]);

        AuditLoggerService::log(
            tindakan: 'LOGIN',
            namaJadual: 'users',
            recordId: $user->id,
            dataLama: null,
            dataBaru: ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
            userId: $user->id
        );

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log out the current user.
     */
    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        if ($userId) {
            AuditLoggerService::log(
                tindakan: 'LOGOUT',
                namaJadual: 'users',
                recordId: $userId,
                dataLama: null,
                dataBaru: null,
                userId: $userId
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah berjaya log keluar.');
    }
}
