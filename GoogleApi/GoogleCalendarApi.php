<?php
/*
Install the Google Client Library:
composer require google/apiclient:^2.0
*/

use \Google_Service_Calendar;
use \Google_Service_Exception;
use \Google_Service_Calendar_Event;
//use \Google_Service_Calendar_Events;
//use \Google_Service_Calendar_CalendarListEntry;
//use \Google_Service_Calendar_Setting;
use Path\To\Class\GoogleApiSettings;

class GoogleCalendarApi
{
    private
    $client = null,
    $service = null,
    $calendarId = '',
    $scope = null,
    $log = [],
    $event_recurrence_warnings = [];

    const
        SCOPE_CALENDAR = Google_Service_Calendar::CALENDAR,
        SCOPE_EVENTS = Google_Service_Calendar::CALENDAR_EVENTS,
        MAX_RESPONSE_PAGES = 10,
        STORAGE_PERMISSION = 0777;

    const
        RESPONSE_STATUS_NO_CONTENT = 204, //removed successfully
        RESPONSE_STATUS_GONE = 410, //already removed
        RESPONSE_STATUS_INVALID_CREDENTIALS = 401;

    const
        EVENT__SUMMARY = 'summary',
        EVENT__LOCATION = 'location',
        EVENT__DESCRIPTION = 'description',
        EVENT__START = 'start',
        EVENT__END = 'end',
        EVENT__RECURRENCE = 'recurrence',
        EVENT__ATTENDEES = 'attendees',
        EVENT__REMINDERS = 'reminders',
        EVENT__SEND_NOTIFICATIONS = 'sendNotifications';

    const
        EVENT_STATUS_CONFIRMED = 'confirmed',
        EVENT_STATUS_TENTATIVE = 'tentative', //default
        EVENT_STATUS_CANCELLED = 'cancelled';
    /* Possible values are:
    "confirmed" - The event is confirmed. This is the default status.
    "tentative" - The event is tentatively confirmed.
    "cancelled" - The event is cancelled (deleted).
    Look at https://developers.google.com/calendar/api/v3/reference/events
    */

    public function __construct($calendarId = '')
    {
        $this->calendarId = $calendarId; //for example 'primary'
    }

    private function process_service_exception(Google_Service_Exception $exception, $request = []): void
    {
        /* //TODO: implement your own error reporting
        ErrorReports::log(
            __METHOD__, [
            'Google_Service_Exception' => [
                $exception->getMessage(),
                $exception->getFile().':'.$exception->getLine()
            ],
            'request' => $request
        ]);*/
    }

    public function connect(): bool
    {
        $scopes = [
            GoogleApiSettings::SCOPE_CALENDAR,
            GoogleApiSettings::SCOPE_CALENDAR_EVENTS
        ];
        if ($this->client && $this->service) { //already connected
            return true;
        }
        // \Google_Client;
        $this->client = GoogleApiSettings::get_client($scopes);
        if (empty($this->client)) {
            $this->log('connect', GoogleApiSettings::flash_log());
            return false;
        }
        $this->service = new Google_Service_Calendar($this->client);
        return !empty($this->service);
    }

    private function log($title, $data): void
    {
        $this->log[] = [$title => $data];
    }

    public function flashLog(): array
    {
        $log = $this->log;
        $this->log = [];
        return $log;
    }

    public function isClientLoaded(): bool
    {
        return !empty($this->client) || is_a($this->client, 'Google_Client');
    }

