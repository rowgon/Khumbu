<?php

namespace FluentSupport\App\Services\Integrations\FluentBooking;

use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Models\Meta;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Support\Arr;

class BookingLinkManager
{
    /**
     * Persists a booking link record as serialised ticket meta.
     *
     * @param Ticket $ticket
     * @param array  $metadata
     * @return void
     */
    public function storeLink(Ticket $ticket, array $metadata)
    {
        Meta::create([
            'object_type' => 'ticket',
            'object_id'   => (int) $ticket->id,
            'key'         => 'fluent_booking_link',
            'value'       => maybe_serialize($metadata)
        ]);
    }

    /**
     * Extracts a link token and event details from a URL and stores them as ticket meta, skipping duplicates.
     *
     * @param Ticket $ticket
     * @param object $person
     * @param string $bookingUrl
     * @return void
     */
    public function storeLinkFromUrl(Ticket $ticket, $person, $bookingUrl)
    {
        $bookingUrl = esc_url_raw($bookingUrl);
        $linkToken = $this->getLinkTokenFromUrl($bookingUrl);

        if (!$bookingUrl || !$linkToken || $this->hasStoredLink($ticket, $linkToken)) {
            return;
        }

        $event = $this->getEventFromUrl($bookingUrl);

        if (!$event || !$event->calendar || !FluentBookingService::canUseEvent($event)) {
            return;
        }

        $this->storeLink($ticket, [
            'event_type_id'    => (int) $event->id,
            'event_type_title' => sanitize_text_field($event->title),
            'calendar_id'      => (int) $event->calendar_id,
            'calendar_title'   => sanitize_text_field($event->calendar->title),
            'booking_url'      => $bookingUrl,
            'link_token'       => sanitize_key($linkToken),
            'ticket_id'        => (int) $ticket->id,
            'customer_id'      => (int) $ticket->customer_id,
            'agent_id'         => $person ? (int) $person->id : 0,
            'created_at'       => current_time('mysql')
        ]);
    }

    /**
     * Returns true when this link token is already recorded in the ticket's booking link meta.
     *
     * @param Ticket $ticket
     * @param string $linkToken
     * @return bool
     */
    public function hasStoredLink(Ticket $ticket, $linkToken)
    {
        return (bool) $this->metaQuery()
            ->where('object_id', (int) $ticket->id)
            ->where('value', 'LIKE', '%' . sanitize_key($linkToken) . '%')
            ->exists();
    }

    /**
     * Returns all deserialised booking link records stored in this ticket's meta, most recent first.
     *
     * @param Ticket $ticket
     * @return array
     */
    public function getLinksForTicket(Ticket $ticket)
    {
        return $this->metaQuery()
            ->where('object_id', (int) $ticket->id)
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->get()
            ->map(function ($meta) {
                $link = Helper::safeUnserialize($meta->value);
                return is_array($link) ? $link : [];
            })
            ->filter()
            ->toArray();
    }

    /**
     * Returns a DB-ready datetime one day before the oldest stored link's creation date.
     *
     * Used to bound the booking query when scoping by generated links.
     *
     * @param array $generatedLinks
     * @return string
     */
    public function getEarliestLinkDate($generatedLinks)
    {
        if (!$generatedLinks || !is_array($generatedLinks)) {
            return '';
        }

        $dates = array_filter(array_map(function ($link) {
            $createdAt = Arr::get($link, 'created_at');
            return $createdAt && strtotime($createdAt) ? $createdAt : '';
        }, $generatedLinks));

        if (!$dates) {
            return '';
        }

        return gmdate('Y-m-d H:i:s', strtotime(min($dates)) - DAY_IN_SECONDS);
    }

    /**
     * Extracts all tokenised booking URLs (containing fs_booking_link_token) from HTML content.
     *
     * @param string $content
     * @return array
     */
    public function extractLinksFromContent($content)
    {
        $content = html_entity_decode((string) $content, ENT_QUOTES, 'UTF-8');

        if (!preg_match_all('/https?:\/\/[^\s"\']*fs_booking_link_token=[a-f0-9]{32}[^\s"\']*/i', $content, $matches)) {
            return [];
        }

        return array_values(array_unique(array_map('esc_url_raw', $matches[0])));
    }

    /**
     * Returns the fs_booking_link_token query param value from a URL.
     *
     * @param string $url
     * @return string
     */
    public function getLinkTokenFromUrl($url)
    {
        return sanitize_key(Arr::get($this->parseUrlQueryArgs($url), 'fs_booking_link_token'));
    }

