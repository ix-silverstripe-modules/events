<?php

namespace Internetrix\Events\Pages;

use Internetrix\Events\Controllers\EventsPageController;
use Internetrix\Events\Pages\CalendarEvent;
use Page;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Upload;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLEditor\HtmlEditorField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\GroupedList;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ArrayData;

/**
 * A page that lists all events and allows users to view details about them.
 *
 * @package event
 * @author Internetrix
 */
class EventsPage extends Page
{
    private static $icon_class = 'font-icon-p-event-alt';

    private static $description = 'Page that lists all upcoming Events for selected calendars.';

    private static $singular_name = 'Events Holder';

    private static $plural_name = 'Events Holders';

    private static $table_name = 'EventsPage';

    private static $controller_name = EventsPageController::class;

    private static $db = [
        'PaginationLimit' => 'Int',
        'ViewMoreText' => 'Varchar(255)',
        'SearchEventsPlaceholder' => 'Varchar(255)',
        'EventsListTitle' => 'Varchar(255)',
        'NoEventsText' => 'HTMLText',
        'FinishedMessage' => 'HTMLText',
        'HideSearchBox' => 'Boolean',
        'AddEventEmailTo' => 'Varchar(255)',
        'AddEventEmailFrom' => 'Varchar(255)',
        'PrintTitle' => 'Varchar(255)',
    ];

    private static $defaults = [
        'PaginationLimit' => 5,
        'SearchEventsPlaceholder' => 'Search Events...',
        'ViewMoreText' => 'View Event',
        'EventsListTitle' => 'Viewing All',
        'NoEventsText' => '<p>Sorry there are no events</p>',
        'FinishedMessage' => '<p>Your event has been submitted and is under review.</p>',
        'AddEventEmailTo' => '',
        'AddEventEmailFrom' => '',
        'PrintTitle' => 'Events Calendar for',
    ];

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        if (!EventsPage::get()->First()) {
            $page = EventsPage::create();
            $page->Title = 'Events';
            $page->URLSegment = 'events';
            $page->PaginationLimit = 5;
            $page->SearchEventsPlaceholder = 'Search Events...';
            $page->ViewMoreText = 'View Event';
            $page->EventsListTitle = 'Viewing All';
            $page->NoEventsText = '<p>Sorry there are no events</p>';
            $page->FinishedMessage = '<p>Your event has been submitted and is under review.</p>';
            $page->AddEventEmailTo = '';
            $page->AddEventEmailFrom = '';
            $page->PrintTitle = 'Events Calendar for';
            $page->write();
        }
    }

    public function canCreate($member = null, $context = [])
    {
        $eventsPage = EventsPage::get()->first();
        if ($eventsPage) {
            return false;
        }

        return parent::canCreate($member, $context);
    }

    public function getCMSFields()
    {
        $self = $this;

        $this->beforeUpdateCMSFields(function ($fields) use ($self) {
            // Makes sure the Listing Summary Toggle is present before
            $configBefore = EventsPage::config()->event_fields_before;
            $configBefore = ($configBefore ? $configBefore : 'Content');

            $putBefore = ($fields->fieldByName('Root.Main.ListingSummaryToggle') ? 'ListingSummaryToggle' : $configBefore);

            $fields->addFieldToTab('Root.Main', NumericField::create('PaginationLimit', 'Pagination Limit'), $putBefore);
            $fields->addFieldToTab('Root.Main', TextField::create('ViewMoreText', 'View More Text'), $putBefore);
            $fields->addFieldToTab('Root.Main', TextField::create('SearchEventsPlaceholder', 'Search Events Placeholder'), $putBefore);
            $fields->addFieldToTab('Root.Main', TextField::create('EventsListTitle', 'Events List Title'), $putBefore);
            $fields->addFieldToTab('Root.Main', TextField::create('PrintTitle', 'Print Title'), $putBefore);
            $fields->addFieldToTab('Root.Main', CheckboxField::create('HideSearchBox', 'Hide  the search box?'), $putBefore);
            $fields->addFieldToTab('Root', Tab::create('Messages', 'Messages & Emails'));
            $fields->addFieldToTab('Root.Messages', HTMLEditorField::create('NoEventsText', 'No Events Text')->setRows(10)->addExtraClass('withmargin'));
            $fields->addFieldToTab('Root.Messages', HTMLEditorField::create('FinishedMessage', 'Message after adding an event from the website')->setRows(10)->addExtraClass('withmargin'));
            $fields->addFieldToTab('Root.Messages', TextField::create('AddEventEmailTo', '"Add event" email goes to?'));
            $fields->addFieldToTab('Root.Messages', TextField::create('AddEventEmailFrom', '"Add event" email comes from?'));
        });

        $fields = parent::getCMSFields();

        $this->extend('updateEventsPageCMSFields', $fields);

        return $fields;
    }

    public function MenuYears()
    {
        $set = ArrayList::create();

        $year = DB::get_conn()->formattedDatetimeClause('"Start"', '%Y');

        $query = SQLSelect::create();
        $query->addFrom('"CalendarEvent"');
        $query->addLeftJoin(SiteTree::class, '"SiteTree"."ID" = "CalendarEvent"."ID"');
        $query->setGroupBy('"tDate"');
        $query->setOrderBy('"tDate" DESC');

        // Modfiy select to add subsite in if it's installed
        if (class_exists('Subsite')) {
            $query->setSelect("$year tDate, \"SiteTree\".\"SubsiteID\"");
            $query->setWhere('"SiteTree"."SubsiteID" = ' . Subsite::currentSubsiteID());
        } else {
            $query->setSelect("$year tDate");
        }

        $years = $query->execute()->column();

        if (!in_array(date('Y'), $years)) {
            array_unshift($years, date('Y'));
        }

        $selectedYear = Controller::curr()->getRequest()->param('ID');

        foreach ($years as $year) {
            $set->push(ArrayData::create([
                'Title' => $year,
                'MenuTitle' => $year,
                'Link' => $this->Link('archive/' . $year . '/'),
                'LinkingMode' => ($selectedYear && ($selectedYear == $year)) ? 'current' : 'section',
            ]));
        }

        $this->extend('updateEvenysPageMenuYears', $set);

        return $set;
    }

    public function Children()
    {
        $children = parent::Children();

        foreach ($children as $c) {
            if ($c->ClassName == CalendarEvent::class) {
                $children->remove($c);
            }
        }

        $this->extend('updateEventsPageChildren', $children);

        return $children;
    }

    public function getLumberjackTitle()
    {
        return 'Events';
    }
}
