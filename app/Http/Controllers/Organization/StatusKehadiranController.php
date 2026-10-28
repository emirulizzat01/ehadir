<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\StatusKehadiran;
use App\Services\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StatusKehadiranController extends Controller
{
    public function index(): View
    {
        $this->authorizeSuperAdmin();

        $statuses = StatusKehadiran::withCount('rekodKehadiran')
            ->orderBy('susunan')
            ->get();

        return view('organization.status.index', compact('statuses'));
    }

    public function create(): View
    {
        $this->authorizeSuperAdmin();

        return view('organization.status.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'kod' => ['required', 'string', 'max:40', 'unique:status_kehadiran,kod'],
            'nama' => ['required', 'string', 'max:100'],
            'warna' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'susunan' => ['required', 'integer', 'min:0'],
            'aktif' => ['boolean'],
        ], [
            'kod.required' => 'Kod status wajib diisi.',
            'kod.unique' => 'Kod status ini telah wujud.',
            'nama.required' => 'Nama status wajib diisi.',
            'warna.regex' => 'Format warna mestilah kod HEX 6 aksara (contoh: #16A34A).',
        ]);

        $validated['aktif'] = $request->boolean('aktif', true);

        $status = DB::transaction(function () use ($validated) {
            $item = StatusKehadiran::create($validated);

            AuditLoggerService::log(
                tindakan: 'CREATE',
                namaJadual: 'status_kehadiran',
                recordId: $item->id,
                dataLama: null,
                dataBaru: $item->toArray()
            );

            return $item;
        });

        return redirect()->route('organization.attendance-statuses.index')
            ->with('success', "Status kehadiran '{$status->nama}' berjaya ditambah.");
    }

    public function edit(StatusKehadiran $attendanceStatus): View
    {
        $this->authorizeSuperAdmin();

        return view('organization.status.edit', ['status' => $attendanceStatus]);
    }

    public function update(Request $request, StatusKehadiran $attendanceStatus): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'kod' => ['required', 'string', 'max:40', 'unique:status_kehadiran,kod,'.$attendanceStatus->id],
            'nama' => ['required', 'string', 'max:100'],
            'warna' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'susunan' => ['required', 'integer', 'min:0'],
            'aktif' => ['boolean'],
        ]);

        $validated['aktif'] = $request->boolean('aktif', true);

        DB::transaction(function () use ($attendanceStatus, $validated) {
            $oldData = $attendanceStatus->toArray();
            $attendanceStatus->update($validated);

            AuditLoggerService::log(
                tindakan: 'UPDATE',
                namaJadual: 'status_kehadiran',
                recordId: $attendanceStatus->id,
                dataLama: $oldData,
                dataBaru: $attendanceStatus->fresh()->toArray()
            );
        });

        return redirect()->route('organization.attendance-statuses.index')
            ->with('success', "Status kehadiran '{$attendanceStatus->nama}' berjaya dikemaskini.");
    }

    protected function authorizeSuperAdmin(): void
    {
        if (! Auth::user()?->isSuperAdmin()) {
            abort(403, 'Hanya Superadmin dibenarkan mengkonfigurasi status kehadiran.');
        }
    }
}