    /**
     * Resolves a CalendarSlot from a booking URL.
     *
     * Tries host/event query params first; falls back to extracting calendar and event slugs from the URL path.
     *
     * @param string $bookingUrl
     * @return \FluentBooking\App\Models\CalendarSlot|null
     */
    public function getEventFromUrl($bookingUrl)
    {
        $queryArgs = $this->parseUrlQueryArgs($bookingUrl);
        $calendarSlug = sanitize_text_field(Arr::get($queryArgs, 'host'));
        $eventSlug = sanitize_text_field(Arr::get($queryArgs, 'event'));

        if (!$calendarSlug || !$eventSlug) {
            $path = trim((string) wp_parse_url($bookingUrl, PHP_URL_PATH), '/');
            $parts = array_values(array_filter(explode('/', $path)));

            if (count($parts) >= 2) {
                $eventSlug = sanitize_text_field(array_pop($parts));
                $calendarSlug = sanitize_text_field(array_pop($parts));
            }
        }

        if (!$calendarSlug || !$eventSlug) {
            return null;
        }

        return \FluentBooking\App\Models\CalendarSlot::query()
            ->with('calendar')
            ->where('slug', $eventSlug)
            ->whereHas('calendar', function ($query) use ($calendarSlug) {
                $query->where('slug', $calendarSlug);
            })
            ->first();
    }

    /**
     * Resolves which ticket a new booking belongs to.
     *
     * Tries matching via the fs_ticket_id query param first, then falls back to
     * fs_booking_link_token stored in ticket meta.
     *
     * @param object $booking
     * @param array  $bookingData
     * @return Ticket|null
     */
    public function getTicketFromBooking($booking, $bookingData = [])
    {
        $queryArgs = $this->getBookingSourceQueryArgs($booking, $bookingData);
        $ticketId = absint(Arr::get($queryArgs, 'fs_ticket_id'));

        if ($ticketId) {
            $ticket = Ticket::with('customer')->find($ticketId);

            if ($ticket && $this->bookingMatchesTicket($booking, $ticket)) {
                return $ticket;
            }
        }

        $linkToken = sanitize_key(Arr::get($queryArgs, 'fs_booking_link_token'));

        if ($linkToken) {
            $ticket = $this->findTicketByToken($booking, $linkToken);

            if ($ticket) {
                return $ticket;
            }
        }

        return null;
    }

    /**
     * Returns true when an internal_info conversation entry already exists for this booking.
     *
     * Prevents duplicate timeline notes from being created.
     *
     * @param object $booking
     * @return bool
     */
    public function hasTimelineNote($booking)
    {
        return (bool) Conversation::where('message_id', 'fluent_booking_scheduled_' . (int) $booking->id)
            ->where('source', 'fluent_booking')
            ->exists();
    }

    /**
     * Maps a Booking model to a sanitised array for frontend display.
     *
     * Includes local time conversion and join-URL extraction.
     *
     * @param object $booking
     * @return array
     */
    public function formatBooking($booking)
    {
        $joinUrl = '';
        $timezone = $this->getDisplayTimezone($booking);

        if (method_exists($booking, 'getLocationAsText')) {
            $locationText = $booking->getLocationAsText();
            if (filter_var($locationText, FILTER_VALIDATE_URL)) {
                $joinUrl = esc_url_raw($locationText);
            }
        }

        return [
            'id'               => (int) $booking->id,
            'event_title'      => $booking->calendar_event ? sanitize_text_field($booking->calendar_event->title) : '',
            'calendar_title'   => $booking->calendar ? sanitize_text_field($booking->calendar->title) : '',
            'status'           => sanitize_key($booking->status),
            'status_label'     => sanitize_text_field(method_exists($booking, 'getStatusLabel') ? $booking->getStatusLabel() : ucwords(str_replace('_', ' ', $booking->status))),
            'start_time'       => sanitize_text_field($booking->start_time),
            'local_start_time' => sanitize_text_field($this->convertTime($booking->start_time, $timezone)),
            'timezone'         => sanitize_text_field($timezone),
            'date_text'        => sanitize_text_field(method_exists($booking, 'getFullBookingDateTimeText') ? $booking->getFullBookingDateTimeText($timezone) : $booking->start_time),
            'admin_url'        => method_exists($booking, 'getAdminViewUrl') ? esc_url_raw($booking->getAdminViewUrl()) : '',
            'join_url'         => esc_url_raw($joinUrl),
            'internal_note'    => !empty($booking->internal_note) ? sanitize_textarea_field(wp_strip_all_tags($booking->internal_note)) : '',
        ];
    }

