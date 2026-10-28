<?php

namespace App\Http\Controllers\Personnel;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\Bahagian;
use App\Models\Unit;
use App\Services\AuditLoggerService;
use App\Services\PenempatanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PenempatanController extends Controller
{
    public function __construct(
        protected PenempatanService $penempatanService
    ) {}

    public function create(Anggota $anggota): View
    {
        $this->authorizePermission('anggota.manage');

        $bahagianList = Bahagian::where('aktif', true)->orderBy('nama')->get();
        $unitList = Unit::where('aktif', true)->orderBy('nama')->get();
        $currentPlacement = $anggota->getPenempatanAtDate();

        return view('personnel.penempatan.create', compact('anggota', 'bahagianList', 'unitList', 'currentPlacement'));
    }

    public function store(Request $request, Anggota $anggota): RedirectResponse
    {
        $this->authorizePermission('anggota.manage');
        $user = Auth::user();

        $validated = $request->validate([
            'penempatan_type' => ['required', 'in:bahagian,unit'],
            'bahagian_id' => ['nullable', 'required_if:penempatan_type,bahagian', 'exists:bahagian,id'],
            'unit_id' => ['nullable', 'required_if:penempatan_type,unit', 'exists:unit,id'],
            'tarikh_mula' => ['required', 'date'],
            'tarikh_tamat' => ['nullable', 'date', 'after_or_equal:tarikh_mula'],
            'auto_transfer' => ['boolean'],
        ], [
            'bahagian_id.required_if' => 'Sila pilih bahagian.',
            'unit_id.required_if' => 'Sila pilih unit.',
            'tarikh_mula.required' => 'Tarikh mula penempatan wajib diisi.',
            'tarikh_tamat.after_or_equal' => 'Tarikh tamat mesti sama atau selepas tarikh mula.',
        ]);

        $placementData = [
            'anggota_id' => $anggota->id,
            'bahagian_id' => $validated['penempatan_type'] === 'bahagian' ? $validated['bahagian_id'] : null,
            'unit_id' => $validated['penempatan_type'] === 'unit' ? $validated['unit_id'] : null,
            'tarikh_mula' => $validated['tarikh_mula'],
            'tarikh_tamat' => $validated['tarikh_tamat'] ?? null,
        ];

        if ($request->boolean('auto_transfer')) {
            $this->penempatanService->transferAnggota($anggota->id, $placementData, $user->id);
        } else {
            $this->penempatanService->createPenempatan($placementData, $user->id);
        }

        return redirect()->route('personnel.anggota.show', $anggota)
            ->with('success', "Penempatan baharu bagi {$anggota->nama_penuh} berjaya direkodkan.");
    }

    public function edit(Anggota $anggota, AnggotaPenempatan $penempatan): View
    {
        $this->authorizePermission('anggota.manage');

        if ($penempatan->anggota_id !== $anggota->id) {
            abort(404);
        }

        $bahagianList = Bahagian::where('aktif', true)->orderBy('nama')->get();
        $unitList = Unit::where('aktif', true)->orderBy('nama')->get();

        return view('personnel.penempatan.edit', compact('anggota', 'penempatan', 'bahagianList', 'unitList'));
    }

    public function update(Request $request, Anggota $anggota, AnggotaPenempatan $penempatan): RedirectResponse
    {
        $this->authorizePermission('anggota.manage');

        if ($penempatan->anggota_id !== $anggota->id) {
            abort(404);
        }

        $validated = $request->validate([
            'penempatan_type' => ['required', 'in:bahagian,unit'],
            'bahagian_id' => ['nullable', 'required_if:penempatan_type,bahagian', 'exists:bahagian,id'],
            'unit_id' => ['nullable', 'required_if:penempatan_type,unit', 'exists:unit,id'],
            'tarikh_mula' => ['required', 'date'],
            'tarikh_tamat' => ['nullable', 'date', 'after_or_equal:tarikh_mula'],
        ]);

        $placementData = [
            'bahagian_id' => $validated['penempatan_type'] === 'bahagian' ? $validated['bahagian_id'] : null,
            'unit_id' => $validated['penempatan_type'] === 'unit' ? $validated['unit_id'] : null,
            'tarikh_mula' => $validated['tarikh_mula'],
            'tarikh_tamat' => $validated['tarikh_tamat'] ?? null,
        ];

        $this->penempatanService->updatePenempatan($penempatan, $placementData, Auth::id());

        return redirect()->route('personnel.anggota.show', $anggota)
            ->with('success', 'Rekod penempatan berjaya dikemaskini.');
    }

    public function destroy(Anggota $anggota, AnggotaPenempatan $penempatan): RedirectResponse
    {
        $this->authorizePermission('anggota.manage');

        if ($penempatan->anggota_id !== $anggota->id) {
            abort(404);
        }

        if ($penempatan->kehadiran()->exists()) {
            return back()->with('error', 'Penempatan ini tidak boleh dipadam kerana mempunyai rekod kehadiran yang terikat dengannya.');
        }

        DB::transaction(function () use ($penempatan) {
            $oldData = $penempatan->toArray();
            $penempatan->delete();

            AuditLoggerService::log(
                tindakan: 'DELETE',
                namaJadual: 'anggota_penempatan',
                recordId: $penempatan->id,
                dataLama: $oldData,
                dataBaru: null
            );
        });

        return redirect()->route('personnel.anggota.show', $anggota)
            ->with('success', 'Rekod penempatan berjaya dipadam.');
    }

    protected function authorizePermission(string $perm): void
    {
        if (! Auth::user()?->hasPermission($perm)) {
            abort(403, 'Anda tidak mempunyai kebenaran untuk mengakses fungsi ini.');
        }
    }
}
