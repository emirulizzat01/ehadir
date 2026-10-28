<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Services\ScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LookupController extends Controller
{
    public function __construct(
        protected ScopeService $scopeService
    ) {}

    /**
     * Search and retrieve Anggota options for async/searchable select dropdowns.
     */
    public function anggota(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['results' => []], 401);
        }

        $search = trim((string) $request->input('q', ''));
        $selectedId = $request->input('selected_id');
        $limit = min((int) $request->input('limit', 15), 50);
        $today = now()->toDateString();

        // 1. If only selected_id is requested (for initial component render in edit forms)
        if ($selectedId && empty($search)) {
            $selected = Anggota::with(['penempatan' => function ($q) use ($today) {
                $q->with(['bahagian', 'unit'])
                    ->where('tarikh_mula', '<=', $today)
                    ->where(function ($sq) use ($today) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $today);
                    });
            }])->find($selectedId);

            if ($selected) {
                return response()->json([
                    'results' => [
                        $this->formatAnggotaOption($selected),
                    ],
                ]);
            }

            return response()->json(['results' => []]);
        }

        // 2. Query active Anggota with scope enforcement (Invariant 3)
        $query = Anggota::query()->where('aktif', true);

        // For non-superadmin, enforce administrative scope
        if (! $user->isSuperAdmin()) {
            $this->scopeService->scopeAnggota($query, $user, $today);
        }

        // Apply search keyword
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_penuh', 'like', "%{$search}%")
                    ->orWhere('no_pekerja', 'like', "%{$search}%")
                    ->orWhere('jawatan', 'like', "%{$search}%")
                    ->orWhere('emel', 'like', "%{$search}%");
            });
        }

        // Eager load active placement
        $query->with(['penempatan' => function ($q) use ($today) {
            $q->with(['bahagian', 'unit'])
                ->where('tarikh_mula', '<=', $today)
                ->where(function ($sq) use ($today) {
                    $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $today);
                });
        }]);

        $anggotaList = $query->orderBy('nama_penuh')->limit($limit)->get();

        // If a selected_id is specified but not in the search results, prepend it
        if ($selectedId && ! $anggotaList->contains('id', (int) $selectedId)) {
            $selectedAnggota = Anggota::with(['penempatan' => function ($q) use ($today) {
                $q->with(['bahagian', 'unit'])
                    ->where('tarikh_mula', '<=', $today)
                    ->where(function ($sq) use ($today) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $today);
                    });
            }])->find($selectedId);

            if ($selectedAnggota) {
                $anggotaList->prepend($selectedAnggota);
            }
        }

        $results = $anggotaList->map(function ($a) {
            return $this->formatAnggotaOption($a);
        })->values()->all();

        return response()->json(['results' => $results]);
    }

    /**
     * Format Anggota model into a clean UI option structure.
     */
    protected function formatAnggotaOption(Anggota $anggota): array
    {
        $activePlacement = $anggota->penempatan->first();
        $placementName = $activePlacement ? $activePlacement->lokasi_penempatan : null;

        $subtitleParts = [];
        if ($anggota->jawatan) {
            $subtitleParts[] = $anggota->jawatan . ($anggota->gred ? ' (' . $anggota->gred . ')' : '');
        }
        if ($placementName) {
            $subtitleParts[] = $placementName;
        }

        return [
            'id' => $anggota->id,
            'title' => $anggota->nama_penuh_gelaran,
            'subtitle' => implode(' • ', $subtitleParts),
            'badge' => $anggota->no_pekerja ?: 'ID Tiada',
            'department' => $placementName,
            'gred' => $anggota->gred,
            'email' => $anggota->emel,
        ];
    }
}

