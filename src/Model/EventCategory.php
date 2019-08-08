<?php

namespace Internetrix\Events\Model;

use Internetrix\Events\Pages\CalendarEvent;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Upload;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;
use SilverStripe\View\Parsers\URLSegmentFilter;
use TractorCow\Colorpicker\Color;
use TractorCow\Colorpicker\Forms\ColorField;

class EventCategory extends DataObject
{
    private static $default_sort = 'Sort';

    private static $table_name = 'EventCategory';

    private static $db = [
        'Title' => 'Varchar(100)',
        'URLSegment' => 'Varchar(255)',
        'Colour' => Color::class,
        'Sort' => 'Int',
    ];

    private static $has_one = [
        'Image' => Image::class,
    ];

    private static $owns = [
        'Image',
    ];

    private static $many_many = [
        'Events' => CalendarEvent::class,
    ];

    private static $many_many_extraFields = [
        'Events' => [
            'Approved' => 'Int',
        ]
    ];

    private static $searchable_fields = [
        'Title',
    ];

    private static $summary_fields = [
        'ColourBlock' => 'Colour',
        'Title' => 'Title',
        'NumberOfEvents' => 'Number of Events',
        'Image.CMSThumbnail' => 'Image',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Sort');
        $fields->removeByName('URLSegment');
        $fields->removeByName('Pages');
        $fields->removeByName('Events');

        $fields->addFieldToTab('Root.Main', ColorField::create('Colour', 'Colour'));


        $fields->addFieldToTab(
            'Root.Main',
            UploadField::create('Image')
                ->setFolderName(Upload::config()->uploads_folder . '/event-categories')
                ->addExtraClass('withmargin')
                ->setRightTitle('If an event does not have a listing image, it will source its listing image from its first category')
        );

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->isChanged('Title')) {
            $filter = URLSegmentFilter::create();
            $this->URLSegment = $filter->filter($this->Title);

            // Ensure that this object has a non-conflicting URLSegment value.
            $count = 2;
            while (!$this->validURLSegment()) {
                $this->URLSegment = preg_replace('/-[0-9]+$/', null, $this->URLSegment) . '-' . $count;
                $count++;
            }
        }
    }

    public function validURLSegment()
    {
        $segment = Convert::raw2sql($this->URLSegment);
        $existingCategory = EventCategory::get()->filter('URLSegment', $segment)->first();

        return !($existingCategory);
    }

    public function ColourBlock()
    {
        $html = DBHTMLVarchar::create();
        $html->setValue("<div style='width: 20px; height: 92px; background-color: #" . $this->Colour . ";'></div>");
        return $html;
    }

    public function customTitle()
    {
        $colourBlock = $this->ColourBlock();
        return $colourBlock->getValue() . '<span class="category-title">' . $this->Title . '</span>';
    }

    public function NumberOfEvents()
    {
        return $this->Events()->Count();
    }

    public function NumberOfUpcomingEvents()
    {
        return $this->Events()->filter(['Start:GreaterThan' => date('Y-m-d H:i:s')])->Count();
    }

    public function IsChecked()
    {
        $request = Controller::curr()->getRequest();
        $types = $request->getVar('types');

        if (stripos($types, $this->URLSegment) !== false) {
            return true;
        }
    }

    public function PrinterActive()
    {
        $controller = Controller::curr();
        if ($controller) {
            $request = $controller->getRequest();
            if (strpos($request->getVar('category'), $this->URLSegment) === false) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }
}
