<?php

namespace Internetrix\Events\Controllers;

use Internetrix\Events\Pages\CalendarEvent;
use Internetrix\Events\Pages\EventsPage;
use PageController;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Upload;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\PolymorphicHasManyList;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\UserForms\Model\EditableFormField\EditableFileField;
use SilverStripe\UserForms\Model\Submission\SubmittedForm;
use SilverStripe\UserForms\Model\UserDefinedForm;

class CalendarEventController extends PageController
{
    private static $allowed_actions = [
        'index',
        'RegistrationForm',
        'finished',
        'ics',
    ];

    public function init()
    {
        parent::init();
    }

    public function index()
    {
        return $this->customise(['Finished' => false]);
    }

    public function finished()
    {
        return $this->customise(['Finished' => true])->renderWith(['Internetrix/Events/Pages/Layout/CalendarEvent', 'Page']);
    }

    public function ics()
    {
        $timezone = date_default_timezone_get();

        $this->getResponse()->addHeader('Cache-Control', 'private');
        $this->getResponse()->addHeader('Content-Description', 'File Transfer');
        $this->getResponse()->addHeader('Content-Type', 'text/calendar');
        $this->getResponse()->addHeader('Content-Transfer-Encoding', 'binary');

        if (stristr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            $this->getResponse()->addHeader('Content-disposition', 'filename=event.ics; attachment;');
        } else {
            $this->getResponse()->addHeader('Content-disposition', 'attachment; filename=event.ics');
        }
        $result = trim(strip_tags($this->customise([
            'HOST' => 'IRXICS',
            'START' => $timezone . ':' . gmdate('Ymd\THis\Z', strtotime($this->obj('Start')->getValue())),
            'END' => $timezone . ':' . gmdate('Ymd\THis\Z', strtotime($this->obj('End')->getValue())),
            'URL' => $this->AbsoluteLink(),
            'SUMMARY' => $this->Title,
            'DESC' => $this->Content,
            'LOCATION' => $this->LoadAddress(),
            'NOW' => $timezone . ':' . gmdate('Ymd\THis\Z', time()),
            'ID' => $this->ID,
        ])->renderWith(['Internetrix/Events/ics'])));

        return $result;
    }

    public function escapeString($string)
    {
        return preg_replace('/([\,;])/', '\\\$1', $string);
    }

    public function ShareLinksEnabled()
    {
        return EventsPage::config()->enable_sharing;
    }

    public function BackLink()
    {
        $url = false;

        $session = $this->getRequest()->getSession();

        $value = $session->get('EventsOffset' . $this->ParentID);

        // check the referrer first. If they came from a filtered page, the back link needs to be formulated a little different
        $referer = $this->request->getHeaders();

        if (isset($referer['Referer'])) {
            $parseReferer = parse_url($referer['Referer']);

            if (isset($parseReferer['query'])) {
                // Get parent
                $parent = $this->Parent;
                $url = $parent->Link('?' . $parseReferer['query'] . "&start=$value" . '#' . $this->URLSegment);
            }
        }

        if (!$url && $value) {
            // Get parent
            $parent = $this->Parent;
            $url = $parent->Link("?start=$value" . '#' . $this->URLSegment);
        }

        if (!$url) {
            $page = $this->Parent();
            $url = $page ? $page->Link('#' . $this->URLSegment) : false;
        }

        return $url;
    }

    public function PrevNextPage($mode = 'next')
    {
        if ($mode == 'next') {
            $direction = 'Start:GreaterThan';
            $sort = 'Start ASC';
        } elseif ($mode == 'prev') {
            $direction = 'Start:LessThan';
            $sort = 'Start DESC';
        } else {
            return false;
        }

        // Filter out events that start before this
        $PrevNext = CalendarEvent::get()->filter(['Start:GreaterThan' => date('Y-m-d H:i:s')]);

        $PrevNext = $PrevNext->filter([
                $direction => $this->Start,
            ])
            ->sort($sort)
            ->first();

        if ($PrevNext) {
            return $PrevNext;
        }
    }

