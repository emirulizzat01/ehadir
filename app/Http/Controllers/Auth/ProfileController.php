<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the user profile form.
     */
    public function edit(): View
    {
        $user = Auth::user();
        $user->load(['roles', 'scopes.bahagian', 'scopes.unit']);

        return view('profile.edit', compact('user'));
    }

    /**
     * Update user name and email.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:200', 'unique:users,email,'.$user->id],
        ], [
            'nama.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Alamat emel wajib diisi.',
            'email.unique' => 'Alamat emel ini telah digunakan oleh akaun lain.',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $oldData = $user->only(['nama', 'email']);
            $user->update($validated);

            AuditLoggerService::log(
                tindakan: 'UPDATE_PROFILE',
                namaJadual: 'users',
                recordId: $user->id,
                dataLama: $oldData,
                dataBaru: $validated,
                userId: $user->id
            );
        });

        return redirect()->route('profile.edit')
            ->with('success', 'Maklumat profil peribadi berjaya dikemaskini.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ], [
            'current_password.required' => 'Sila masukkan kata laluan semasa anda.',
            'password.required' => 'Sila masukkan kata laluan baharu.',
            'password.min' => 'Kata laluan baharu mestilah sekurang-kurangnya 8 aksara.',
            'password.confirmed' => 'Pengesahan kata laluan baharu tidak sepadan.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password_hash)) {
            return back()->withErrors([
                'current_password' => 'Kata laluan semasa yang dimasukkan adalah salah.',
            ]);
        }

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'password_hash' => Hash::make($validated['password']),
            ]);

            AuditLoggerService::log(
                tindakan: 'UPDATE_PASSWORD',
                namaJadual: 'users',
                recordId: $user->id,
                dataLama: null,
                dataBaru: ['password_changed' => true],
                userId: $user->id
            );
        });

        return redirect()->route('profile.edit')
            ->with('success', 'Kata laluan anda berjaya dikemaskini.');
    }
}
