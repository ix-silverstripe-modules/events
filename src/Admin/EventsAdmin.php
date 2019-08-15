<?php

namespace Internetrix\Events\Admin;

use Internetrix\Events\Controllers\CalendarEventActions_ItemRequest;
use Internetrix\Events\Model\EventCategory;
use Internetrix\Events\Pages\CalendarEvent;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * @package events
 * @author Internetrix
 */
class EventsAdmin extends ModelAdmin
{
    private static $title = 'Events';
    private static $menu_title = 'Events';
    private static $url_segment = 'events';
    private static $menu_icon_class = 'font-icon-p-event';

    private static $managed_models = [
        CalendarEvent::class,
        EventCategory::class,
    ];

    private static $model_importers = [];

    public function init()
    {
        parent::init();
    }

    public function getSearchContext()
    {
        $context = parent::getSearchContext();

        if ($this->modelClass == CalendarEvent::class) {
            $categories = EventCategory::get()->sort('Sort');
            $fields = $context->getFields();

            $fields->push(
                DropdownField::create('q[Category]', 'Category', $categories->map()->toArray())
                ->setHasEmptyDefault(true)
            );
            $fields->push(CheckboxField::create('q[UserSubmitted]', 'Submitted by a user?'));
        }

        return $context;
    }

    public function getList()
    {
        $list = parent::getList();

        if ($this->modelClass == CalendarEvent::class) {
            $params = $this->request->requestVar('q'); // use this to access search parameters

            $list = $list->filter(['End:GreaterThan' => date('Y-m-d H:i:s')]);
            $list = $list->leftJoin('CalendarEvent', '"SiteTree"."ID" = "EventsModelAdmin"."ID"', 'EventsModelAdmin');
            $list = $list->sort('"EventsModelAdmin"."Start"');

            if ($this->action != 'EditForm') { //only apply filters to the listing page
                if (isset($params['Category']) && $params['Category']) {
                    $list = $list->innerJoin('EventCategory_Events', '"EventCategory_Events"."CalendarEventID" = "CalendarEvent"."ID"');
                    $list = $list->filter('EventCategory_Events.EventCategoryID', $params['Category']);
                }

                if (isset($params['UserSubmitted']) && $params['UserSubmitted']) {
                    $list = $list->where('"CalendarEvent"."SubmitterFirstName" IS NOT NULL OR "CalendarEvent"."SubmitterSurname" IS NOT NULL OR "CalendarEvent"."SubmitterEmail" IS NOT NULL OR "CalendarEvent"."SubmitterPhoneNumber" IS NOT NULL');
                }
            }
        }

        return $list;
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        $gridField = $form->Fields()->fieldByName($this->sanitiseClassName($this->modelClass));
        if ($this->modelClass == EventCategory::class) {
            $gridField->getConfig()->addComponent(new GridFieldOrderableRows('Sort'));
        } elseif ($this->modelClass == CalendarEvent::class) {
            $gridField->setTitle('Upcoming Events');

            if ($gridFieldDetailForm = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class)) {
                $gridFieldDetailForm->setItemRequestClass(CalendarEventActions_ItemRequest::class);
            }
        }

        return $form;
    }
}