    /* event data format (from https://developers.google.com/calendar/v3/reference/events#extendedProperties):
    {
      "kind": "calendar#event",
      "etag": etag,
      "id": string,
      "status": string,
      "htmlLink": string,
      "created": datetime,
      "updated": datetime,
      "summary": string,
      "description": string,
      "location": string,
      "colorId": string,
      "creator": {
        "id": string,
        "email": string,
        "displayName": string,
        "self": boolean
      },
      "organizer": {
        "id": string,
        "email": string,
        "displayName": string,
        "self": boolean
      },
      "start": {
        "date": date,
        "dateTime": datetime,
        "timeZone": string
      },
      "end": {
        "date": date,
        "dateTime": datetime,
        "timeZone": string
      },
      "endTimeUnspecified": boolean,
      "recurrence": [
        string
      ],
      "recurringEventId": string,
      "originalStartTime": {
        "date": date,
        "dateTime": datetime,
        "timeZone": string
      },
      "transparency": string,
      "visibility": string,
      "iCalUID": string,
      "sequence": integer,
      "attendees": [
        {
          "id": string,
          "email": string,
          "displayName": string,
          "organizer": boolean,
          "self": boolean,
          "resource": boolean,
          "optional": boolean,
          "responseStatus": string,
          "comment": string,
          "additionalGuests": integer
        }
      ],
      "attendeesOmitted": boolean,
      "extendedProperties": {
        "private": {
          (key): string
        },
        "shared": {
          (key): string
        }
      },
      "hangoutLink": string,
      "conferenceData": {
        "createRequest": {
          "requestId": string,
          "conferenceSolutionKey": {
            "type": string
          },
          "status": {
            "statusCode": string
          }
        },
        "entryPoints": [
          {
            "entryPointType": string,
            "uri": string,
            "label": string,
            "pin": string,
            "accessCode": string,
            "meetingCode": string,
            "passcode": string,
            "password": string
          }
        ],
        "conferenceSolution": {
          "key": {
            "type": string
          },
          "name": string,
          "iconUri": string
        },
        "conferenceId": string,
        "signature": string,
        "notes": string,
        "gadget": {
        "type": string,
        "title": string,
        "link": string,
        "iconLink": string,
        "width": integer,
        "height": integer,
        "display": string,
        "preferences": {
          (key): string
        }
      },
      "anyoneCanAddSelf": boolean,
      "guestsCanInviteOthers": boolean,
      "guestsCanModify": boolean,
      "guestsCanSeeOtherGuests": boolean,
      "privateCopy": boolean,
      "locked": boolean,
      "reminders": {
        "useDefault": boolean,
        "overrides": [
          {
            "method": string,
            "minutes": integer
          }
        ]
      },
      "source": {
        "url": string,
        "title": string
      },
      "attachments": [
        {
          "fileUrl": string,
          "title": string,
          "mimeType": string,
          "iconLink": string,
          "fileId": string
        }
      ]
    }*/
    /**
     *
     * @param \Google_Service_Calendar_EventAttendee $attendee
     * @return array
     */
    private function load_attendee($attendee): array
    {
        return empty($attendee) ? [] : [
            'displayName' => $attendee->getDisplayName(),
            'email' => $attendee->getEmail(),
            'comment' => $attendee->getComment(),
            'id' => $attendee->getId(),
            'responseStatus' => $attendee->getResponseStatus(),
        ];
    }

    private function event_to_array($event): array
    {
        if (empty($event) || !is_object($event)) {
            return [];
        }
        /**
         * @var $event \Google_Service_Calendar_Event
         * @var $reminders \Google_Service_Calendar_EventReminders
         * @var $attendee \Google_Service_Calendar_EventAttendee
         * @var $reminderOverrider \Google_Service_Calendar_EventReminder
         */
        $start = empty($event->start->dateTime) ? $event->start->date : $event->start->dateTime;
        $end = empty($event->end->dateTime) ? $event->end->date : $event->end->dateTime;

        $reminders = $event->getReminders();
        $attendees = [];
        if ($event->getAttendees()) {
            foreach ($event->getAttendees() as $attendee) {
                $attendees[] = $this->load_attendee($attendee);
            }
        }
        $attendeesOmitted = [];
        if ($event->getAttendeesOmitted()) {
            foreach ($event->getAttendeesOmitted() as $attendee) {
                $attendeesOmitted[] = $this->load_attendee($attendee);
            }
        }
        $reminderOverriders = [];
        if ($reminders->getOverrides()) {
            foreach ($reminders->getOverrides() as $reminderOverrider) {
                $reminderOverriders[$reminderOverrider->getMethod()] = $reminderOverrider->getMinutes();
            }
        }
        return [
            'id' => $event->getId(),
            self::EVENT__SUMMARY => $event->getSummary(),
            self::EVENT__START => $start,
            self::EVENT__END => $end,
            self::EVENT__LOCATION => $event->getLocation(), //Geographic location of the event as free-form text. Optional.
            'creatorName' => $event->getCreator()->getDisplayName(),
            'creatorEmail' => $event->getCreator()->getEmail(),
            'etag' => $event->getEtag(),
            'htmlLink' => $event->getHtmlLink(),
            'hangoutLink' => $event->getHangoutLink(),
            'status' => $event->getStatus(),
            self::EVENT__ATTENDEES => $attendees,
            'attendeesOmitted' => $attendeesOmitted,
            self::EVENT__REMINDERS => $reminderOverriders,
            self::EVENT__RECURRENCE => $event->getRecurrence(),
            'attachments' => $event->getAttachments(),
            'visibility' => $event->getVisibility(),
            /*
            visibility	string
            Visibility of the event. Optional. Possible values are:
                "default" - Uses the default visibility for events on the calendar. This is the default value.
                "public" - The event is public and event details are visible to all readers of the calendar.
                "private" - The event is private and only event attendees may view event details.
                "confidential" - The event is private. This value is provided for compatibility reasons.
            */
            /*
            extendedProperties	object	Extended properties of the event.
                .private	    object	Properties that are private to the copy of the event that appears on this calendar.	writable
                .private.(key)	string	The name of the private property and the corresponding value.
                .shared	        object	Properties that are shared between copies of the event on other attendees' calendars.	writable
                .shared.(key)	string	The name of the shared property and the corresponding value.
            */
        ];
        /* example: array (
             'id' => 'no0tcc8q7huh0608ujbrs421ug',
             'summary' => 'Gena task for : Prepare guests room',
             'start' => '2019-11-12T14:00:00-04:00',
             'end' => '2019-11-12T15:00:00-04:00',
             'etag' => '"3146324846380000"',
             'htmlLink' => 'https://www.google.com/calendar/event?eid=bm8wdGNj...3AuY29t',
             'status' => 'confirmed',
             ...
           )*/
    }

