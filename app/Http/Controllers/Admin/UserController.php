<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Bahagian;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserScope;
use App\Services\AuditLoggerService;
use App\Traits\HasDataTableQueries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    use HasDataTableQueries;

    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin();

        $query = User::with(['roles', 'scopes.bahagian', 'scopes.unit', 'anggota']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sortableColumns = [
            'nama' => 'nama',
            'email' => 'email',
            'last_login' => 'last_login',
            'aktif' => 'aktif',
        ];

        $users = $this->applyDataTable(
            $query,
            $request,
            $sortableColumns,
            defaultSort: 'nama',
            defaultDirection: 'asc',
            defaultPerPage: 15
        );

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorizeSuperAdmin();

        $roles = Role::orderBy('nama')->get();
        $bahagianList = Bahagian::where('aktif', true)->orderBy('nama')->get();
        $unitList = Unit::where('aktif', true)->orderBy('nama')->get();
        $anggotaList = Anggota::where('aktif', true)->orderBy('nama_penuh')->get();

        return view('admin.users.create', compact('roles', 'bahagianList', 'unitList', 'anggotaList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        // Option C: Auto-derive nama from linked Anggota if left blank
        if ($request->filled('anggota_id') && ! $request->filled('nama')) {
            $anggota = Anggota::find($request->input('anggota_id'));
            if ($anggota) {
                $request->merge(['nama' => $anggota->nama_penuh]);
                if (! $request->filled('email') && $anggota->emel) {
                    $request->merge(['email' => $anggota->emel]);
                }
            }
        }

        $validated = $request->validate([
            'anggota_id' => ['nullable', 'exists:anggota,id', 'unique:users,anggota_id'],
            'nama' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:200', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'aktif' => ['boolean'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['exists:roles,id'],
            'scopes_bahagian' => ['nullable', 'array'],
            'scopes_bahagian.*' => ['exists:bahagian,id'],
            'scopes_unit' => ['nullable', 'array'],
            'scopes_unit.*' => ['exists:unit,id'],
        ], [
            'nama.required' => 'Nama pengguna wajib diisi.',
            'email.unique' => 'Alamat emel ini telah didaftarkan.',
            'password.min' => 'Kata laluan sekurang-kurangnya 8 aksara.',
            'role_ids.required' => 'Sila pilih sekurang-kurangnya satu peranan.',
            'anggota_id.unique' => 'Anggota ini telah dipautkan kepada akaun pengguna lain.',
        ]);

        $user = DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'anggota_id' => $validated['anggota_id'] ?? null,
                'nama' => $validated['nama'],
                'email' => $validated['email'],
                'password_hash' => Hash::make($validated['password']),
                'aktif' => $request->boolean('aktif', true),
            ]);

            // Sync roles
            $user->roles()->sync($validated['role_ids']);

            // Save scopes
            if (! empty($validated['scopes_bahagian'])) {
                foreach ($validated['scopes_bahagian'] as $bId) {
                    UserScope::create([
                        'user_id' => $user->id,
                        'bahagian_id' => $bId,
                        'unit_id' => null,
                    ]);
                }
            }

            if (! empty($validated['scopes_unit'])) {
                foreach ($validated['scopes_unit'] as $uId) {
                    UserScope::create([
                        'user_id' => $user->id,
                        'bahagian_id' => null,
                        'unit_id' => $uId,
                    ]);
                }
            }

            AuditLoggerService::log(
                tindakan: 'CREATE',
                namaJadual: 'users',
                recordId: $user->id,
                dataLama: null,
                dataBaru: ['nama' => $user->nama, 'email' => $user->email, 'roles' => $validated['role_ids'], 'anggota_id' => $user->anggota_id]
            );

            return $user;
        });

        return redirect()->route('users.show', $user)
            ->with('success', "Pengguna '{$user->nama}' berjaya ditambah.");
    }

    public function show(User $user): View
    {
        $this->authorizeSuperAdmin();

        $user->load([
            'roles.permissions',
            'scopes.bahagian',
            'scopes.unit',
            'anggota.penempatan.bahagian',
            'anggota.penempatan.unit',
        ]);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $this->authorizeSuperAdmin();

        $roles = Role::orderBy('nama')->get();
        $bahagianList = Bahagian::where('aktif', true)->orderBy('nama')->get();
        $unitList = Unit::where('aktif', true)->orderBy('nama')->get();
        $anggotaList = Anggota::where('aktif', true)->orderBy('nama_penuh')->get();

        $selectedRoleIds = $user->roles->pluck('id')->all();
        $selectedBahagianIds = $user->scopes->whereNotNull('bahagian_id')->pluck('bahagian_id')->all();
        $selectedUnitIds = $user->scopes->whereNotNull('unit_id')->pluck('unit_id')->all();

        return view('admin.users.edit', compact(
            'user',
            'roles',
            'bahagianList',
            'unitList',
            'anggotaList',
            'selectedRoleIds',
            'selectedBahagianIds',
            'selectedUnitIds'
        ));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        // Option C: Auto-derive nama from linked Anggota if left blank
        if ($request->filled('anggota_id') && ! $request->filled('nama')) {
            $anggota = Anggota::find($request->input('anggota_id'));
            if ($anggota) {
                $request->merge(['nama' => $anggota->nama_penuh]);
            }
        }

        $validated = $request->validate([
            'anggota_id' => ['nullable', 'exists:anggota,id', 'unique:users,anggota_id,'.$user->id],
            'nama' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:200', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'aktif' => ['boolean'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['exists:roles,id'],
            'scopes_bahagian' => ['nullable', 'array'],
            'scopes_bahagian.*' => ['exists:bahagian,id'],
            'scopes_unit' => ['nullable', 'array'],
            'scopes_unit.*' => ['exists:unit,id'],
        ], [
            'anggota_id.unique' => 'Anggota ini telah dipautkan kepada akaun pengguna lain.',
        ]);

        DB::transaction(function () use ($user, $validated, $request) {
            $oldData = $user->toArray();

            $updateData = [
                'anggota_id' => $validated['anggota_id'] ?? null,
                'nama' => $validated['nama'],
                'email' => $validated['email'],
                'aktif' => $request->boolean('aktif', true),
            ];

            if (! empty($validated['password'])) {
                $updateData['password_hash'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            // Sync roles
            $user->roles()->sync($validated['role_ids']);

            // Re-sync scopes
            $user->scopes()->delete();

            if (! empty($validated['scopes_bahagian'])) {
                foreach ($validated['scopes_bahagian'] as $bId) {
                    UserScope::create([
                        'user_id' => $user->id,
                        'bahagian_id' => $bId,
                        'unit_id' => null,
                    ]);
                }
            }

            if (! empty($validated['scopes_unit'])) {
                foreach ($validated['scopes_unit'] as $uId) {
                    UserScope::create([
                        'user_id' => $user->id,
                        'bahagian_id' => null,
                        'unit_id' => $uId,
                    ]);
                }
            }

            AuditLoggerService::log(
                tindakan: 'UPDATE',
                namaJadual: 'users',
                recordId: $user->id,
                dataLama: $oldData,
                dataBaru: $user->fresh()->toArray()
            );
        });

        return redirect()->route('users.show', $user)
            ->with('success', "Maklumat pengguna '{$user->nama}' berjaya dikemaskini.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak boleh memadam akaun anda sendiri.');
        }

        DB::transaction(function () use ($user) {
            $oldData = $user->toArray();
            $user->scopes()->delete();
            $user->roles()->detach();
            $user->delete();

            AuditLoggerService::log(
                tindakan: 'DELETE',
                namaJadual: 'users',
                recordId: $user->id,
                dataLama: $oldData,
                dataBaru: null
            );
        });

        return redirect()->route('users.index')
            ->with('success', 'Pengguna berjaya dipadam.');
    }

    protected function authorizeSuperAdmin(): void
    {
        if (! Auth::user()?->isSuperAdmin()) {
            abort(403, 'Hanya Superadmin dibenarkan mengakses modul ini.');
        }
    }
}
