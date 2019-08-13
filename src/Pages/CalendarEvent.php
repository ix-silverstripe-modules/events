<?php

namespace Internetrix\Events\Pages;

use Colymba\BulkManager\BulkManager;
use Internetrix\Events\Controllers\CalendarEventController;
use Internetrix\Events\Model\EventCategory;
use Internetrix\Events\Pages\EventsPage;
use Page;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Upload;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\GridField\GridState_Component;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\UserForms\Extension\UserFormFieldEditorExtension;
use SilverStripe\UserForms\Form\UserFormsGridFieldFilterHeader;
use SilverStripe\UserForms\Model\Submission\SubmittedForm;
use SilverStripe\Versioned\Versioned;
use Symbiote\Addressable\Addressable;
use Symbiote\Addressable\Geocodable;

/**
 * An event that is displayed on the events page.
 *
 * @package irxeventcalendar
 * @author Internetrix
 */
class CalendarEvent extends Page
{
    private static $icon_class = 'font-icon-p-event';

    private static $description = 'Page that displays a single event.';

    private static $singular_name = 'Event';

    private static $plural_name = 'Events';

    private static $table_name = 'CalendarEvent';

    private static $controller_name = CalendarEventController::class;

    private static $db = [
        'Title' => 'Varchar(255)',
        'Start' => 'Datetime',
        'End' => 'Datetime',
        'Cost' => 'Varchar(255)',
        'Website' => 'Varchar(255)',
        'Email' => 'Varchar(255)',
        'Contact' => 'Varchar(255)',
        'Phone' => 'Varchar(255)',
        'LegacyID' => 'Int',
        'LegacyLocation' => 'Text',
        'LegacyFileName' => 'Text',
        'LegacyCategoryID' => 'Int',
        'SubmitterFirstName' => 'Varchar(255)',
        'SubmitterSurname' => 'Varchar(255)',
        'SubmitterEmail' => 'Varchar(255)',
        'SubmitterPhoneNumber' => 'Varchar(255)',
        'HideStartAndEndTimes' => 'Boolean',
        'HideDatePosted' => 'Boolean',
        'EnableRegistrationPage' => 'Boolean',
    ];

    private static $default_sort = 'Start ASC';

    private static $defaults = [
        'ShowListingImageOnPage' => true,
        'ShowShareIcons' => true,
        'HideDatePosted' => true,
    ];

    private static $has_one = [
        'CreatedBy' => Member::class,
        'ListingImage' => Image::class,
    ];

    private static $owns = [
        'ListingImage',
    ];

    private static $has_many = [
        'Submissions' => SubmittedForm::class,
    ];

    private static $belongs_many_many = [
        'Categories' => EventCategory::class,
    ];

    private static $extensions = [
        Addressable::class,
        Geocodable::class,
        UserFormFieldEditorExtension::class,
    ];

    private static $searchable_fields = [
        'Title' => ['filter' => 'PartialMatchFilter', 'title' => 'Title'],
        'Content' => ['filter' => 'PartialMatchFilter', 'title' => 'Content'],
    ];

    private static $summary_fields = [
        'Title',
        'Status',
        'Start.Nice',
        'End.Nice',
        'DisplayCategories',
        'ListingImage.CMSThumbnail',
    ];

    private static $field_labels = [
        'Start.Nice' => 'Starts',
        'End.Nice' => 'Ends',
        'DisplayCategories' => 'Categories',
        'ListingImage.CMSThumbnail' => 'Image',
    ];

