<?php

namespace Internetrix\Events\Tasks;

use Internetrix\Events\Model\EventCategory;
use Internetrix\Events\Pages\CalendarEvent;
use Internetrix\Events\Pages\EventsPage;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\Debug;

class GenerateCalendarEvent extends BuildTask
{
    protected $title = 'Generate Dummy Calendar Events';

    protected $description = 'Generate calendar events for testing';

    public function run($request)
    {
        $amount = 20; // amount of articles to generate

        $text = "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed nec mi urna. Proin nunc purus, porta et malesuada non, ullamcorper eget nisl. Nam pulvinar accumsan lobortis. Donec in lacinia felis. Praesent dictum nisi non libero porta, pharetra ullamcorper tellus tincidunt. In pellentesque tellus tincidunt, egestas libero sit amet, ullamcorper dolor. Maecenas lacinia id dolor sit amet cursus. Aliquam erat volutpat. Ut ut luctus dolor. Cras sed diam cursus, dictum tellus ut, vehicula tellus. Donec gravida tortor a aliquet lobortis. Curabitur pellentesque iaculis faucibus.</p>
<p>Mauris ut turpis interdum, porta enim et, tristique sem. Quisque facilisis consectetur justo, sit amet ornare sem tempor eget. Aliquam dapibus libero mauris, vel consectetur sapien volutpat ornare. Morbi ac nisi nec nisi consequat sollicitudin eget vel purus. Phasellus ut lacus posuere, consequat turpis in, pretium felis. Phasellus tellus quam, consequat in metus vel, venenatis pulvinar arcu. Praesent suscipit tortor vel justo elementum pretium. Quisque commodo porta cursus. Fusce eget vulputate erat. Mauris iaculis auctor augue, ac semper eros bibendum ac.</p>";

        $eventDuration = ["+2 hours 2 minutes", "+1 day 2 hours", "+1 week 4 hours", "+30 minutes", "+5 hours 44 minutes", "+ 1 hour 12 minutes", "+12 hours", "+ 1 month", "+6 hours 22 minutes", "+15 minutes"];
        $eventDurationCount = count($eventDuration);
        // holder
        $eventsPage = EventsPage::get()->last();

        // categories
        $categories = EventCategory::get()->map()->toArray();

        if (!$eventsPage) {
            echo "Cannot run without events page";
            return;
        }

        for ($x = 1; $x <= $amount; $x++) {
            echo "Generating event $x<br />";
            $rand_dur = rand(0, $eventDurationCount - 1);
            $rand_category = rand(0, count($categories));
            $rand_category2 = rand(0, count($categories));

            $startDate = rand(strtotime('-1 year', time()), strtotime('+1 year', time()));
            $endDate = strtotime($eventDuration[$rand_dur], $startDate);
            Debug::show($eventDuration[$rand_dur]);

            $calendarEvent = CalendarEvent::create();
            $calendarEvent->Title = "event $x";
            $calendarEvent->ParentID = $eventsPage->ID;
            $calendarEvent->Start = date('d/m/Y h:i A', $startDate);
            $calendarEvent->End = date('d/m/Y h:i A', $endDate);
            $calendarEvent->Content = $text;

            if ($rand_category) {
                $cattoadd = EventCategory::get_by_id("EventCategory", $rand_category);
                $calendarEvent->Categories()->add($cattoadd);
            }

            if ($rand_category2 && ($rand_category != $rand_category2)) {
                $cattoadd2 = EventCategory::get_by_id("EventCategory", $rand_category2);
                $calendarEvent->Categories()->add($cattoadd2);
            }

            $calendarEvent->write();
            //$calendarEvent->publish('Stage', 'Live');
        }

        echo "<br />Generated $amount events";
    }
}