    private function get_events($calendarId, $optParams): array
    {
        $result = [];
        $nextPageToken = false;
        /**
         * @var $response Google_Service_Calendar_Events
         * @var $service Google_Service_Calendar
         */
        $service = $this->service;
        for ($i = 1; $i < self::MAX_RESPONSE_PAGES; $i++) {
            if ($nextPageToken) {
                $optParams['pageToken'] = $nextPageToken;
            }
            try {
                $response = $service->events->listEvents($calendarId, $optParams);
            } catch (Google_Service_Exception $e) {
                $this->process_service_exception($e, [
                    'method' => __METHOD__,
                    'calendarId ' => $calendarId,
                    'optParams' => $optParams
                ]);
                $this->log(__METHOD__, 'events.listEvents() raised Google_Service_Exception');
                return ['error' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()];
            }
            if (!$response) {
                return ['error' => 'empty response'];
            }
            $pageEvents = $response->getItems();
            foreach ($pageEvents as $event) {
                $result[] = $this->event_to_array($event);
            }
            if (empty($response->getNextPageToken())) {
                break;
            }
            $nextPageToken = $response->getNextPageToken(); //example: 'EiUKGj...1GICcqL-mxOUC'
        }
        return $result;
    }

    //advice: before use attachment look at https://developers.google.com/drive/api/v3/about-sdk and test
    /*Important:
    You must perform a full sync of all events before enabling the supportsAttachments parameter
    for event modifications when adding attachments support into your existing app that stores events locally.
    If you do not perform a sync first, you may inadvertently remove existing attachments from user's events.
    */
    public function add_attachment_from_drive_to_event($driveService, $eventId, $fileId)
    {
        if (empty($this->service) || empty($this->calendarId)) {
            $this->log(__METHOD__, 'service or calendarId is empty');
            return false;
        }
        $file = $driveService->files->get($fileId);
        $event = $this->service->events->get($this->calendarId, $eventId);
        $attachments = $event->attachments;

        $attachments[] = array(
            'fileUrl' => $file->alternateLink,
            'mimeType' => $file->mimeType,
            'title' => $file->title
        );
        $changes = new Google_Service_Calendar_Event(
            array(
                'attachments' => $attachments
            )
        );

        try {
            $result = $this->service->events->patch(
                $this->calendarId,
                $eventId,
                $changes,
                array(
                    'supportsAttachments' => true
                )
            );
        } catch (Google_Service_Exception $e) {
            $this->process_service_exception($e, [
                'method' => __METHOD__,
                'calendarId ' => $this->calendarId,
                'eventId' => $eventId,
                'attachments' => $attachments
            ]);
            $this->log(__METHOD__, 'events.patch() raised Google_Service_Exception');
            return false;
        }

        return $result;
    }

    public function get_upcoming_events($timeFrom = 0, $maxResults = 10, $singleEvents = false): array
    {
        $this->flashLog();
        if (!$this->connect()) {
            return ['error' => 'Failed to connect'];
        }
        // Print the next 10 events on the user's calendar.
        $optParams = array(
            'maxResults' => $maxResults && is_numeric($maxResults) ? $maxResults : 10,
            //'orderBy' => 'startTime', // "The requested ordering is not available for the particular query."
            'singleEvents' => $singleEvents,
            'timeMin' => $timeFrom ? date('c', $timeFrom) : date('c'),
        );
        $result = $this->get_events($this->calendarId, $optParams);

        return $result;
    }

    /*
    Remember that maxResults does not guarantee the number of results on one page.
    Incomplete results can be detected by a non-empty nextPageToken field in the result.
    In order to retrieve the next page, perform the exact same request as previously and append a pageToken field with
    the value of nextPageToken from the previous page. A new nextPageToken is provided on the following pages until all
    the results are retrieved.

    For example, here is a query followed by the query for retrieving the next page of results in a paginated list:
    GET /calendars/primary/events?maxResults=10&singleEvents=true
    //Result contains
    "nextPageToken":"CiAKGjBpNDd2Nmp2Zml2cXRwYjBpOXA",

    The subsequent query takes the value from nextPageToken and submits it as the value for pageToken:
    GET /calendars/primary/events?maxResults=10&singleEvents=true&pageToken=CiAKGjBpNDd2Nmp2Zml2cXRwYjBpOXA
    */

    private function get_val(&$array, $key, $default_value = null)
    {
        return isset($array[$key]) ? $array[$key] : $default_value;
    }