    public function populateDefaults()
    {
        parent::populateDefaults();

        $this->setField('Start', date('Y-m-d', strtotime('+1day')));
        $this->setField('End', date('Y-m-d', strtotime('+1day')));
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('FormFields');

        // Makes sure the Listing Summary Toggle is present before
        $configBefore = EventsPage::config()->event_fields_before;
        $configBefore = ($configBefore ? $configBefore : 'Content');

        $putBefore = ($fields->fieldByName('Root.Main.ListingSummaryToggle') ? 'ListingSummaryToggle' : $configBefore);

        // If an image has not been set, open the toggle field to remind user
        if ($this->ListingImageID == 0) {
            $toggle = $fields->fieldByName('Root.Main.ListingSummaryToggle');
            $toggle->setStartClosed(false);
        }

        $fields->addFieldToTab('Root.Main', UploadField::create('ListingImage', 'Listing Image')
            ->addExtraClass('withmargin')
            ->setFolderName(Upload::config()->uploads_folder . '/Events'), 'ShowListingImageOnPage');

        $start = DatetimeField::create('Start', 'Start');

        $end = DatetimeField::create('End', 'End');

        $fields->addFieldToTab('Root.Main', $start, $configBefore);
        $fields->addFieldToTab('Root.Main', $end, $configBefore);

        $fields->addFieldToTab('Root.Main', CheckboxField::create('HideStartAndEndTimes', 'Hide start and end times'), $configBefore);
        $fields->addFieldToTab('Root.Main', CheckboxField::create('HideDatePosted', 'Hide date posted'), $configBefore);

        if (EventsPage::config()->enable_event_registration) {
            $fields->addFieldToTab('Root.Main', CheckboxField::create('EnableRegistrationPage', 'Enable visitors to register for event?'), $configBefore);
        }

        $fields->addFieldToTab('Root.Main', TextField::create('Cost', 'Cost (Leave it blank if cost is free)'), $configBefore);

        $fields->addFieldToTab('Root.Main', $cats = ListboxField::create('Categories', 'Categories', EventCategory::get()->map()->toArray()), $configBefore);

        $fields->addFieldToTab('Root.Main', $contactToggle = ToggleCompositeField::create('ContactToggle', 'Contact Details', [
            TextField::create('Website', 'Website'),
            TextField::create('Email', 'Email'),
            TextField::create('Contact', 'Contact'),
            TextField::create('Phone', 'Phone'),
        ]), $configBefore);

        $address = $fields->findOrMakeTab('Root.Address');
        $fields->removeByName('Address');
        $address->removeByName('AddressHeader');
        $fields->addFieldToTab('Root.Main', $addressToggle = ToggleCompositeField::create('AddressToggle', 'Address', $address), $configBefore);

        if (!$this->ID) {
            $contactToggle->setStartClosed(false);
            $addressToggle->setStartClosed(false);
        }

        if ($this->SubmitterFirstName || $this->SubmitterSurname || $this->SubmitterEmail || $this->SubmitterPhoneNumber) {
            $fields->addFieldsToTab('Root.SubmittedBy', [
                ReadonlyField::create('SubmitterFirstName', 'First Name'),
                ReadonlyField::create('SubmitterSurname', 'Surname'),
                ReadonlyField::create('SubmitterEmail', Email::class),
                ReadonlyField::create('SubmitterPhoneNumber', 'Phone Number'),
            ]);
        }

        // Show this tab only if the Registration Page has been enabled
        if ($this->EnableRegistrationPage) {
            $fields->addFieldToTab('Root.RegistrationForm', LiteralField::create('FE', '<div class="form-group field stacked"><div class="form__field-holder">Note: Full Name, Email and Phone are required fields. If they are deleted or modified, they will be recreated automatically.</div></div>'));
            $fields->addFieldToTab('Root.RegistrationForm', $this->getFieldEditorGrid());

            // view the submissions
            $submissions = GridField::create(
                'Submissions',
                _t('UserDefinedForm.SUBMISSIONS', 'Submissions'),
                $this->Submissions()->sort('Created', 'DESC')
            );

            // make sure a numeric not a empty string is checked against this int column for SQL server
            $parentID = (!empty($this->ID)) ? $this->ID : 0;

            // get a list of all field names and values used for print and export CSV views of the GridField below.
            $columnSQL = <<<SQL
SELECT "Name", "Title"
FROM "SubmittedFormField"
LEFT JOIN "SubmittedForm" ON "SubmittedForm"."ID" = "SubmittedFormField"."ParentID"
WHERE "SubmittedForm"."ParentID" = '$parentID'
ORDER BY "Title" ASC
SQL;
            $columns = DB::query($columnSQL)->map();

            $config = new GridFieldConfig();
            $config->addComponent(new GridFieldToolbarHeader());
            $config->addComponent($sort = new GridFieldSortableHeader());
            $config->addComponent($filter = new UserFormsGridFieldFilterHeader());
            $config->addComponent(new GridFieldDataColumns());
            $config->addComponent(new GridFieldEditButton());
            $config->addComponent(new GridState_Component());
            $config->addComponent(new GridFieldDeleteAction());
            $config->addComponent(new GridFieldPageCount('toolbar-header-right'));
            $config->addComponent($pagination = new GridFieldPaginator(25));
            $config->addComponent(new GridFieldDetailForm());
            $config->addComponent($export = new GridFieldExportButton());
            $config->addComponent($print = new GridFieldPrintButton());

            /*
             * Support for {@link https://github.com/colymba/GridFieldBulkEditingTools}
            */
            if (class_exists(BulkManager::class)) {
                $config->addComponent(new BulkManager());
            }

            $sort->setThrowExceptionOnBadDataType(false);
            $filter->setThrowExceptionOnBadDataType(false);
            $pagination->setThrowExceptionOnBadDataType(false);

            // attach every column to the print view form
            $columns['Created'] = 'Created';
            $filter->setColumns($columns);

            // print configuration

            $print->setPrintHasHeader(true);
            $print->setPrintColumns($columns);

            // export configuration
            $export->setCsvHasHeader(true);
            $export->setExportColumns($columns);

            $submissions->setConfig($config);
            $fields->addFieldToTab('Root.Registrations', $submissions);
        }

        $this->extend('updateEventCMSFields', $fields);

        return $fields;
    }