    private function getRegistrationFormFields()
    {
        $fields = new FieldList();

        if ($this->Fields()) {
            foreach ($this->Fields() as $editableField) {
                // get the raw form field from the editable version
                $field = $editableField->getFormField();
                if (!$field) {
                    break;
                }

                // set the error / formatting messages
                $field->setCustomValidationMessage($editableField->getErrorMessage());

                // set the right title on this field
                if ($right = $editableField->RightTitle) {
                    $field->setRightTitle($right);
                }

                // if this field is required add some
                if ($editableField->Required) {
                    $field->addExtraClass('required');

                    if ($identifier = UserDefinedForm::config()->required_identifier) {
                        $title = $field->Title() . " <span class='required-identifier'>" . $identifier . '</span>';
                        $field->setTitle($title);
                    }
                }
                // if this field has an extra class
                if ($editableField->ExtraClass) {
                    $field->addExtraClass(Convert::raw2att(
                        $editableField->ExtraClass
                    ));
                }

                $fields->push($field);
            }
        }
        $this->extend('updateRegistrationFormFields', $fields);

        return $fields;
    }

    private function getRequiredFields(PolymorphicHasManyList $fields)
    {
        $requiredfields = [];

        foreach ($fields as $editableField) {
            if ($editableField->Required) {
                $requiredfields[] = $editableField->Name;
            }
        }

        return $requiredfields;
    }

    public function RegistrationForm()
    {
        $fields = $this->getRegistrationFormFields();

        if ($this->EnableRegistrationPage && EventsPage::config()->enable_event_registration && $fields->count() > 1) {
            $required = $this->getRequiredFields($this->Fields());
            $validator = RequiredFields::create($required);

            $actions = FieldList::create(
                FormAction::create('doRegister', 'Submit')
            );

            $this->extend('RegistrationFormFields', $fields);
            $this->extend('RegistrationFormActions', $actions);
            $this->extend('RegistrationFormValidator', $validator);

            return Form::create($this, 'RegistrationForm', $fields, $actions, $validator);
        }
    }

    public function doRegister($data, $form)
    {
        $session = $this->getRequest()->getSession();

        $session->set("FormInfo.{$form->FormName()}.data", $data);
        $session->clear("FormInfo.{$form->FormName()}.errors");

        // Store the submitted form
        $submittedForm = SubmittedForm::create();
        $submittedForm->SubmittedByID = ($id = Member::currentUserID()) ? $id : 0;
        $submittedForm->ParentID = $this->ID;
        $submittedForm->write();

        $submittedFields = new ArrayList();

        foreach ($this->Fields() as $field) {
            $submittedField = $field->getSubmittedFormField();
            $submittedField->ParentID = $submittedForm->ID;
            $submittedField->Name = $field->Name;
            $submittedField->Title = $field->getField('Title');

            // save the value from the data
            if ($field->hasMethod('getValueFromData')) {
                $submittedField->Value = $field->getValueFromData($data);
            } else {
                if (isset($data[$field->Name])) {
                    $submittedField->Value = $data[$field->Name];
                }
            }

            if (!empty($data[$field->Name])) {
                if (in_array(EditableFileField::class, $field->getClassAncestry())) {
                    if (isset($_FILES[$field->Name])) {
                        $foldername = $field->getFormField()->getFolderName();

                        // create the file from post data
                        $upload = new Upload();
                        $file = new File();
                        $file->ShowInSearch = 0;

                        try {
                            $upload->loadIntoFile($_FILES[$field->Name], $file, $foldername);
                        } catch (ValidationException $e) {
                            $validationResult = $e->getResult();
                            $form->addErrorMessage($field->Name, $validationResult->message(), 'bad');
                            Controller::curr()->redirectBack();

                            return;
                        }

                        // write file to form field
                        $submittedField->UploadedFileID = $file->ID;
                    }
                }
            }

            $submittedField->extend('onSubmittedFormField', $field);

            $submittedField->write();

            $submittedFields->push($submittedField);
        }

        $this->redirect($this->Link('finished'));
    }
}