    public function create_calendar($new_id)
    {
        $this->flashLog();
        if (!$this->connect()) {
            return '';
        }
        $calendarListEntry = new \Google_Service_Calendar_CalendarListEntry();
        $calendarListEntry->setId($new_id);
        try {
            $createdEntry = $this->service->calendarList->insert($calendarListEntry);
        } catch (\Google_Service_Exception $e) {
            $this->process_service_exception($e, ['method' => __METHOD__, 'new_id ' => $new_id]);
            $this->log(__METHOD__, 'calendarList.insert() raised Google_Service_Exception');
            return false;
        } catch (\Exception $e) {
            /*
            ErrorReports::log(
                __METHOD__,
                [
                    'Exception' => [
                        $e->getMessage(),
                        $e->getFile() . ':' . $e->getLine()
                    ],
                    'new_id' => $new_id,
                ]
            );*/
            $this->log(__METHOD__, 'calendarList.insert() raised unknown exception');
            return false;
        }
        return $createdEntry->getSummary();
    }

    public function create_event($data): string
    {
        $this->flashLog();

        $event_data = array(
            self::EVENT__SUMMARY => $this->get_val($data, self::EVENT__SUMMARY),
            self::EVENT__LOCATION => $this->get_val($data, self::EVENT__LOCATION),
            self::EVENT__DESCRIPTION => $this->get_val($data, self::EVENT__DESCRIPTION),
            self::EVENT__START => null,
            self::EVENT__END => null,
            self::EVENT__RECURRENCE => !empty($data[self::EVENT__RECURRENCE])
                ? $this->compile_event_recurrence($data[self::EVENT__RECURRENCE])
                : null,
            self::EVENT__ATTENDEES => $this->get_val($data, self::EVENT__ATTENDEES),
            self::EVENT__REMINDERS => $this->get_val($data, self::EVENT__REMINDERS),
            self::EVENT__SEND_NOTIFICATIONS => $this->get_val($data, self::EVENT__SEND_NOTIFICATIONS),
        );
        /* event data example:
        $event = array(
            'summary' => 'created event',
            'location' => '800 Howard St., San Francisco, CA 94103',
            'description' => 'Some description',
            'start' => array(
                'dateTime' => '2019-10-30T09:00:00',
                'timeZone' => 'America/Santo_Domingo',
            ),
            'end' => array(
                'dateTime' => '2019-10-30T17:00:00-07:00', //
                'timeZone' => 'America/Los_Angeles',
            ),
            //date time formats:
                2017-01-25T09:00:00-0500 (use default time zone -5),
                2017-01-25T09:00:00 (use default time zone),
                2017-01-25T09:00:00 + timeZone field
                in UTC use Z or +0000: 2017-01-25T14:00:00Z or 2017-01-25T14:00:00+0000.

            'recurrence' => array(
                'RRULE:FREQ=DAILY;COUNT=2',
            'attendees' => array(
                array('email' => 'hjgkjh543@example.com'),
                array('email' => 'test765@example.com'),
                //The event appears on all the primary G/Calendars of the attendees you included with the same event ID.
            ),
            'reminders' => array(
                'useDefault' => FALSE,
                'overrides' => array(
                    array('method' => 'email', 'minutes' => 24 * 60),
                    array('method' => 'popup', 'minutes' => 10),
                ),
            ),
            //'sendNotifications' => true, // --> the attendees will also receive an email notification for your event
        ); */

        if (!empty($data[self::EVENT__START])) {
            $event_data[self::EVENT__START] = [
                'dateTime' => date('Y-m-d\TH:i:s', $data[self::EVENT__START]),
                'timeZone' => TIME_ZONE_ID,
            ];
        }
        if (!empty($data[self::EVENT__END])) {
            $event_data[self::EVENT__END] = [
                'dateTime' => date('Y-m-d\TH:i:s', $data[self::EVENT__END]),
                'timeZone' => TIME_ZONE_ID,
            ];
        }

        $event_data = array_filter($event_data, function ($value) {
            return !is_null($value);
        });

        $this->log('sending event', $event_data);
        if (!$this->connect()) {
            return '';
        }
        $event = new Google_Service_Calendar_Event($event_data);
        try {
            $createdEvent = $this->service->events->insert($this->calendarId, $event);
        } catch (\Google_Service_Exception $e) {
            $this->process_service_exception($e, [
                'method' => __METHOD__,
                'calendarId ' => $this->calendarId,
                'event' => $event_data
            ]);
            $this->log(__METHOD__, 'events.insert() raised Google_Service_Exception');
            return '';
        } catch (\InvalidArgumentException $e) {
            /*
            ErrorReports::log(
                __METHOD__,
                [
                    'InvalidArgumentException' => [
                        $e->getMessage(),
                        $e->getFile() . ':' . $e->getLine()
                    ],
                    'request' => [$this->calendarId, $event]
                ]
            );*/
            $this->log(__METHOD__, 'events.insert() raised InvalidArgumentException');
            return '';
        } catch (\Exception $e) {
            /*
            ErrorReports::log(
                __METHOD__,
                [
                    'Exception' => [
                        $e->getMessage(),
                        $e->getFile() . ':' . $e->getLine()
                    ],
                    'request' => [$this->calendarId, $event]
                ]
            );*/
            $this->log(__METHOD__, 'events.insert() raised unknown exception');
            return '';
        }

        /** @var $createdEvent \Google_Service_Calendar_Event */
        return is_a($createdEvent, '\Google_Service_Calendar_Event')
            ? $createdEvent->getId() : '';
    }