    /**
     * Returns a pre-scoped Meta query for ticket booking_link records.
     *
     * Shared by lookup, existence-check, and fetch operations.
     *
     * @return \FluentSupport\Framework\Database\Orm\Builder
     */
    private function metaQuery()
    {
        return Meta::where('object_type', 'ticket')
            ->where('key', 'fluent_booking_link');
    }

    /**
     * Parses the query string of a URL into an associative array.
     *
     * @param string $url
     * @return array
     */
    private function parseUrlQueryArgs($url)
    {
        $query = wp_parse_url($url, PHP_URL_QUERY);

        if (!$query) {
            return [];
        }

        parse_str($query, $queryArgs);

        return is_array($queryArgs) ? $queryArgs : [];
    }

    /**
     * Returns query args from the booking's source URL.
     *
     * Prefers $bookingData['source_url'] over the booking model's own field.
     *
     * @param object $booking
     * @param array  $bookingData
     * @return array
     */
    private function getBookingSourceQueryArgs($booking, $bookingData = [])
    {
        $sourceUrl = Arr::get($bookingData, 'source_url') ?: (!empty($booking->source_url) ? $booking->source_url : '');

        return $sourceUrl ? $this->parseUrlQueryArgs($sourceUrl) : [];
    }

    /**
     * Returns true when the booking's email case-insensitively matches the ticket customer's email.
     *
     * @param object $booking
     * @param Ticket $ticket
     * @return bool
     */
    private function bookingEmailMatchesTicket($booking, Ticket $ticket)
    {
        return $ticket->customer
            && !empty($booking->email)
            && strtolower(sanitize_email($ticket->customer->email)) === strtolower(sanitize_email($booking->email));
    }

    /**
     * Returns true when the email matches and the ticket has a stored booking link for this event.
     *
     * @param object $booking
     * @param Ticket $ticket
     * @return bool
     */
    private function bookingMatchesTicket($booking, Ticket $ticket)
    {
        return $this->bookingEmailMatchesTicket($booking, $ticket)
            && $this->ticketHasGeneratedEventLink($ticket, (int) $booking->event_id);
    }

    /**
     * Returns true when any stored booking link for this ticket targets the given event ID.
     *
     * @param Ticket $ticket
     * @param int    $eventId
     * @return bool
     */
    private function ticketHasGeneratedEventLink(Ticket $ticket, $eventId)
    {
        foreach ($this->getLinksForTicket($ticket) as $link) {
            if ((int) Arr::get($link, 'event_type_id') === (int) $eventId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Searches stored link-token meta across all tickets and returns the first whose email and event ID match the booking.
     *
     * @param object $booking
     * @param string $linkToken
     * @return Ticket|null
     */
    private function findTicketByToken($booking, $linkToken)
    {
        $linkToken = sanitize_key($linkToken);

        if (!$linkToken) {
            return null;
        }

        $metaRows = $this->metaQuery()
            ->where('value', 'LIKE', '%' . $linkToken . '%')
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->get();

        foreach ($metaRows as $meta) {
            $link = Helper::safeUnserialize($meta->value);

            if (!is_array($link) || sanitize_key(Arr::get($link, 'link_token')) !== $linkToken) {
                continue;
            }

            $ticket = Ticket::with('customer')->find((int) $meta->object_id);

            if ($ticket && $this->bookingMatchesGeneratedLink($booking, $ticket, $link)) {
                return $ticket;
            }
        }

        return null;
    }

    /**
     * Returns true when the booking email matches the ticket customer and the stored link targets the same event.
     *
     * @param object $booking
     * @param Ticket $ticket
     * @param array  $link
     * @return bool
     */
    private function bookingMatchesGeneratedLink($booking, Ticket $ticket, array $link)
    {
        return $this->bookingEmailMatchesTicket($booking, $ticket)
            && (int) Arr::get($link, 'event_type_id') === (int) $booking->event_id;
    }

    /**
     * Returns the display timezone for a booking, preferring the calendar author timezone.
     *
     * @param object $booking
     * @return string
     */
    private function getDisplayTimezone($booking)
    {
        if ($booking->calendar && !empty($booking->calendar->author_timezone)) {
            return $booking->calendar->author_timezone;
        }

        if (method_exists($booking, 'getHostTimezone') && $timezone = $booking->getHostTimezone()) {
            return $timezone;
        }

        return wp_timezone_string();
    }

    /**
     * Converts a UTC booking time string to the given timezone.
     *
     * @param string $time
     * @param string $timezone
     * @return string
     */
    private function convertTime($time, $timezone)
    {
        if (!$time) {
            return $time;
        }

        return \FluentBooking\App\Services\DateTimeHelper::convertFromUtc($time, $timezone, 'Y-m-d H:i:s');
    }
}
