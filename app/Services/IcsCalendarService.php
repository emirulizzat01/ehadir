<?php

namespace App\Services;

use App\Models\CalendarEvent;
use Illuminate\Support\Collection;

class IcsCalendarService
{
    /**
     * Generate RFC 5545 compliant iCalendar string for a collection of events.
     *
     * @param  Collection<int, CalendarEvent>|iterable  $events
     */
    public function generateFeed(iterable $events, string $calendarName = 'eHadir - Kalendar Aktiviti'): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//eHadir//Sistem Kehadiran Organisasi v5.0//MS',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escapeText($calendarName),
            'X-WR-TIMEZONE:Asia/Kuala_Lumpur',
            'BEGIN:VTIMEZONE',
            'TZID:Asia/Kuala_Lumpur',
            'X-LIC-LOCATION:Asia/Kuala_Lumpur',
            'BEGIN:STANDARD',
            'TZOFFSETFROM:+0800',
            'TZOFFSETTO:+0800',
            'TZNAME:MYT',
            'DTSTART:19700101T000000',
            'END:STANDARD',
            'END:VTIMEZONE',
        ];

        foreach ($events as $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:ehadir-event-'.$event->id.'@ehadir.gov.my';
            $lines[] = 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z');

            if ($event->sepanjang_hari) {
                $lines[] = 'DTSTART;VALUE=DATE:'.$event->mula->format('Ymd');
                // For all-day events, RFC 5545 DTEND is exclusive (next day)
                $lines[] = 'DTEND;VALUE=DATE:'.$event->tamat->copy()->addDay()->format('Ymd');
            } else {
                $lines[] = 'DTSTART;TZID=Asia/Kuala_Lumpur:'.$event->mula->format('Ymd\THis');
                $lines[] = 'DTEND;TZID=Asia/Kuala_Lumpur:'.$event->tamat->format('Ymd\THis');
            }

            $lines[] = 'SUMMARY:'.$this->escapeText($event->tajuk);

            $descriptionParts = [];
            if ($event->catatan) {
                $descriptionParts[] = $event->catatan;
            }
            if ($event->attendees && $event->attendees->isNotEmpty()) {
                $attendeeNames = $event->attendees->pluck('nama_penuh')->take(10)->implode(', ');
                if ($event->attendees->count() > 10) {
                    $attendeeNames .= ' dan '.($event->attendees->count() - 10).' lagi';
                }
                $descriptionParts[] = 'Pegawai Terlibat: '.$attendeeNames;
            }
            $descriptionParts[] = 'Sistem eHadir v5.0: '.route('calendar.show', $event);

            $lines[] = 'DESCRIPTION:'.$this->escapeText(implode("\n\n", $descriptionParts));

            if ($event->lokasi) {
                $lines[] = 'LOCATION:'.$this->escapeText($event->lokasi);
            }

            $lines[] = 'CATEGORIES:'.$this->escapeText($event->jenis_label);
            $lines[] = 'STATUS:'.($event->trashed() ? 'CANCELLED' : 'CONFIRMED');

            if ($event->creator) {
                $creatorEmail = $event->creator->email ?: 'noreply@ehadir.gov.my';
                $lines[] = 'ORGANIZER;CN='.$this->escapeParam($event->creator->nama).':mailto:'.$creatorEmail;
            }

            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * Escape special characters in text values per RFC 5545 section 3.3.11.
     */
    protected function escapeText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\;', $text);
        $text = str_replace(',', '\,', $text);
        $text = str_replace(["\r\n", "\n", "\r"], '\n', $text);

        return $text;
    }

    /**
     * Escape special characters in parameter values.
     */
    protected function escapeParam(string $text): string
    {
        return str_replace(['"', ';', ':', "\r", "\n"], '', $text);
    }
}