    /* Warning for Recurring Events:
    Do not modify instances individually when you want to modify the entire recurring event,
    or "this and following" instances.
    This creates lots of exceptions that clutter the calendar, slowing down access and sending a high number
    of change notifications to users.
    */
    public function patch_event($eventId, $changesArray)
    {
        $this->flashLog();
        if (empty($changesArray) || !is_array($changesArray)) {
            return false;
        }
        if (!$this->connect()) {
            return false;
        }
        $changesObject = new Google_Service_Calendar_Event($changesArray);
        try {
            $patched_event = $this->service->events->patch(
                $this->calendarId,
                $eventId,
                $changesObject
                //, array('supportsAttachments' => TRUE) //additional params
            );
        } catch (Google_Service_Exception $e) {
            $this->process_service_exception($e, [
                'method' => __METHOD__,
                'calendarId ' => $this->calendarId,
                'eventId' => $eventId,
                'changes' => $changesArray
            ]);
            $this->log(__METHOD__, 'events.patch() raised Google_Service_Exception');
            return false;
        }

        $this->log(__METHOD__, 'event patch result: ' . var_export($patched_event, 1));
        return $patched_event;
    }

    public function event_status_domain(): array
    {
        return [
            self::EVENT_STATUS_CANCELLED => 'canceled (deleted)',
            self::EVENT_STATUS_TENTATIVE => 'tentative',
            self::EVENT_STATUS_CONFIRMED => 'confirmed',
        ];
    }

    public function set_event_status($eventId, $status)
    {
        if (empty($eventId)) {
            $this->log(__METHOD__, 'empty event id ' . var_export($eventId, 1));
            return false;
        }
        if (isset($this->event_status_domain()[$status])) {
            $this->log(__METHOD__, 'unsupported status code ' . var_export($status, 1));
            return false;
        }
        if (!$this->connect()) {
            return false;
        }
        //https://www.googleapis.com/calendar/v3/calendars/calendarId/events/instanceId
        try {
            $events = $this->service->events->instances($this->calendarId, $eventId);
        } catch (Google_Service_Exception $e) {
            $this->process_service_exception($e, [
                'method' => __METHOD__,
                'calendarId ' => $this->calendarId,
                'eventId' => $eventId
            ]);
            $this->log(__METHOD__, 'events.instances() raised Google_Service_Exception');
            return false;
        }

        $instance = $events->getItems()[0];
        $instance->setStatus($status);
        try {
            $updatedInstance = $this->service->events->update($this->calendarId, $instance->getId(), $instance);
        } catch (Google_Service_Exception $e) {
            $this->process_service_exception($e, [
                'method' => __METHOD__,
                'eventId' => $eventId,
                'newStatus' => $status
            ]);
            $this->log(__METHOD__, 'events.update() raised Google_Service_Exception');
            return false;
        }
        return $updatedInstance ? $updatedInstance->getUpdated() : false;
    }

    public function set_event_cancelled($eventId)
    {
        if (empty($eventId)) {
            $this->log(__METHOD__, 'empty event id ' . var_export($eventId, 1));
            return false;
        }
        return $this->set_event_status($eventId, self::EVENT_STATUS_CANCELLED);
    }

    public function delete_event($eventId)
    {
        /* This request requires authorization with at least one of the following scopes:
            https://www.googleapis.com/auth/calendar
            https://www.googleapis.com/auth/calendar.events
        If successful, this method returns an empty response body.
        */
        $this->flashLog();
        if (empty($eventId)) {
            return false;
        }
        if (!$this->connect()) {
            return false;
        }
        try {
            $result = $this->service->events->delete($this->calendarId, $eventId);
        } catch (Google_Service_Exception $e) {
            $this->process_service_exception($e, [
                'method' => __METHOD__,
                'calendarId' => $this->calendarId,
                'eventId' => $eventId
            ]);
            $this->log(__METHOD__, 'events.delete() raised Google_Service_Exception');
            return false;
        }
        /* sendUpdates	string:	Guests who should receive notifications about the deletion of the event.
            Acceptable values are:
            "all": Notifications are sent to all guests.
            "externalOnly": Notifications are sent to non-Google Calendar guests only.
            "none": No notifications are sent. This value should only be used for migration use cases (note that in most migration cases the import method should be used).
        */
        $this->log(__METHOD__, 'event removing: ' . var_export($result, 1));
        return $result;
    }

