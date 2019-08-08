<?php

namespace Internetrix\Events\Admin;

use Internetrix\Events\Controllers\CalendarEventActions_ItemRequest;
use Internetrix\Events\Pages\CalendarEvent;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;

class ArchivedEventsAdmin extends ModelAdmin
{
    private static $title = 'Archived Events';
    private static $menu_title = 'Archived';
    private static $url_segment = 'archivedevents';
    private static $menu_icon_class = 'font-icon-p-archive';

    private static $managed_models = [
        CalendarEvent::class,
    ];

    private static $model_importers = [];

    private static $menu_priority = -0.6;

    public function getList()
    {
        $list = parent::getList();
        $list = $list->filter(['End:LessThan' => date('Y-m-d H:i:s')]);
        $list = $list->sort('Start DESC');
        return $list;
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $gridField = $form->Fields()->fieldByName($this->sanitiseClassName($this->modelClass));

        if ($this->modelClass == CalendarEvent::class) {
            $gridField->getConfig()->removeComponentsByType(GridFieldAddNewButton::class);
            $gridField->setTitle('Archived Events');

            if ($gridFieldDetailForm = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class)) {
                $gridFieldDetailForm->setItemRequestClass(CalendarEventActions_ItemRequest::class);
            }
        }

        return $form;
    }
}
