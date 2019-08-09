<?php

namespace Internetrix\Events\Extensions;

use Internetrix\Events\Controllers\EventsPageCalendar;
use Internetrix\Events\Model\EventCategory;
use Internetrix\Events\Pages\CalendarEvent;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataExtension;

class EventPageExtension extends DataExtension
{
    private static $db = [
        'ShowCalendar' => 'Boolean',
        'ShowMonthJumper' => 'Boolean',
        'ShowUpcomingEvents' => 'Boolean',
        'UpcomingEventsCount' => 'Int',
    ];

    private static $many_many = [
        'ForcedCalendarCategories' => EventCategory::class,
    ];

    private static $defaults = [
        'UpcomingEventsCount' => 3,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $tab = 'Root.SideBar';
        $insertBefore = '';

        $fields->addFieldToTab($tab, HeaderField::create('EventOptions', 'Event Options'), $insertBefore);

        $fields->addFieldToTab($tab, CheckboxField::create('ShowCalendar', 'Show the calendar?'), $insertBefore);
        $fields->addFieldToTab($tab, CheckboxSetField::create(
                'ForcedCalendarCategories',
                'Restrict to these categories',
                EventCategory::get(),
                $this->owner->ForcedCalendarCategories()
        )->displayIf('ShowCalendar')->isChecked()->end(), $insertBefore);

        $fields->addFieldToTab($tab, CheckboxField::create('ShowMonthJumper', 'Show the month jumper?'), $insertBefore);

        $fields->addFieldToTab($tab, CheckboxField::create('ShowUpcomingEvents', 'Show upcoming events?'), $insertBefore);
        $fields->addFieldToTab($tab, NumericField::create('UpcomingEventsCount', 'How many upcoming events?')
                ->displayIf('ShowUpcomingEvents')->isChecked()->end(), $insertBefore);

        return $fields;
    }

    public function UpcomingEvents()
    {
        if ($this->owner->ShowUpcomingEvents) {
            $limit = $this->owner->UpcomingEventsCount? $this->owner->UpcomingEventsCount : 3;

            return CalendarEvent::get()->filter(['Start:GreaterThan' => date('Y-m-d H:i:s')])->limit($limit)->sort('Start');
        }
    }

    public function eventcalendar()
    {
        $calendar = EventsPageCalendar::create($this->owner, 'eventcalendar');

        return $calendar;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->owner->UpcomingEventsCount) {
            $this->owner->UpcomingEventsCount = 3;
        }
    }
}