    public function get_response_status_description($action, $status_code): string
    {
        switch ($action . $status_code) {
            case 'delete_event' . self::RESPONSE_STATUS_NO_CONTENT:
                return 'removed successfully';
            case 'delete_event' . self::RESPONSE_STATUS_GONE:
                return 'already removed';
        }
        switch ($status_code) {
            case self::RESPONSE_STATUS_NO_CONTENT:
                return 'no content';
            case self::RESPONSE_STATUS_GONE:
                return 'resource gone';
            case self::RESPONSE_STATUS_INVALID_CREDENTIALS:
                return 'invalid credentials';
            default:
                return 'unknown status ' . $status_code;
        }
    }

    public function create_quick_event($text, $location = '', $date = false, $start_time = false, $end_time = false)
    {
        $this->flashLog();
        if (!$this->connect()) {
            return false;
        }
        $this->log(__METHOD__, [
            'text' => $text,
            'location' => $location,
            'date, start time, end time' => [$date, $start_time, $end_time]
        ]);
        if (empty($text) || !is_string($text)) {
            $this->log(__METHOD__, 'empty text');
            return false;
        }
        if ($location) {
            if (!is_string($location)) {
                $this->log(__METHOD__, 'location is not string: ' . var_export($location));
                return false;
            }
            $text .= " at $location";
        }
        if ($date && is_integer($date)) {
            $text .= " on " . date('Y-m-d', $date);
        }
        if ($start_time && is_integer($start_time) && $end_time && is_integer($end_time)) {
            $text .= " " . date('H:i', $start_time) . ' - ' . date('H:i', $end_time);
        } elseif (date('G', $date) > 0) { //hours in the date != 0
            $text .= " " . date('H:i', $date);
        } else {
            $text .= " 10:00 - 18:00"; //by default time range is 10-18:00
        }
        /** @var $createdEvent Google_Service_Calendar_Event */
        try {
            $createdEvent = $this->service->events->quickAdd($this->calendarId, $text);
            //example 'Appointment at Somewhere on June 3rd 10am-10:25am'
        } catch (Google_Service_Exception $e) {
            $this->process_service_exception($e, [
                'method' => __METHOD__,
                'calendarId' => $this->calendarId,
                'text' => $text,
                'location' => $location,
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
            ]);
            $this->log(__METHOD__, 'events.quickAdd() raised Google_Service_Exception');
            return false;
        }
        return is_a($createdEvent, 'Google_Service_Calendar_Event')
            ? $createdEvent->getId() : false;
    }

    public function get_calendars_list(): array
    {
        $this->flashLog();
        if (!$this->connect()) {
            return [];
        }
        try {
            $calendarList = $this->service->calendarList->listCalendarList();
        } catch (Google_Service_Exception $e) {
            $this->process_service_exception($e, ['method' => __METHOD__]);
            $this->log(__METHOD__, 'listCalendarList() raised Google_Service_Exception');
            return [];
        }
        if (empty($calendarList)) {
            $this->log(__METHOD__, 'listCalendarList() returned empty value');
            return [];
        }
        $result = [];
        foreach ($calendarList->getItems() as $calendarListEntry) {
            /** @var $calendarListEntry Google_Service_Calendar_CalendarListEntry */
            $result[] = [
                'summary' => $calendarListEntry->getSummary(),
                'id' => $calendarListEntry->getId(),
                'isPrimary' => (bool) $calendarListEntry->getPrimary(),
            ];
        }
        return $result;
        /* --> array (
          0 =>
          array (
            'summary' => 'jhvhgkjg@xmlshop.com',
            'id' => 'jhvhgkjg@xmlshop.com',
            'isPrimary' => true,
          ),
          1 =>
          array (
            'summary' => 'Contacts',
            'id' => 'addressbook#contacts@group.v.calendar.google.com',
            'isPrimary' => false,
          ),
          2 =>
          array (
            'summary' => 'Holidays in Dominican Republic',
            'id' => 'en.do#holiday@group.v.calendar.google.com',
            'isPrimary' => false,
          ),
        )*/
    }

    public function get_settings_list(): array
    {
        $this->flashLog();
        if (!$this->connect()) {
            return [];
        }
        try {
            $settings = $this->service->settings->listSettings();
        } catch (Google_Service_Exception $e) {
            $this->process_service_exception($e, ['method' => __METHOD__]);
            $this->log(__METHOD__, 'listSettings() raised Google_Service_Exception');
            return [];
        }
        if (empty($settings)) {
            $this->log(__METHOD__, 'listSettings() returned empty value');
            return [];
        }
        $result = [];
        foreach ($settings->getItems() as $setting) {
            $result[$setting->getId()] = $setting->getValue();
        }
        return $result;
        /*example: array (
          'autoAddHangouts' => 'true',
          'defaultEventLength' => '60',
          'dateFieldOrder' => 'MDY',
          'weekStart' => '0',
          'format24HourTime' => 'false',
          'hideInvitations' => 'false',
          'locale' => 'en',
          'remindOnRespondedEventsOnly' => 'false',
          'showDeclinedEvents' => 'true',
          'timezone' => 'America/New_York',
          'useKeyboardShortcuts' => 'true',
          'hideWeekends' => 'false',
        )*/
    }


