<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\IcsCalendarService;
use App\Services\ScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CalendarFeedController extends Controller
{
    public function __construct(
        protected IcsCalendarService $icsService,
        protected ScopeService $scopeService
    ) {}

    /**
     * Serve authenticated user's private ICS subscription feed for Google Calendar / Outlook.
     */
    public function feed(string $token): Response
    {
        // 1. Verify token format and find active user
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) {
            abort(404, 'Pautan langganan kalendar tidak sah.');
        }

        $user = User::where('calendar_feed_token', $token)
            ->where('aktif', true)
            ->first();

        if (! $user) {
            abort(404, 'Pautan langganan kalendar tidak sah atau telah dijana semula.');
        }

        // 2. Fetch scoped events for this user (past 60 days to future 365 days)
        $rangeStart = now()->subDays(60)->startOfDay();
        $rangeEnd = now()->addDays(365)->endOfDay();

        $query = CalendarEvent::with(['attendees.penempatan.bahagian', 'attendees.penempatan.unit', 'creator'])
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('mula', [$rangeStart, $rangeEnd])
                    ->orWhereBetween('tamat', [$rangeStart, $rangeEnd]);
            });

        // If user is not superadmin, scope events to their authority or participation
        if (! $user->isSuperAdmin()) {
            $scopedBahagianIds = $user->getScopedBahagianIds();
            $scopedUnitIds = $user->getScopedUnitIds();

            $query->where(function ($sq) use ($user, $scopedBahagianIds, $scopedUnitIds) {
                // Events created by this user
                $sq->where('created_by', $user->id)
                    // Events where user is an attendee (matched by email)
                    ->orWhereHas('attendees', function ($aq) use ($user) {
                        $aq->where('email', $user->email);
                    });

                // Events belonging to departments in user's scoped authority
                if (! empty($scopedBahagianIds) || ! empty($scopedUnitIds)) {
                    $sq->orWhereHas('attendees.penempatan', function ($pq) use ($scopedBahagianIds, $scopedUnitIds) {
                        if (! empty($scopedBahagianIds)) {
                            $pq->whereIn('bahagian_id', $scopedBahagianIds);
                        }
                        if (! empty($scopedUnitIds)) {
                            $pq->orWhereIn('unit_id', $scopedUnitIds);
                        }
                    });
                }
            });
        }

        $events = $query->orderBy('mula')->get();

        // 3. Generate RFC 5545 string
        $calendarName = 'eHadir - '.$user->nama;
        $icsContent = $this->icsService->generateFeed($events, $calendarName);

        return response($icsContent, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="ehadir-calendar.ics"',
            'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Published-TTL' => 'PT1H', // Advise calendar clients to poll hourly
        ]);
    }

    /**
     * Regenerate user's calendar feed token to revoke previous subscription links.
     */
    public function regenerate(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $user->regenerateCalendarFeedToken();

        return back()->with('success', 'Pautan langganan kalendar baharu berjaya dijana. Pautan terdahulu kini tidak lagi aktif.');
    }

    /**
     * Export a single event as a downloadable .ics file.
     */
    public function exportEvent(CalendarEvent $calendar): Response
    {
        $calendar->load(['attendees', 'creator']);
        $icsContent = $this->icsService->generateFeed(collect([$calendar]), $calendar->tajuk);

        $safeTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $calendar->tajuk);

        return response($icsContent, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="ehadir_'.$calendar->id.'_'.$safeTitle.'.ics"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
