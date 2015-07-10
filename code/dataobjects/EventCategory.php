<?php

class EventCategory extends DataObject {
	
	private static $default_sort = "Sort";

	private static $db = array(
		'Title' 		=> 'Varchar(100)',
		'URLSegment' 	=> 'Varchar(255)',
		'Colour' 		=> 'Varchar(255)',
		'Sort'			=> 'Int'
	);
	
	private static $has_one = array(
		'Image' => 'Image'
	);

	private static $many_many = array(
		'Events' => 'CalendarEvent'
	);
	
	private static $many_many_extraFields = array(
		'Events' => array('Approved' => 'Int')
	);
	
	private static $searchable_fields = array(
		'Title'
	);

	private static $summary_fields = array(
		'ColourBlock' 			=> 'Colour',
		'Title'	 				=> 'Title',	
		'NumberOfEvents'		=> 'Number of Events',
		'Image.CMSThumbnail' 	=> 'Image',
	);
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName('Sort');
		$fields->removeByName('URLSegment');
		$fields->removeByName('Pages');
		$fields->removeByName('Events');
		
		$fields->addFieldToTab('Root.Main', ColorField::create('Colour', 'Colour'));
		

		$fields->addFieldToTab('Root.Main', UploadField::create('Image')
				->setFolderName( Config::inst()->get('Upload', 'uploads_folder') . "/" . 'Event-Categories')
				->addExtraClass('withmargin')
				->setRightTitle('If an event does not have an listing image, it will source its listing image from its first category')
		);
				
		return $fields;
	}
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if($this->isChanged('Title')){
			$filter	 			= URLSegmentFilter::create();
			$this->URLSegment 	= $filter->filter($this->Title);
			
			// Ensure that this object has a non-conflicting URLSegment value.
			$count = 2;
			while(!$this->validURLSegment()) {
				$this->URLSegment = preg_replace('/-[0-9]+$/', null, $this->URLSegment) . '-' . $count;
				$count++;
			}
		}
	}
	
	public function validURLSegment() {

		$segment 		  = Convert::raw2sql($this->URLSegment);
		$existingCategory = EventCategory::get()->filter('URLSegment', $segment)->first();
	
		return !($existingCategory);
	}
	
	public function ColourBlock(){
		$html = new HTMLVarchar();
		$html->setValue("<div style='width: 20px; height: 20px; background-color: #" . $this->Colour . ";'></div>");
		return $html;
	}
	
	public function customTitle(){
		$colourBlock 	= $this->ColourBlock();
		return $colourBlock->getValue() . '<span class="category-title">' . $this->Title . '</span>';
	}
	
	public function NumberOfEvents(){
		return $this->Events()->Count();
	}
	
	public function PrinterActive(){
		$controller = Controller::curr();
		if($controller){
			$request = $controller->getRequest();
			if(strpos($request->getVar('category'), $this->URLSegment) === false){
				return false;
			}else{
				return true;
			}
		}
	}
}