<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\AuditLoggerService;
use App\Traits\HasDataTableQueries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UnitController extends Controller
{
    use HasDataTableQueries;

    public function index(Request $request): View
    {
        $this->authorizePermission('org.view');

        $query = Unit::withCount(['penempatan as aktif_anggota_count' => function ($q) {
            $q->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', now()->toDateString());
        }]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kod', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $sortableColumns = [
            'kod' => 'kod',
            'nama' => 'nama',
            'bil_anggota' => 'aktif_anggota_count',
            'aktif' => 'aktif',
        ];

        $unitList = $this->applyDataTable(
            $query,
            $request,
            $sortableColumns,
            defaultSort: 'nama',
            defaultDirection: 'asc',
            defaultPerPage: 15
        );

        return view('organization.unit.index', compact('unitList'));
    }

    public function create(): View
    {
        $this->authorizePermission('org.manage');

        return view('organization.unit.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('org.manage');

        $validated = $request->validate([
            'kod' => ['required', 'string', 'max:40', 'unique:unit,kod'],
            'nama' => ['required', 'string', 'max:200'],
            'aktif' => ['boolean'],
        ], [
            'kod.required' => 'Kod unit wajib diisi.',
            'kod.unique' => 'Kod unit ini telah wujud dalam sistem.',
            'nama.required' => 'Nama unit wajib diisi.',
        ]);

        $validated['aktif'] = $request->boolean('aktif', true);

        $unit = DB::transaction(function () use ($validated) {
            $item = Unit::create($validated);

            AuditLoggerService::log(
                tindakan: 'CREATE',
                namaJadual: 'unit',
                recordId: $item->id,
                dataLama: null,
                dataBaru: $item->toArray()
            );

            return $item;
        });

        return redirect()->route('organization.unit.show', $unit)
            ->with('success', "Unit '{$unit->nama}' berjaya ditambah.");
    }

    public function show(Unit $unit): View
    {
        $this->authorizePermission('org.view');

        $today = now()->toDateString();
        $activePlacements = $unit->penempatan()
            ->with('anggota')
            ->where('tarikh_mula', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $today);
            })
            ->get();

        return view('organization.unit.show', compact('unit', 'activePlacements'));
    }

    public function edit(Unit $unit): View
    {
        $this->authorizePermission('org.manage');

        return view('organization.unit.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $this->authorizePermission('org.manage');

        $validated = $request->validate([
            'kod' => ['required', 'string', 'max:40', 'unique:unit,kod,'.$unit->id],
            'nama' => ['required', 'string', 'max:200'],
            'aktif' => ['boolean'],
        ]);

        $validated['aktif'] = $request->boolean('aktif', true);

        DB::transaction(function () use ($unit, $validated) {
            $oldData = $unit->toArray();
            $unit->update($validated);

            AuditLoggerService::log(
                tindakan: 'UPDATE',
                namaJadual: 'unit',
                recordId: $unit->id,
                dataLama: $oldData,
                dataBaru: $unit->fresh()->toArray()
            );
        });

        return redirect()->route('organization.unit.show', $unit)
            ->with('success', "Maklumat unit '{$unit->nama}' berjaya dikemaskini.");
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorizePermission('org.manage');

        if ($unit->penempatan()->exists()) {
            return back()->with('error', "Unit '{$unit->nama}' tidak boleh dipadam kerana mempunyai rekod penempatan anggota.");
        }

        DB::transaction(function () use ($unit) {
            $oldData = $unit->toArray();
            $unit->delete();

            AuditLoggerService::log(
                tindakan: 'DELETE',
                namaJadual: 'unit',
                recordId: $unit->id,
                dataLama: $oldData,
                dataBaru: null
            );
        });

        return redirect()->route('organization.unit.index')
            ->with('success', 'Unit berjaya dipadam.');
    }

    protected function authorizePermission(string $perm): void
    {
        if (! Auth::user()?->hasPermission($perm)) {
            abort(403, 'Anda tidak mempunyai kebenaran untuk mengakses fungsi ini.');
        }
    }
}