    //
    //event settings parser
    //

    public function event_recurrence_rule_types(): array
    {
        return [
            'rule', //Examples: 'RRULE:FREQ=DAILY;COUNT=2', "RRULE:FREQ=WEEKLY;UNTIL=20110701T170000Z",
            //"RRULE:FREQ=WEEKLY;WKST=SU;BYDAY=FR;UNTIL=20141203T173500Z",
            //"RRULE:FREQ=WEEKLY;COUNT=5;BYDAY=TU,FR"
            'include_dates',
            'exclude_dates', //example: "EXDATE;TZID=America/New_York:20140905T103000"
        ];
    }

    //documentation at https://developers.google.com/calendar/recurringevents
    private function compile_event_recurrence($rules): array
    {
        $result = [];
        $this->event_recurrence_warnings = [];

        foreach ($rules as $rule_type => $settings) {
            if (!in_array($rule_type, $this->event_recurrence_rule_types())) {
                $this->event_recurrence_warnings[] = "compile_event_recurrence: unexpected rule type $rule_type with value "
                    . var_export($settings, 1);
                continue;
            }
            switch ($rule_type) {
                case 'rule':
                    $compiled = $this->compile_event_recurrence_rule($settings);
                    break;
                case 'exclude_dates':
                    //EXDATE - exclude certain date, must be in format as the start and end, look RFC 5545
                    //example: "EXDATE;TZID=America/New_York:20140905T103000"
                    $compiled = $this->compile_event_recurrence_additional_dates($settings);
                    $compiled = empty($compiled) ? '' : 'EXDATE;' . $compiled;
                    break;
                case 'include_dates':
                    //RDATE specifies additional dates or date-times when the event occurrences should happen, look RFC 5545.
                    //For example, RDATE;VALUE=DATE:19970101,19970120 for all-day events
                    $compiled = $this->compile_event_recurrence_additional_dates($settings);
                    $compiled = empty($compiled) ? '' : 'RDATE;' . $compiled;
                    break;
            }
            if ($compiled) {
                $result[] = $compiled;
            }
        }

        if ($this->event_recurrence_warnings) {
            /*
            ErrorReports::log(
                __METHOD__,
                [
                    'input_data' => $rules,
                    'result' => $result,
                    'warnings' => $this->event_recurrence_warnings
                ]
            );*/
            $this->event_recurrence_warnings = [];
            return [];
        }
        return $result;
    }

    private function compile_event_recurrence_additional_dates($settings): string
    {
        $parts = [];
        //date time validation
        if (
            (isset($settings['date_list']) && isset($settings['datetime_list'])) ||
            (empty($settings['date_list']) && empty($settings['datetime_list']))
        ) {
            $this->event_recurrence_warnings[] = "compile_event_recurrence_additional_dates: "
                . "date_list and datetime_list are used together or are empty: "
                . var_export($settings, 1);
            return '';
        }
        $dates = [];
        if (!empty($settings['date_list'])) {
            $parts[] = 'VALUE=DATE';
            foreach ($settings['date_list'] as $date) {
                if (!is_numeric($date) || empty($date)) {
                    $this->event_recurrence_warnings[] = "compile_event_recurrence_additional_dates: "
                        . "wrong date_list item: " . var_export($date, 1);
                    continue;
                }
                $dates[] = date('Ymd', $date); //20191201
            }
        }
        if (!empty($settings['datetime_list'])) {
            if (empty($settings['time_zone_id'])) {
                $settings['time_zone_id'] = TIME_ZONE_ID;
            }
            if (function_exists('timezone_identifiers_list')) {
                if (!in_array($settings['time_zone_id'], timezone_identifiers_list())) {
                    $this->event_recurrence_warnings[] = "compile_event_recurrence_additional_dates: "
                        . "time zone id is wrong: " . var_export($settings['time_zone_id'], 1);
                    unset($settings['time_zone_id']);
                }
            } else {
                $this->event_recurrence_warnings[] = "compile_event_recurrence_additional_dates: "
                    . "timezone_identifiers_list() is undefined. Can't validate " . $settings['time_zone_id'];
            }
            if (!empty($settings['time_zone_id'])) {
                $parts[] = 'TZID=' . $settings['time_zone_id'];
            }
            foreach ($settings['datetime_list'] as $datetime) {
                if (!is_numeric($datetime) || empty($datetime)) {
                    $this->event_recurrence_warnings[] = "compile_event_recurrence_additional_dates: "
                        . "wrong datetime_list item: " . var_export($datetime, 1);
                    continue;
                }
                $dates[] = date('Ymd\THis', $datetime); //20140905T103000
            }
        }

        return empty($parts) || empty($dates) ? '' : implode('', $parts) . ':' . implode(',', $dates);
    }

