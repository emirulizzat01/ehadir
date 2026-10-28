<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Calendar\CalendarFeedController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Organization\BahagianController;
use App\Http\Controllers\Organization\StatusKehadiranController;
use App\Http\Controllers\Organization\UnitController;
use App\Http\Controllers\Personnel\AnggotaController;
use App\Http\Controllers\Personnel\PenempatanController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\TvDisplayController;
use Illuminate\Support\Facades\Route;

// Public Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Public Tokenized iCalendar Feed (Tier 2 Zero-Cost Sync for Google Calendar & Outlook)
Route::get('/calendar/feed/{token}.ics', [CalendarFeedController::class, 'feed'])->name('calendar.feed');

// Public Tokenized Kiosk TV Display (for unmanned TVs / Raspberry Pi)
Route::get('/tv/kiosk/{token}', [TvDisplayController::class, 'kiosk'])->name('tv.kiosk');

// Protected Application Routes
Route::middleware('auth')->group(function () {
    // TV Display Dashboard (Fullscreen Command Center)
    Route::get('/tv', [TvDisplayController::class, 'index'])->name('tv.index');

    // Dashboard (Priority 9 & 7)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Async Lookup Routes (High-volume dataset comboboxes)
    Route::get('lookup/anggota', [\App\Http\Controllers\LookupController::class, 'anggota'])->name('lookup.anggota');

    // Organization Module (Dedicated CRU)
    Route::prefix('organization')->name('organization.')->group(function () {
        Route::resource('bahagian', BahagianController::class);
        Route::resource('unit', UnitController::class);
        Route::resource('attendance-statuses', StatusKehadiranController::class)->except(['show', 'destroy']);
    });

    // Personnel Module (Dedicated CRU)
    Route::prefix('personnel')->name('personnel.')->group(function () {
        Route::post('anggota/sync-onestack', [AnggotaController::class, 'syncOneStack'])->name('anggota.sync-onestack');
        Route::post('anggota/{anggota}/refresh-onestack', [AnggotaController::class, 'refreshOneStack'])->name('anggota.refresh-onestack');
        Route::resource('anggota', AnggotaController::class)->parameters(['anggota' => 'anggota']);

        // Nested Penempatan routes for specific staff member
        Route::get('anggota/{anggota}/penempatan/create', [PenempatanController::class, 'create'])->name('penempatan.create');
        Route::post('anggota/{anggota}/penempatan', [PenempatanController::class, 'store'])->name('penempatan.store');
        Route::get('anggota/{anggota}/penempatan/{penempatan}/edit', [PenempatanController::class, 'edit'])->name('penempatan.edit');
        Route::put('anggota/{anggota}/penempatan/{penempatan}', [PenempatanController::class, 'update'])->name('penempatan.update');
        Route::delete('anggota/{anggota}/penempatan/{penempatan}', [PenempatanController::class, 'destroy'])->name('penempatan.destroy');
    });

    // Daily Attendance Module (Dedicated CRU)
    Route::get('attendance/whatsapp-summary', [AttendanceController::class, 'whatsappSummary'])->name('attendance.whatsapp-summary');
    Route::post('attendance/prime-defaults', [AttendanceController::class, 'primeDefaults'])->name('attendance.prime-defaults');
    Route::post('attendance/batch', [AttendanceController::class, 'batch'])->name('attendance.batch');
    Route::resource('attendance', AttendanceController::class);

    // Calendar Module (Dedicated CRU & Tier 2 Tokenized ICS Feed)
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('calendar/events/create', [CalendarController::class, 'create'])->name('calendar.events.create');
    Route::post('calendar/events', [CalendarController::class, 'store'])->name('calendar.events.store');
    Route::get('calendar/events/{calendar}', [CalendarController::class, 'show'])->name('calendar.show');
    Route::get('calendar/events/{calendar}/edit', [CalendarController::class, 'edit'])->name('calendar.events.edit');
    Route::put('calendar/events/{calendar}', [CalendarController::class, 'update'])->name('calendar.events.update');
    Route::delete('calendar/events/{calendar}', [CalendarController::class, 'destroy'])->name('calendar.events.destroy');
    Route::post('calendar/feed/regenerate', [CalendarFeedController::class, 'regenerate'])->name('calendar.feed.regenerate');
    Route::get('calendar/events/{calendar}/export-ics', [CalendarFeedController::class, 'exportEvent'])->name('calendar.events.export-ics');

    // Reports Module
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('daily', [ReportController::class, 'daily'])->name('daily');
        Route::get('daily/print', [ReportController::class, 'dailyPrint'])->name('daily.print');
        Route::get('monthly-matrix', [ReportController::class, 'monthly'])->name('monthly');
        Route::get('monthly-matrix/print', [ReportController::class, 'monthlyPrint'])->name('monthly.print');
        Route::get('export', [ReportController::class, 'export'])->name('export');
    });

    // System Administration Module (Superadmin only)
    Route::resource('users', UserController::class);
    Route::get('audit-logs', [AuditController::class, 'index'])->name('audit.index');
    Route::get('audit-logs/{audit}', [AuditController::class, 'show'])->name('audit.show');
});
