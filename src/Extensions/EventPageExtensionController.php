<?php

namespace Internetrix\Events\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DB;
use SilverStripe\View\Requirements;

class EventPageExtensionController extends Extension
{
    public function onAfterInit()
    {
        Requirements::css('internetrix/silverstripe-events:thirdparty/qtip/jquery.qtip-2.0.0.css');

        Requirements::javascript('//code.jquery.com/jquery-3.3.1.min.js');
        Requirements::javascript('internetrix/silverstripe-events:javascript/EventsPageCalendar.js');
        Requirements::javascript('internetrix/silverstripe-events:thirdparty/qtip/jquery.qtip-2.0.0.min.js');
    }

    /**
     * @return Form
     */
    public function MonthForm()
    {
        $months = array_flip(range(1, 12));

        foreach ($months as $num => &$name) {
            $name = date('F', mktime(null, null, null, $num, 1));
        }

        $monthField = DropdownField::create('Month', '', $months)
            ->setEmptyString('Month...')
            ->addExtraClass('dropdown');
        $yearField = DropdownField::create('Year', '', $this->getYearRange())
            ->setEmptyString('Year...')
            ->addExtraClass('dropdown');
        $categoryField = SelectAllCheckboxSetField::create('Categories', 'Categories', EventCategory::get(), $this->getCalendarCategories());

        $fields = FieldList::create($monthField, $yearField, $categoryField);

        $formAction = FormAction::create('search', 'Go');
        $formAction->addExtraClass('events-submit');

        $actions = FieldList::create($formAction);

        $form = Form::create($this->owner, 'MonthForm', $fields, $actions, new RequiredFields('Year'));
        $form->disableSecurityToken();
        $form->setFormMethod('GET');

        $eventsPage = EventsPage::get()->first();
        if ($eventsPage) {
            $form->setFormAction($eventsPage->Link('search'));
        }

        $session = $this->getRequest()->getSession();


        if ($data = $session->get('MonthForm')) {
            $session->clear('MonthForm');
            $form->loadDataFrom($data);
        }

        $this->owner->extend('updateMonthForm', $form);

        return $form;
    }

    /**
     * Returns an array of years that encompasses all events as well as the
     * current year.
     *
     * @return array
     */
    protected function getYearRange()
    {
        $conn = DB::get_conn();
        $year = date('Y');

        $min = $conn->formattedDatetimeClause('MIN("Start")', '%Y');
        $max = $conn->formattedDatetimeClause('MAX("End")', '%Y');
        $range = DB::query("SELECT $min AS \"min\", $max AS \"max\" FROM \"CalendarEvent\"")->record();

        $min = min($range['min'], $year - 1);
        $max = max($range['max'], $year + 1);
        return ArrayLib::valuekey(range($max, $min));
    }

    public function getCalendarCategories()
    {
        $forced = $this->owner->ForcedCalendarCategories();
        if ($forced->Count() > 0) {
            return $forced->map('ID', 'ID')->toArray();
        }
    }
}
