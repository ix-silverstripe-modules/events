<?php

namespace Internetrix\Events\Controllers;

use Internetrix\Events\Pages\CalendarEvent;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FormAction;
use SilverStripe\Versioned\VersionedGridFieldItemRequest;

class CalendarEventActions_ItemRequest extends VersionedGridFieldItemRequest
{
    protected function getFormActions()
    {
        $calendarEvent = $this->getRecord();
        $actions = parent::getFormActions();
        if ($calendarEvent->isInDB()) {
            $actions->insertAfter(
                FormAction::create('duplicateCalendarEvent', 'Duplicate event')
                    ->addExtraClass('btn-secondary font-icon-plus-circled')
                    ->setUseButtonTag(true)
                    ->setDescription('Duplicate event'),
                'action_doPublish'
            );
        }

        return $actions;
    }

    public function duplicateCalendarEvent($data, $form)
    {
        $calendarEvent = $this->getRecord();

        if ($calendarEvent->ClassName != CalendarEvent::class) {
            return $this->httpError(400);
        }

        $id = $calendarEvent->ID;

        if (!$calendarEvent || !$calendarEvent->exists()) {
            return $this->httpError(400);
        }

        $clonedCalendarEvent = $calendarEvent->duplicate(true, [
            'Categories',
        ]);

        $clonedCalendarEventEditLink = Controller::join_links($this->gridField->Link('item'), $clonedCalendarEvent->ID, 'edit');
        $controller = $this->getToplevelController();

        $this->setFormMessage($form, 'Calendar event successfully duplicated');

        return $controller->redirect($clonedCalendarEventEditLink, 200);
    }
}