    private function compile_event_recurrence_rule($rule): string
    {
        $parts = [];
        $prefix = 'RRULE:'; // defines a regular rule for repeating the event, look RFC 5545
        $keys_map = [
            'frequency' => 'FREQ', //Required
            'interval' => 'INTERVAL', //FREQ=DAILY;INTERVAL=2 means once every two days

            //You can use either COUNT or UNTIL to specify the end of the event recurrence. Don't use both in the same rule.
            'count' => 'COUNT', //Number of times this event should be repeated
            'until_date' => 'UNTIL', //The date until which the event should be repeated (inclusive)
            'until_datetime' => 'UNTIL', //same but with time, compiled example 20200701T170000
            //Must work, but I get "recurrence error", todo: fix recurrence until datetime

            'week_start' => 'WKST',

            'by_day' => 'BYDAY', //Days of the week on which the event should be repeated (SU, MO, TU, etc.).
            'by_month' => 'BYMONTH',
            'by_year_day' => 'BYYEARDAY',
            'by_hour' => 'BYHOUR',
        ];
        $week_days = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];
        $value_domains = [
            'frequency' => ['DAILY', 'WEEKLY', 'MONTHLY'],
            'week_start' => $week_days,
            'by_day' => $week_days,
        ];
        foreach ($rule as $key => $value) {
            if (!isset($keys_map[$key])) {
                $this->event_recurrence_warnings[] = "compile_event_recurrence_rule: unexpected key $key with value "
                    . var_export($value, 1);
                continue;
            }
            if (isset($value_domains[$key])) {
                if (is_string($value)) {
                    $value = strtoupper($value);
                }
                if (empty($value) || !in_array($value, $value_domains[$key])) {
                    $this->event_recurrence_warnings[] = "compile_event_recurrence_rule: wrong $key value: "
                        . var_export($value, 1);
                    continue;
                }
            }
            if ($key === 'count' && !is_numeric($value)) {
                $this->event_recurrence_warnings[] = "compile_event_recurrence_rule: Count must be numeric. Got "
                    . var_export($value, 1);
                continue;
            }
            if ($key === 'until_date') {
                if (!is_numeric($value)) {
                    $this->event_recurrence_warnings[] = "compile_event_recurrence_rule: Until date must be numeric. Got "
                        . var_export($value, 1);
                    continue;
                }
                $value = date('Ymd', $value);
            }
            if ($key === 'until_datetime') { //deprecated: doesn't work
                if (!is_numeric($value)) {
                    $this->event_recurrence_warnings[] = "compile_event_recurrence_rule: Until datetime must be numeric. Got "
                        . var_export($value, 1);
                    continue;
                }
                $value = date('Ymd\THis', $value);
            }

            $parts[] = $keys_map[$key] . '=' . $value;

        }
        return empty($parts) ? '' : $prefix . implode(';', $parts);
    }

    //documentation at https://developers.google.com/calendar/concepts/events-calendars
    public function validate_event_settings($data): bool
    {
        $valid = true;

        $error = '';
        foreach ($this->get_event_validation_rules() as $field => $rules) {
            /* //todo: implement your field validator
            if (!FieldValidator::validate($field, $rules, isset($data[$field]) ? $data[$field] : '')) {
                $error = $field . ' is invalid'
                    . (is_array($data[$field]) ? '' : ' (' . $data[$field] . ')')
                    . '.'
                    . (isset($rules['type']) ? ' Format: ' . FieldValidator::get_type_description($rules['type']) : '');
                break;
            }*/
        }

        if ($error) {
            $this->log(__METHOD__, $error);
            return false;
        }
        if (!empty($data['recurrence']) && !$this->compile_event_recurrence($data['recurrence'])) {
            $this->log(__METHOD__, 'Invalid recurrence data');
            return false;
        }
        return $valid;
    }

    private function get_event_validation_rules(): array
    {
        return [
            /* //todo: implement your validation rules
            'summary' => ['type' => FieldValidator::TYPE_TEXT, 'minlen' => 5, 'maxlen' => 50, 'required' => true],
            'location' => ['type' => FieldValidator::TYPE_TEXT, 'minlen' => 5, 'maxlen' => 50, 'required' => true],
            'description' => ['type' => FieldValidator::TYPE_TEXT, 'minlen' => 20, 'maxlen' => 200],
            'start' => ['type' => FieldValidator::TYPE_NUMERIC, 'required' => true],
            'end' => ['type' => FieldValidator::TYPE_NUMERIC, 'required' => true],
            //'numberTest'=>['type'=>FieldValidator::TYPE_NUMERIC, 'not_negative'=>true,],
            'recurrence' => [
                'type' => FieldValidator::TYPE_ARRAY,
                'keys_list' => $this->event_recurrence_rule_types(),
            ],
            'attendees' => [
                'type' => FieldValidator::TYPE_ARRAY,
                'maxqty' => 20,
            ],
            'reminders' => [
                'type' => FieldValidator::TYPE_ARRAY,
                'keys_list' => ['useDefault', 'overrides'],
                'maxqty' => 20,
            ],
            'sendNotifications' => ['type' => FieldValidator::TYPE_BOOLEAN],
            */
        ];
    }

    //
    // END of event settings parser
    //
}

