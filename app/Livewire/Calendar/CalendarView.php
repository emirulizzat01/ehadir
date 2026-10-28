<?php

namespace App\Livewire\Calendar;

use App\Models\Anggota;
use App\Models\Bahagian;
use App\Models\CalendarEvent;
use App\Services\ScopeService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CalendarView extends Component
{
    public string $view = 'month'; // 'month', 'week', 'day', 'agenda'

    public string $currentDate;    // e.g. '2026-10-27'

    public string $miniMonth;      // e.g. '2026-10-01'

    public array $selectedTypes = ['MESYUARAT', 'BENGKEL', 'KURSUS', 'LAWATAN_TAPAK', 'LAIN_LAIN'];

    public ?int $selectedBahagianId = null;

    public bool $onlyMyEvents = false;

    public string $search = '';

    public ?int $activeEventId = null;

    public bool $showSidebar = true;

    protected $queryString = [
        'view' => ['except' => 'month'],
        'currentDate' => ['as' => 'date'],
    ];

    public function mount(?string $initialDate = null, ?string $initialView = 'month'): void
    {
        $this->currentDate = $initialDate
            ? Carbon::parse($initialDate)->toDateString()
            : (request()->query('date') ?: now()->toDateString());

        $this->miniMonth = Carbon::parse($this->currentDate)->startOfMonth()->toDateString();

        $requestedView = request()->query('view') ?: $initialView;
        if (in_array($requestedView, ['month', 'week', 'day', 'agenda'])) {
            $this->view = $requestedView;
        }
    }

    public function setView(string $newView): void
    {
        if (in_array($newView, ['month', 'week', 'day', 'agenda'])) {
            $this->view = $newView;
        }
    }

    public function goToToday(): void
    {
        $this->currentDate = now()->toDateString();
        $this->miniMonth = now()->startOfMonth()->toDateString();
    }

    public function previousPeriod(): void
    {
        $curr = Carbon::parse($this->currentDate);
        $this->currentDate = match ($this->view) {
            'month' => $curr->subMonth()->toDateString(),
            'week' => $curr->subWeek()->toDateString(),
            'day' => $curr->subDay()->toDateString(),
            'agenda' => $curr->subMonth()->toDateString(),
            default => $curr->subMonth()->toDateString(),
        };

        $this->miniMonth = Carbon::parse($this->currentDate)->startOfMonth()->toDateString();
    }

    public function nextPeriod(): void
    {
        $curr = Carbon::parse($this->currentDate);
        $this->currentDate = match ($this->view) {
            'month' => $curr->addMonth()->toDateString(),
            'week' => $curr->addWeek()->toDateString(),
            'day' => $curr->addDay()->toDateString(),
            'agenda' => $curr->addMonth()->toDateString(),
            default => $curr->addMonth()->toDateString(),
        };

        $this->miniMonth = Carbon::parse($this->currentDate)->startOfMonth()->toDateString();
    }

    public function previousMiniMonth(): void
    {
        $this->miniMonth = Carbon::parse($this->miniMonth)->subMonth()->startOfMonth()->toDateString();
    }

    public function nextMiniMonth(): void
    {
        $this->miniMonth = Carbon::parse($this->miniMonth)->addMonth()->startOfMonth()->toDateString();
    }

    public function selectDate(string $date, ?string $switchView = null): void
    {
        $this->currentDate = Carbon::parse($date)->toDateString();
        $this->miniMonth = Carbon::parse($date)->startOfMonth()->toDateString();

        if ($switchView && in_array($switchView, ['month', 'week', 'day', 'agenda'])) {
            $this->view = $switchView;
        }
    }

    public function toggleType(string $type): void
    {
        if (in_array($type, $this->selectedTypes)) {
            $this->selectedTypes = array_values(array_diff($this->selectedTypes, [$type]));
        } else {
            $this->selectedTypes[] = $type;
        }
    }

    public function toggleAllTypes(): void
    {
        $all = ['MESYUARAT', 'BENGKEL', 'KURSUS', 'LAWATAN_TAPAK', 'LAIN_LAIN'];
        if (count($this->selectedTypes) === count($all)) {
            $this->selectedTypes = [];
        } else {
            $this->selectedTypes = $all;
        }
    }

    public function toggleOnlyMyEvents(): void
    {
        $this->onlyMyEvents = ! $this->onlyMyEvents;
    }

    public function toggleSidebar(): void
    {
        $this->showSidebar = ! $this->showSidebar;
    }

    public function openQuickView(int $eventId): void
    {
        $this->activeEventId = $eventId;
    }

    public function closeQuickView(): void
    {
        $this->activeEventId = null;
    }

    public function render(): View
    {
        $currCarbon = Carbon::parse($this->currentDate);
        $todayString = now()->toDateString();

        // 1. Base query with filters
        $baseQuery = CalendarEvent::with(['attendees.penempatan.bahagian', 'attendees.penempatan.unit', 'creator'])
            ->when(! empty($this->selectedTypes), function ($q) {
                $q->whereIn('jenis', $this->selectedTypes);
            }, function ($q) {
                $q->whereRaw('1 = 0');
            })
            ->when($this->search !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sq) use ($term) {
                    $sq->where('tajuk', 'like', $term)
                        ->orWhere('lokasi', 'like', $term)
                        ->orWhere('catatan', 'like', $term);
                });
            })
            ->when($this->selectedBahagianId, function ($q) {
                $q->whereHas('attendees.penempatan', function ($pq) {
                    $pq->where('bahagian_id', $this->selectedBahagianId);
                });
            })
            ->when($this->onlyMyEvents && Auth::check(), function ($q) {
                $user = Auth::user();
                $q->where(function ($sq) use ($user) {
                    $sq->where('created_by', $user->id)
                        ->orWhereHas('attendees', function ($aq) use ($user) {
                            $aq->where('email', $user->email);
                        });
                });
            });

        // 2. Format Period Title for Top Bar
        $periodTitle = match ($this->view) {
            'month' => $currCarbon->translatedFormat('F Y'),
            'week' => $currCarbon->copy()->startOfWeek(Carbon::MONDAY)->translatedFormat('d M').' – '.
                      $currCarbon->copy()->endOfWeek(Carbon::SUNDAY)->translatedFormat('d M Y'),
            'day' => $currCarbon->translatedFormat('l, d F Y'),
            'agenda' => 'Agenda '.$currCarbon->translatedFormat('F Y'),
            default => $currCarbon->translatedFormat('F Y'),
        };

        // 3. Mini Month Grid Calculation (for Sidebar)
        $miniCarbon = Carbon::parse($this->miniMonth);
        $miniStart = $miniCarbon->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $miniEnd = $miniCarbon->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $miniDatesWithEvents = CalendarEvent::whereBetween('mula', [$miniStart->copy()->startOfDay(), $miniEnd->copy()->endOfDay()])
            ->pluck('mula')
            ->map(fn ($d) => $d->toDateString())
            ->unique()
            ->flip()
            ->all();

        $miniWeeks = [];
        $mCursor = $miniStart->copy();
        while ($mCursor->lte($miniEnd)) {
            $mWeek = [];
            for ($i = 0; $i < 7; $i++) {
                $dStr = $mCursor->toDateString();
                $mWeek[] = [
                    'date' => $dStr,
                    'dayNumber' => $mCursor->day,
                    'isCurrentMonth' => $mCursor->month === $miniCarbon->month,
                    'isToday' => $dStr === $todayString,
                    'isSelected' => $dStr === $this->currentDate,
                    'hasEvents' => isset($miniDatesWithEvents[$dStr]),
                ];
                $mCursor->addDay();
            }
            $miniWeeks[] = $mWeek;
        }

        // 4. View-specific calculations
        $monthWeeks = [];
        $weekDays = [];
        $dayData = [];
        $agendaGroups = collect();

        if ($this->view === 'month') {
            $monthStart = $currCarbon->copy()->startOfMonth();
            $monthEnd = $currCarbon->copy()->endOfMonth();
            $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
            $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

            $events = (clone $baseQuery)->where(function ($q) use ($gridStart, $gridEnd) {
                $q->whereBetween('mula', [$gridStart->copy()->startOfDay(), $gridEnd->copy()->endOfDay()])
                    ->orWhereBetween('tamat', [$gridStart->copy()->startOfDay(), $gridEnd->copy()->endOfDay()])
                    ->orWhere(function ($sub) use ($gridStart, $gridEnd) {
                        $sub->where('mula', '<=', $gridStart)
                            ->where('tamat', '>=', $gridEnd);
                    });
            })->orderBy('mula')->get();

            $eventsByDate = [];
            foreach ($events as $ev) {
                $start = $ev->mula->copy()->startOfDay();
                $end = $ev->tamat->copy()->startOfDay();
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $key = $cursor->toDateString();
                    $eventsByDate[$key][] = $ev;
                    $cursor->addDay();
                }
            }

            $gCursor = $gridStart->copy();
            while ($gCursor->lte($gridEnd)) {
                $w = [];
                for ($i = 0; $i < 7; $i++) {
                    $dStr = $gCursor->toDateString();
                    $w[] = [
                        'date' => $dStr,
                        'dayNumber' => $gCursor->day,
                        'isCurrentMonth' => $gCursor->month === $currCarbon->month,
                        'isToday' => $dStr === $todayString,
                        'isSelected' => $dStr === $this->currentDate,
                        'events' => $eventsByDate[$dStr] ?? [],
                    ];
                    $gCursor->addDay();
                }
                $monthWeeks[] = $w;
            }
        } elseif ($this->view === 'week') {
            $weekStart = $currCarbon->copy()->startOfWeek(Carbon::MONDAY);
            $weekEnd = $currCarbon->copy()->endOfWeek(Carbon::SUNDAY);

            $weekEvents = (clone $baseQuery)->where(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('mula', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
                    ->orWhereBetween('tamat', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
                    ->orWhere(function ($sub) use ($weekStart, $weekEnd) {
                        $sub->where('mula', '<=', $weekStart)
                            ->where('tamat', '>=', $weekEnd);
                    });
            })->orderBy('mula')->get();

            $wCursor = $weekStart->copy();
            for ($i = 0; $i < 7; $i++) {
                $dStr = $wCursor->toDateString();
                $dayEvents = $weekEvents->filter(function ($ev) use ($dStr) {
                    return $ev->mula->toDateString() <= $dStr && $ev->tamat->toDateString() >= $dStr;
                });

                $weekDays[] = [
                    'carbon' => $wCursor->copy(),
                    'date' => $dStr,
                    'dayName' => $wCursor->translatedFormat('D'),
                    'dayNumber' => $wCursor->day,
                    'isToday' => $dStr === $todayString,
                    'isSelected' => $dStr === $this->currentDate,
                    'allDayEvents' => $dayEvents->filter(fn ($e) => $e->sepanjang_hari),
                    'timedEvents' => $dayEvents->filter(fn ($e) => ! $e->sepanjang_hari),
                ];
                $wCursor->addDay();
            }
        } elseif ($this->view === 'day') {
            $dayCarbon = $currCarbon->copy();
            $dStr = $dayCarbon->toDateString();

            $dayEvents = (clone $baseQuery)->where(function ($q) use ($dStr) {
                $q->whereDate('mula', '<=', $dStr)
                    ->whereDate('tamat', '>=', $dStr);
            })->orderBy('mula')->get();

            $dayData = [
                'carbon' => $dayCarbon,
                'date' => $dStr,
                'isToday' => $dStr === $todayString,
                'allDayEvents' => $dayEvents->filter(fn ($e) => $e->sepanjang_hari),
                'timedEvents' => $dayEvents->filter(fn ($e) => ! $e->sepanjang_hari),
            ];
        } elseif ($this->view === 'agenda') {
            $agendaStart = $currCarbon->copy()->startOfMonth();
            $agendaEnd = $currCarbon->copy()->endOfMonth();

            $agendaGroups = (clone $baseQuery)->where(function ($q) use ($agendaStart, $agendaEnd) {
                $q->whereBetween('mula', [$agendaStart->copy()->startOfDay(), $agendaEnd->copy()->endOfDay()])
                    ->orWhereBetween('tamat', [$agendaStart->copy()->startOfDay(), $agendaEnd->copy()->endOfDay()]);
            })->orderBy('mula')->get()->groupBy(fn ($e) => $e->mula->toDateString());
        }

        // Active event for popover modal
        $activeEvent = $this->activeEventId
            ? CalendarEvent::with(['attendees.penempatan.bahagian', 'attendees.penempatan.unit', 'creator', 'updater'])->find($this->activeEventId)
            : null;

        $bahagianList = Bahagian::where('aktif', true)->orderBy('nama')->get();

        $user = Auth::user();
        $availableAttendees = collect();
        if ($user) {
            $today = now()->toDateString();
            $anggotaQuery = Anggota::where('aktif', true);
            app(ScopeService::class)->scopeAnggota($anggotaQuery, $user, $today);
            $availableAttendees = $anggotaQuery->orderBy('nama_penuh')->get();
        }

        return view('livewire.calendar.calendar-view', [
            'periodTitle' => $periodTitle,
            'miniMonthTitle' => $miniCarbon->translatedFormat('F Y'),
            'miniWeeks' => $miniWeeks,
            'monthWeeks' => $monthWeeks,
            'weekDays' => $weekDays,
            'dayData' => $dayData,
            'agendaGroups' => $agendaGroups,
            'activeEvent' => $activeEvent,
            'bahagianList' => $bahagianList,
            'availableAttendees' => $availableAttendees,
        ]);
    }
}
