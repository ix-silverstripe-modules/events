<?php

namespace Internetrix\Events\Extensions;

use Internetrix\Events\Pages\CalendarEvent;
use SilverStripe\Admin\LeftAndMainExtension;

class CalendarEventCustomActionsExtension extends LeftAndMainExtension
{
    private static $allowed_actions = [
        'doDuplicateCalendarEvent'
    ];

    public function doDuplicateCalendarEvent()
    {
        $className = $this->owner->request->postVar('ClassName');

        if ($className != CalendarEvent::class) {
            $this->owner->response->addHeader(
                'X-Status',
                rawurlencode('Incorrect class type ' . $className)
            );

            return;
        }

        $id = $this->owner->request->postVar('ID');

        if (!$id) {
            $this->owner->response->addHeader(
                'X-Status',
                rawurlencode('Calendar Event object not found')
            );

            return;
        }

        $calendarEvent = CalendarEvent::get()->byID($id);

        if (!$calendarEvent || !$calendarEvent->exists()) {
            $this->owner->response->addHeader(
                'X-Status',
                rawurlencode('Calendar Event object not found')
            );

            return;
        }

        $clonedCalendarEvent = $calendarEvent->duplicate(true, [
            'Categories',
        ]);

        $this->owner->response->addHeader(
            'X-Status',
            rawurlencode('Calendar event successfully duplicated')
        );


        return $this->owner->redirect($clonedCalendarEvent->CMSEditLink());
    }
}
