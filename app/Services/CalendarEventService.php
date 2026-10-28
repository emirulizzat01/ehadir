<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalendarEventService
{
    public function __construct(
        protected ScopeService $scopeService
    ) {}

    /**
     * Create a calendar event and sync attendees within the actor's authorized scope.
     */
    public function createEvent(array $data, array $attendeeIds, User $actor): CalendarEvent
    {
        return DB::transaction(function () use ($data, $attendeeIds, $actor) {
            $mula = Carbon::parse($data['mula']);
            $tamat = Carbon::parse($data['tamat']);

            if ($tamat <= $mula) {
                throw ValidationException::withMessages([
                    'tamat' => 'Tarikh dan masa tamat mesti selepas masa mula.',
                ]);
            }

            // Invariant 4: Validate each attendee is within actor's scope on event start date
            $eventDate = $mula->toDateString();
            foreach ($attendeeIds as $anggotaId) {
                if (! $this->scopeService->canAccessAnggotaAtDate($actor, $anggotaId, $eventDate)) {
                    throw ValidationException::withMessages([
                        'attendees' => "Anggota (ID: {$anggotaId}) berada di luar skop pentadbiran anda.",
                    ]);
                }
            }

            $event = CalendarEvent::create([
                'tajuk' => $data['tajuk'],
                'jenis' => $data['jenis'] ?? 'LAIN_LAIN',
                'mula' => $mula,
                'tamat' => $tamat,
                'sepanjang_hari' => $data['sepanjang_hari'] ?? false,
                'lokasi' => $data['lokasi'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $event->attendees()->sync($attendeeIds);

            AuditLoggerService::log(
                tindakan: 'CREATE',
                namaJadual: 'calendar_events',
                recordId: $event->id,
                dataLama: null,
                dataBaru: array_merge($event->toArray(), ['attendees' => $attendeeIds]),
                userId: $actor->id
            );

            return $event;
        });
    }

    /**
     * Update an event and its attendees.
     */
    public function updateEvent(CalendarEvent $event, array $data, array $attendeeIds, User $actor): CalendarEvent
    {
        return DB::transaction(function () use ($event, $data, $attendeeIds, $actor) {
            $mula = Carbon::parse($data['mula']);
            $tamat = Carbon::parse($data['tamat']);

            if ($tamat <= $mula) {
                throw ValidationException::withMessages([
                    'tamat' => 'Tarikh dan masa tamat mesti selepas masa mula.',
                ]);
            }

            $eventDate = $mula->toDateString();
            foreach ($attendeeIds as $anggotaId) {
                if (! $this->scopeService->canAccessAnggotaAtDate($actor, $anggotaId, $eventDate)) {
                    throw ValidationException::withMessages([
                        'attendees' => "Anggota (ID: {$anggotaId}) berada di luar skop pentadbiran anda.",
                    ]);
                }
            }

            $oldData = array_merge($event->toArray(), ['attendees' => $event->attendees()->pluck('anggota.id')->all()]);

            $event->update([
                'tajuk' => $data['tajuk'],
                'jenis' => $data['jenis'] ?? 'LAIN_LAIN',
                'mula' => $mula,
                'tamat' => $tamat,
                'sepanjang_hari' => $data['sepanjang_hari'] ?? false,
                'lokasi' => $data['lokasi'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'updated_by' => $actor->id,
            ]);

            $event->attendees()->sync($attendeeIds);

            AuditLoggerService::log(
                tindakan: 'UPDATE',
                namaJadual: 'calendar_events',
                recordId: $event->id,
                dataLama: $oldData,
                dataBaru: array_merge($event->fresh()->toArray(), ['attendees' => $attendeeIds]),
                userId: $actor->id
            );

            return $event;
        });
    }

    /**
     * Delete an event with audit log.
     */
    public function deleteEvent(CalendarEvent $event, User $actor): void
    {
        DB::transaction(function () use ($event, $actor) {
            $oldData = $event->toArray();
            $event->delete();

            AuditLoggerService::log(
                tindakan: 'DELETE',
                namaJadual: 'calendar_events',
                recordId: $event->id,
                dataLama: $oldData,
                dataBaru: null,
                userId: $actor->id
            );
        });
    }
}
