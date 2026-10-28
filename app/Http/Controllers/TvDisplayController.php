<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TvDisplayController extends Controller
{
    /**
     * Show real-time TV Command Center display for authenticated users.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        if (! $user?->hasPermission('attendance.view')) {
            abort(403, 'Anda tidak mempunyai kebenaran untuk mengakses paparan ini.');
        }

        $date = $request->query('date', now()->toDateString());
        $scopedBahagianIds = $user->getScopedBahagianIds();
        $isScoped = count($scopedBahagianIds) > 0 && ! $user->isSuperAdmin();

        $requestedMode = $request->query('mode');
        $requestedBahagian = $request->query('bahagian_id');

        if ($isScoped) {
            // Scoped admins are automatically locked to Department Mode for their assigned department
            $mode = 'department';
            $bahagianId = in_array((int) $requestedBahagian, $scopedBahagianIds)
                ? (int) $requestedBahagian
                : $scopedBahagianIds[0];
        } else {
            // Superadmins and unscoped admins can view any mode
            $mode = in_array($requestedMode, ['executive', 'department', 'lobby']) ? $requestedMode : 'executive';
            $bahagianId = $requestedBahagian ? (int) $requestedBahagian : null;
        }

        $isKiosk = false;

        return view('tv.index', compact('date', 'mode', 'bahagianId', 'isKiosk'));
    }

    /**
     * Show unattended TV Command Center display via kiosk security token.
     */
    public function kiosk(Request $request, string $token): View
    {
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) {
            abort(403, 'Akses token kiosk tidak sah.');
        }

        $user = User::where('calendar_feed_token', $token)
            ->where('aktif', true)
            ->first();

        if (! $user) {
            abort(403, 'Akses token kiosk tidak sah atau telah dibatalkan.');
        }

        $date = $request->query('date', now()->toDateString());
        $scopedBahagianIds = $user->getScopedBahagianIds();
        $isScoped = count($scopedBahagianIds) > 0 && ! $user->isSuperAdmin();

        $requestedMode = $request->query('mode');
        $requestedBahagian = $request->query('bahagian_id');

        if ($isScoped) {
            // Scoped kiosk token: default to department mode (or lobby if explicitly requested)
            $mode = $requestedMode === 'lobby' ? 'lobby' : 'department';
            $bahagianId = in_array((int) $requestedBahagian, $scopedBahagianIds)
                ? (int) $requestedBahagian
                : $scopedBahagianIds[0];
        } else {
            $mode = in_array($requestedMode, ['executive', 'department', 'lobby']) ? $requestedMode : 'executive';
            $bahagianId = $requestedBahagian ? (int) $requestedBahagian : null;
        }

        $isKiosk = true;

        return view('tv.index', compact('date', 'mode', 'bahagianId', 'isKiosk'));
    }
}
