<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Bahagian;
use App\Services\AuditLoggerService;
use App\Traits\HasDataTableQueries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BahagianController extends Controller
{
    use HasDataTableQueries;

    public function index(Request $request): View
    {
        $this->authorizePermission('org.view');

        $query = Bahagian::withCount(['penempatan as aktif_anggota_count' => function ($q) {
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

        $bahagianList = $this->applyDataTable(
            $query,
            $request,
            $sortableColumns,
            defaultSort: 'nama',
            defaultDirection: 'asc',
            defaultPerPage: 15
        );

        return view('organization.bahagian.index', compact('bahagianList'));
    }

    public function create(): View
    {
        $this->authorizePermission('org.manage');

        return view('organization.bahagian.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('org.manage');

        $validated = $request->validate([
            'kod' => ['required', 'string', 'max:40', 'unique:bahagian,kod'],
            'nama' => ['required', 'string', 'max:200'],
            'aktif' => ['boolean'],
        ], [
            'kod.required' => 'Kod bahagian wajib diisi.',
            'kod.unique' => 'Kod bahagian ini telah wujud dalam sistem.',
            'nama.required' => 'Nama bahagian wajib diisi.',
        ]);

        $validated['aktif'] = $request->boolean('aktif', true);

        $bahagian = DB::transaction(function () use ($validated) {
            $item = Bahagian::create($validated);

            AuditLoggerService::log(
                tindakan: 'CREATE',
                namaJadual: 'bahagian',
                recordId: $item->id,
                dataLama: null,
                dataBaru: $item->toArray()
            );

            return $item;
        });

        return redirect()->route('organization.bahagian.show', $bahagian)
            ->with('success', "Bahagian '{$bahagian->nama}' berjaya ditambah.");
    }

    public function show(Bahagian $bahagian): View
    {
        $this->authorizePermission('org.view');

        $today = now()->toDateString();
        $activePlacements = $bahagian->penempatan()
            ->with('anggota')
            ->where('tarikh_mula', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $today);
            })
            ->get();

        return view('organization.bahagian.show', compact('bahagian', 'activePlacements'));
    }

    public function edit(Bahagian $bahagian): View
    {
        $this->authorizePermission('org.manage');

        return view('organization.bahagian.edit', compact('bahagian'));
    }

    public function update(Request $request, Bahagian $bahagian): RedirectResponse
    {
        $this->authorizePermission('org.manage');

        $validated = $request->validate([
            'kod' => ['required', 'string', 'max:40', 'unique:bahagian,kod,'.$bahagian->id],
            'nama' => ['required', 'string', 'max:200'],
            'aktif' => ['boolean'],
        ]);

        $validated['aktif'] = $request->boolean('aktif', true);

        DB::transaction(function () use ($bahagian, $validated) {
            $oldData = $bahagian->toArray();
            $bahagian->update($validated);

            AuditLoggerService::log(
                tindakan: 'UPDATE',
                namaJadual: 'bahagian',
                recordId: $bahagian->id,
                dataLama: $oldData,
                dataBaru: $bahagian->fresh()->toArray()
            );
        });

        return redirect()->route('organization.bahagian.show', $bahagian)
            ->with('success', "Maklumat bahagian '{$bahagian->nama}' berjaya dikemaskini.");
    }

    public function destroy(Bahagian $bahagian): RedirectResponse
    {
        $this->authorizePermission('org.manage');

        // Check if there are foreign placements
        if ($bahagian->penempatan()->exists()) {
            return back()->with('error', "Bahagian '{$bahagian->nama}' tidak boleh dipadam kerana mempunyai rekod penempatan anggota.");
        }

        DB::transaction(function () use ($bahagian) {
            $oldData = $bahagian->toArray();
            $bahagian->delete();

            AuditLoggerService::log(
                tindakan: 'DELETE',
                namaJadual: 'bahagian',
                recordId: $bahagian->id,
                dataLama: $oldData,
                dataBaru: null
            );
        });

        return redirect()->route('organization.bahagian.index')
            ->with('success', 'Bahagian berjaya dipadam.');
    }

    protected function authorizePermission(string $perm): void
    {
        if (! Auth::user()?->hasPermission($perm)) {
            abort(403, 'Anda tidak mempunyai kebenaran untuk mengakses fungsi ini.');
        }
    }
}
