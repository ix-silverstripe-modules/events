<?php

namespace Internetrix\Events\Extensions;

use SilverStripe\View\Requirements;
use SilverStripe\Core\Extension;


class EventPageExtensionController extends Extension
{
    public function onAfterInit()
    {
        Requirements::css('internetrix/events:thirdparty/qtip/jquery.qtip-2.0.0.css');

        Requirements::javascript('//code.jquery.com/jquery-3.3.1.min.js');
        Requirements::javascript('internetrix/events:javascript/EventsPageCalendar.js');
        Requirements::javascript('internetrix/events:thirdparty/qtip/jquery.qtip-2.0.0.min.js');
    }
}
