<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Traits\HasDataTableQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuditController extends Controller
{
    use HasDataTableQueries;

    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin();

        $query = AuditLog::with('user');

        if ($jadual = $request->input('nama_jadual')) {
            $query->where('nama_jadual', $jadual);
        }

        if ($tindakan = $request->input('tindakan')) {
            $query->where('tindakan', $tindakan);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_jadual', 'like', "%{$search}%")
                    ->orWhere('tindakan', 'like', "%{$search}%")
                    ->orWhere('record_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('nama', 'like', "%{$search}%");
                    });
            });
        }

        if ($date = $request->input('tarikh')) {
            $query->whereDate('created_at', $date);
        }

        $sortableColumns = [
            'masa' => 'created_at',
            'tindakan' => 'tindakan',
            'jadual' => 'nama_jadual',
            'record_id' => 'record_id',
        ];

        $logs = $this->applyDataTable(
            $query,
            $request,
            $sortableColumns,
            defaultSort: 'masa',
            defaultDirection: 'desc',
            defaultPerPage: 20
        );

        $tableOptions = AuditLog::select('nama_jadual')->distinct()->pluck('nama_jadual');
        $actionOptions = AuditLog::select('tindakan')->distinct()->pluck('tindakan');
        $userOptions = User::orderBy('nama')->get();

        return view('admin.audit.index', compact('logs', 'tableOptions', 'actionOptions', 'userOptions'));
    }

    public function show(AuditLog $audit): View
    {
        $this->authorizeSuperAdmin();

        $audit->load('user');

        return view('admin.audit.show', ['log' => $audit]);
    }

    protected function authorizeSuperAdmin(): void
    {
        if (! Auth::user()?->isSuperAdmin()) {
            abort(403, 'Hanya Superadmin dibenarkan mengakses audit trail.');
        }
    }
}