    public function onAfterPublish()
    {

        /*****************************************belong many many relationships**********************************/
        $categories = $this->Categories();
        foreach ($categories as $category) {
            $categories->add($category, ['Approved' => 1]);
        }
        /**********************************************************************************************************/
    }

    public function getCMSValidator()
    {
        return RequiredFields::create('Title', 'Start', 'End');
    }

    public function duplicate($doWrite = true, $relations = null)
    {
        $clonedCalendarEvent = parent::duplicate($doWrite, $relations);
        $clonedCalendarEvent->Created = date('Y-m-d H:i:s');
        $clonedCalendarEvent->Title = 'Copy of ' . $clonedCalendarEvent->Title;
        $clonedCalendarEvent->write();

        return $clonedCalendarEvent;
    }

    public function getCMSActions()
    {
        $fields = parent::getCMSActions();

        if ($this->isInDB()) {
            $fields->fieldByName('MajorActions')->push(
                FormAction::create('doDuplicateCalendarEvent', 'Duplicate event')
                    ->addExtraClass('btn-secondary font-icon-plus-circled')
                    ->setUseButtonTag(true)
                    ->setDescription('Duplicate event')
            );
        }

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->ParentID) {
            $parent = EventsPage::get()->first();
            if (!$parent) {
                $parent = EventsPage::create();
                $parent->Title = 'Events';
                $parent->URLSegment = 'events';
                $parent->write();
                $parent->publish('Stage', 'Live');
            }
            $this->setField('ParentID', $parent->ID);
        }

        $checkSite = $this->getField('Website');
        if (!empty($checkSite) && strpos($checkSite, 'http') !== 0) {
            $this->setField('Website', 'http://' . $checkSite);
        }
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        // If a submission form is desired, we need to ensure it has the default fields
        // If no fields exist do not call as UserFormFieldEditorExtension needs to first add a EditableFormStep
        if ($this->EnableRegistrationPage && $this->Fields()->count()) {
            $forcedFields = EventsPage::config()->required_event_fields;

            // Only attempt to create the fields if the config exists
            if ($forcedFields) {
                foreach ($forcedFields as $fieldname => $field) {
                    $title = $field['Title'];
                    $type = $field['Type'];

                    // Search current fields by name
                    $checkFields = $this->Fields()->filter(['Title' => $title]);

                    // Don't create fields that already exist
                    if ($checkFields->Count() == 0) {
                        $fieldObj = new $type();
                        $fieldObj->ParentID = $this->ID;
                        $fieldObj->ParentClass = $this->ClassName;
                        $fieldObj->Title = $title;
                        $fieldObj->Required = 1;
                        $fieldObj->write();
                        $fieldObj->publish('Stage', 'Live');
                    }
                }
            }
        }
    }

    public function googleMapAddress()
    {
        $fullAddress = $this->Address;
        $fullAddress .= ' ' . $this->Suburb;
        $fullAddress .= ' ' . $this->State;
        $fullAddress .= ' ' . $this->Postcode;

        return urlencode($fullAddress);
    }

    public function LoadDate()
    {
        if ($this->Start && $this->End) {
            $startSTR = strtotime($this->Start);
            $start = date('jS M Y', $startSTR);

            $endSTR = strtotime($this->End);
            $end = date('jS M Y', $endSTR);

            if ($start == $end) {
                return $start;
            }

            return $start . ' - ' . $end;
        }

        if ($this->Start && !$this->End) {
            $startSTR = strtotime($this->Start);
            $start = date('jS M Y', $startSTR);

            return $start;
        }

        return false;
    }

    public function LoadAddress()
    {
        if ($this->Address && $this->Suburb && $this->State) {
            return $this->Address . ', ' . $this->Suburb . ', ' . $this->State . ' ' . $this->Postcode;
        }

        return false;
    }

    public function Status()
    {
        if ($this->isNew()) {
            return 'New Page';
        } elseif ($this->isPublished()) {
            return 'Published';
        }

        return 'Unpublished';
    }

    public function Categories()
    {
        $categories = $this->getManyManyComponents('Categories');
        if (Versioned::get_stage() == 'Live') {
            $categories = $categories->filter('Approved', true);
        }

        return $categories;
    }

    public function DisplayCategories()
    {
        $categories = $this->Categories();
        if ($categories) {
            return implode(', ', $categories->map()->toArray());
        }
    }

    public function getStartMonth()
    {
        return date('F', strtotime($this->Start));
    }

    public function getStartYear()
    {
        return date('Y', strtotime($this->Start));
    }

    public function getDateMonth()
    {
        return date('F', strtotime($this->Start));
    }

    public function OneDay()
    {
        return date('d/m/Y', strtotime($this->Start)) == date('d/m/Y', strtotime($this->End));
    }

    public function getLeadingImage()
    {
        if ($this->ListingImageID) {
            return $this->ListingImage();
        } elseif ($this->Categories()->first() && $this->Categories()->first()->ImageID) {
            return $this->Categories()->first()->Image();
        }

        return false;
    }
}
