<?php

namespace Internetrix\Events\Controllers;

use Internetrix\Events\Pages\CalendarEvent;
use Internetrix\Events\Pages\EventsPage;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\View\ArrayData;

/**
 * @package irxeventcalendar
 * @author Internetrix
 */
class EventsPageCalendar extends Controller
{
    private static $url_handlers = [
        '' => 'index',
    ];

    private static $allowed_actions = [
        'index',
    ];

    protected $parent;
    protected $name;
    protected $month;
    protected $year;
    protected $day;
    protected $widecalendar = false;
    protected $member;

    public function __construct($parent, $name, $month = null, $year = null, $day = null)
    {
        if (!$month) {
            $month = date('m');
        }
        if (!$year) {
            $year = date('Y');
        }
        if (!$day) {
            $day = date('d');
        }// or d

        $this->parent = $parent;
        $this->name = $name;
        $this->month = $month;
        $this->year = $year;
        $this->day = $day;

        parent::__construct();
    }

    public function index($r)
    {
        if ($m = $r->getVar('m')) {
            $this->month = (int) $m;
        }
        if ($y = $r->getVar('y')) {
            $this->year = (int) $y;
        }

        return $this->forTemplate();
    }

    public function forTemplate()
    {
        $time = mktime(null, null, null, $this->month, 1, $this->year);
        $prev = mktime(null, null, null, $this->month - 1, 1, $this->year);
        $next = mktime(null, null, null, $this->month + 1, 1, $this->year);

        // Need to ensure the calendar loads from the correct controller. Get the link of the EventsPage and use that.
        $eventsPage = EventsPage::get()->first();
        $link = $this;

        if ($eventsPage) {
            $link = $eventsPage;
        }

        return $this->render([
            'MonthName' => date('F', $time),
            'Year' => $this->year,
            'PrevLink' => $link->Link(sprintf('%s?m=%d&y=%d', $this->name, date('m', $prev), date('Y', $prev))),
            'NextLink' => $link->Link(sprintf('%s?m=%d&y=%d', $this->name, date('m', $next), date('Y', $next))),
        ]);
    }

    public function Weeks()
    {
        $weeks = new ArrayList();
        $start = mktime(0, 0, 0, $this->month, 1, $this->year);
        $finish = mktime(0, 0, 0, $this->month + 1, 0, $this->year);

        if (date('N', $start) == 1) {
            $curr = $start;
        } else {
            $curr = strtotime('last monday', $start);
        }

        // Figure out which days have events on them.
        $nWeeks = ceil(($finish - $curr) / (3600 * 24 * 7));

        $conn = DB::get_conn();
        $events = new SQLSelect();

        $events = CalendarEvent::get()->where(
            sprintf(
                '(("CalendarEvent"."Start" <= \'%1$s\' AND "CalendarEvent"."End" >= \'%1$s\') OR ("CalendarEvent"."Start" BETWEEN \'%1$s\' AND \'%2$s\'))',
                date('Y-m-d', $curr),
                date('Y-m-d', $curr + $nWeeks * 3600 * 24 * 7)
        )
        )->sort('Start');

        $eventDays = [];

        foreach ($events as $event) {
            $start = strtotime($event->Start);
            $end = strtotime($event->End);

            while ($start <= $end) {
                $colour = null;
                $extra = null;

                $category = $event->Categories()->first();
                if ($category) {
                    $colour = $category->Colour;
                    $extra = "style='color: #" . $colour . "'";
                }

                $title = "<li style='list-style: none;'><span $extra>" . $event->MenuTitle . '</span></li>';

                $eventDays[date('m', $start)][date('d', $start)]['Title'][] = $title;
                $eventDays[date('m', $start)][date('d', $start)]['ID'][] = $event->ID;
                $eventDays[date('m', $start)][date('d', $start)]['Colour'][] = $colour;

                $start += 3600 * 24;
            }
        }

        // Now loop through and build a set of weeks and days.
        while ($curr <= $finish) {
            $days = new ArrayList();

            for ($i = 0; $i < 7; $i++) {
                $d = date('d', $curr);
                $m = date('m', $curr);
                $ymd = date('Y-m-d', $curr);
                if ($d == $this->day) {
                    $selected = true;
                } else {
                    $selected = false;
                }
                $curr = strtotime('+1 day', $curr);

                $ids = '';
                $titles = '';
                $colours = '';
                if (isset($eventDays[$m][$d])) {
                    $titles = $eventDays[$m][$d]['Title'];
                    $titles = implode(' ', $titles);
                    $ids = $eventDays[$m][$d]['ID'];
                    $ids = implode(' ', $ids);
                    $colours = $eventDays[$m][$d]['Colour'];
                    if (is_array($colours)) {
                        $assc = [];
                        foreach ($colours as $c) {
                            $assc[] = ArrayData::create(['Colour' => $c]);
                        }

                        $colours = ArrayList::create($assc);
                    }
                }

                $days->push(new ArrayData([
                    'Num' => $d,
                    'Link' => $this->DayLink('?startd=' . $ymd . '&end=' . $ymd),
                    'InMonth' => $m == $this->month,
                    'Past' => $m < date('m'),
                    'Today' => $ymd == date('Y-m-d'),
                    'HasEvent' => isset($eventDays[$m][$d]),
                    'Title' => $titles,
                    'EventID' => $ids,
                    'Colours' => $colours,
                    'Selected' => $selected,
                ]));
            }

            $weeks->push($days);
        }

        return $weeks;
    }

    public function Link($action = null)
    {
        return Controller::join_links($this->parent->Link(), $action);
    }

    public function DayLink($action = null)
    {
        $eventsPage = EventsPage::get()->first();
        if ($eventsPage) {
            return Controller::join_links($eventsPage->Link(), $action);
        }
    }

    public function setForcedCategories($forcedCategories)
    {
        $this->forcedCategories = $forcedCategories;
    }

    public function setWideCalendar()
    {
        $this->widecalendar = true;
    }

    public function getWideCalendar()
    {
        return $this->widecalendar;
    }
}
