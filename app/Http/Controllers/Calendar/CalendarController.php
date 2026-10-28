<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\CalendarEvent;
use App\Services\CalendarEventService;
use App\Services\ScopeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(
        protected CalendarEventService $calendarService,
        protected ScopeService $scopeService
    ) {}

    public function index(Request $request): View
    {
        $this->authorizePermission('calendar.view');

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->startOfDay();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        $events = CalendarEvent::with(['attendees', 'creator'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('mula', [$startDate, $endDate])
                    ->orWhereBetween('tamat', [$startDate, $endDate]);
            })
            ->orderBy('mula')
            ->get();

        return view('calendar.index', compact('events', 'year', 'month'));
    }

    public function create(Request $request): View
    {
        $this->authorizePermission('calendar.manage');
        $user = Auth::user();
        $today = now()->toDateString();

        $anggotaQuery = Anggota::where('aktif', true);
        $this->scopeService->scopeAnggota($anggotaQuery, $user, $today);
        $availableAttendees = $anggotaQuery->orderBy('nama_penuh')->get();

        $defaultDate = $request->input('date', $today);
        $defaultTime = $request->input('time', '09:00');

        return view('calendar.create', compact('availableAttendees', 'defaultDate', 'defaultTime'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('calendar.manage');

        $validated = $request->validate([
            'tajuk' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:MESYUARAT,BENGKEL,KURSUS,LAWATAN_TAPAK,LAIN_LAIN'],
            'mula' => ['required', 'date'],
            'tamat' => ['required', 'date', 'after:mula'],
            'sepanjang_hari' => ['boolean'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'attendees' => ['required', 'array', 'min:1'],
            'attendees.*' => ['exists:anggota,id'],
        ], [
            'tajuk.required' => 'Tajuk program / aktiviti wajib diisi.',
            'tamat.after' => 'Masa tamat mesti selepas masa mula.',
            'attendees.required' => 'Sila pilih sekurang-kurangnya seorang anggota yang terlibat.',
        ]);

        $validated['sepanjang_hari'] = $request->boolean('sepanjang_hari', false);

        $event = $this->calendarService->createEvent(
            $validated,
            $validated['attendees'],
            Auth::user()
        );

        return redirect()->route('calendar.show', $event)
            ->with('success', "Aktiviti '{$event->tajuk}' berjaya dijadualkan.");
    }

    public function show(CalendarEvent $calendar): View
    {
        $this->authorizePermission('calendar.view');

        $calendar->load(['attendees.penempatan.bahagian', 'attendees.penempatan.unit', 'creator', 'updater']);

        return view('calendar.show', ['event' => $calendar]);
    }

    public function edit(CalendarEvent $calendar): View
    {
        $this->authorizePermission('calendar.manage');
        $user = Auth::user();
        $today = $calendar->mula->toDateString();

        $anggotaQuery = Anggota::where('aktif', true);
        $this->scopeService->scopeAnggota($anggotaQuery, $user, $today);
        $availableAttendees = $anggotaQuery->orderBy('nama_penuh')->get();

        $calendar->load('attendees');
        $selectedAttendeeIds = $calendar->attendees->pluck('id')->all();

        return view('calendar.edit', [
            'event' => $calendar,
            'availableAttendees' => $availableAttendees,
            'selectedAttendeeIds' => $selectedAttendeeIds,
        ]);
    }

    public function update(Request $request, CalendarEvent $calendar): RedirectResponse
    {
        $this->authorizePermission('calendar.manage');

        $validated = $request->validate([
            'tajuk' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:MESYUARAT,BENGKEL,KURSUS,LAWATAN_TAPAK,LAIN_LAIN'],
            'mula' => ['required', 'date'],
            'tamat' => ['required', 'date', 'after:mula'],
            'sepanjang_hari' => ['boolean'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'attendees' => ['required', 'array', 'min:1'],
            'attendees.*' => ['exists:anggota,id'],
        ]);

        $validated['sepanjang_hari'] = $request->boolean('sepanjang_hari', false);

        $event = $this->calendarService->updateEvent(
            $calendar,
            $validated,
            $validated['attendees'],
            Auth::user()
        );

        return redirect()->route('calendar.show', $event)
            ->with('success', 'Aktiviti kalendar berjaya dikemaskini.');
    }

    public function destroy(CalendarEvent $calendar): RedirectResponse
    {
        $this->authorizePermission('calendar.manage');

        $this->calendarService->deleteEvent($calendar, Auth::user());

        return redirect()->route('calendar.index')
            ->with('success', 'Aktiviti kalendar berjaya dipadam.');
    }

    protected function authorizePermission(string $perm): void
    {
        if (! Auth::user()?->hasPermission($perm)) {
            abort(403, 'Anda tidak mempunyai kebenaran untuk mengakses fungsi ini.');
        }
    }
}
