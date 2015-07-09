<?php
/**
 * An event that is displayed on the events page.
 *
 * @package irxeventcalendar
 * @author 	Internetrix
 */
class CalendarEvent extends Page {
	
	private static $icon = 'irxeventcalendar/images/icons/eventspage';
	private static $description = 'Page that displays a single event.';
	private static $singular_name = 'Event';
	private static $plural_name = 'Events';
	private static $db = array(
		'Title'          			=> 'Varchar(255)',
		'Start'          			=> 'SS_Datetime',
		'End'            			=> 'SS_Datetime',
		'Cost'						=> 'Varchar(255)',
		'Website'          			=> 'Varchar(255)',
		'Email'          			=> 'Varchar(255)',
		'Contact'          			=> 'Varchar(255)',
		'Phone'          			=> 'Varchar(255)',
		'LegacyID'					=> 'Int',
		'LegacyLocation'			=> 'Text',
		'LegacyFileName'			=> 'Text',
		'LegacyCategoryID'			=> 'Int',
		'SubmitterFirstName'		=> 'Varchar(255)',
		'SubmitterSurname'			=> 'Varchar(255)',
		'SubmitterEmail'			=> 'Varchar(255)',
		'SubmitterPhoneNumber'		=> 'Varchar(255)',
		'HideStartAndEndTimes'		=> 'Boolean',
		'HideDatePosted'			=> 'Boolean',
	);
	
	private static $default_sort = '"Start" ASC'; // broke the modelAdmin

	private static $defaults = array(
		'ShowListingImageOnPage' => true,
		'ShowShareIcons'		 => true,
		'HideDatePosted'		 => true
	);
		
	private static $has_one = array(
		'CreatedBy' => 'Member'
	);
	
	private static $belongs_many_many = array(
		'Categories' => 'EventCategory'
	);
	
	private static $extensions = array(
		'Addressable',
		'Geocodable'
	);

	private static $searchable_fields = array(
		'Title' => array('filter' => 'PartialMatchFilter', 'title' => 'Title' ),
		'Content' => array('filter' => 'PartialMatchFilter', 'title' => 'Content' )
	);
	
	private static $summary_fields = array(
		'Title',
		"Status",
		'Start.Nice',
		'End.Nice',
		'DisplayCategories',
		'ListingImage.CMSThumbnail'
	);
	
	private static $field_labels = array(
		"Start.Nice" 				=> 'Starts',
		"End.Nice" 					=> 'Ends',
		"DisplayCategories" 		=> 'Categories',
		"ListingImage.CMSThumbnail" => 'Image'
	);
	
	
	public function populateDefaults(){
		parent::populateDefaults();
		
// 		$member = Member::currentUser();
// 		$member = $member ? $member->getName() : "";
		
// 		$this->setField('CreatedBy', $member);
		
		$this->setField('Start', date('Y-m-d', strtotime('now')));
		$this->setField('End', date('Y-m-d', strtotime('now')));
	
	}
	

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->renameField('Title', 'Title');
		$fields->removeByName('Behaviour');
		$fields->removeByName('Todo');
		$fields->removeByName('Access');
		$fields->removeByName('Slideshow');
		$fields->removeByName('MenuContent');
		
		// If an image has not been set, open the toggle field to remind user
		if($this->ListingImageID == 0){
			$toggle = $fields->fieldByName('Root.Main.ListingSummaryToggle');
			$toggle->setStartClosed(false);
		}
		
		$fields->addFieldToTab('Root.Main', UploadField::create('ListingImage', 'Listing Image')
			->addExtraClass('withmargin')
			->setFolderName(Config::inst()->get('Upload', 'uploads_folder') . '/Events')
		, 'ShowListingImageOnPage');
		
		$start = DatetimeField::create('Start', 'Start');
		$start->getDateField()->setConfig('showcalendar', true);
// 		$start->getDateField()->setConfig('dateformat', 'dd-MM-yyyy');
		$start->setTimeField(TimeDropdownField::create('Start[time]' , 'Time'));
		
		$end = DatetimeField::create('End', 'End');
		$end->getDateField()->setConfig('showcalendar', true);
// 		$end->getDateField()->setConfig('dateformat', 'dd-MM-yyyy');
		$end->setTimeField(TimeDropdownField::create('End[time]' , 'Time'));
		
		$fields->addFieldToTab('Root.Main', $start, 'ListingSummaryToggle');
		$fields->addFieldToTab('Root.Main', $end, 'ListingSummaryToggle');
		
		$fields->addFieldToTab('Root.Main', CheckboxField::create('HideStartAndEndTimes', 'Hide start and end times'), 'ListingSummaryToggle');
		$fields->addFieldToTab('Root.Main', CheckboxField::create('HideDatePosted', 'Hide date posted'), 'ListingSummaryToggle');
		
		$fields->addFieldToTab('Root.Main', TextField::create('Cost', 'Cost (Leave it blank if cost is free)'), 'ListingSummaryToggle');
		
		$fields->addFieldToTab('Root.Main', $cats = ListboxField::create('Categories', 'Categories', EventCategory::get()->map()->toArray())
				->setMultiple(true)
				,  'ListingSummaryToggle');
		
		$fields->addFieldToTab('Root.Main', $contactToggle = ToggleCompositeField::create('ContactToggle', 'Contact Details', array(
			TextField::create('Website', 'Website'),
			TextField::create('Email', 'Email'),
			TextField::create('Contact', 'Contact'),
			TextField::create('Phone', 'Phone')
		)), 'ListingSummaryToggle');
		
		$address = $fields->findOrMakeTab('Root.Address');
		$fields->removeByName('Address');
		$address->removeByName('AddressHeader');
		$fields->addFieldToTab('Root.Main', $addressToggle = ToggleCompositeField::create('AddressToggle', 'Address', $address), 'ListingSummaryToggle');
		
		if(!$this->ID){
			$contactToggle->setStartClosed(false);
			$addressToggle->setStartClosed(false);
		}
		
		if($this->SubmitterFirstName || $this->SubmitterSurname || $this->SubmitterEmail || $this->SubmitterPhoneNumber){
			$fields->addFieldsToTab('Root.SubmittedBy', array(
				ReadonlyField::create('SubmitterFirstName', 'First Name'),
				ReadonlyField::create('SubmitterSurname', 'Surname'),
				ReadonlyField::create('SubmitterEmail', 'Email'),
				ReadonlyField::create('SubmitterPhoneNumber', 'Phone Number')
			));
		}
		
		$this->extend('updateEventCMSFields', $fields);
		
		return $fields;
	}
	
	public function onAfterPublish() {
		
			/*****************************************belong many many relationships**********************************/
			$categories = $this->Categories();
			foreach($categories as $category){
				$categories->add($category, array('Approved' => 1));
			}
			/**********************************************************************************************************/
			
	}

	public function getCMSValidator() {
		return new RequiredFields('Title', 'Start', 'End');
	}

	public function onBeforeWrite(){
		parent::onBeforeWrite();
		
		if(!$this->ParentID){
			$parent = EventsPage::get()->first();
			if(!$parent){
				$parent = new EventsPage();
				$parent->Title 		= 'Events';
				$parent->URLSegment = 'events';
				$parent->write();
				$parent->publish('Stage', 'Live');
			}
			$this->setField('ParentID', $parent->ID);
		}
		
		$checkSite = $this->getField('Website');
		if (!empty($checkSite) && strpos($checkSite, "http") !== 0){
			$this->setField('Website', "http://" . $checkSite);
		}
		
	}
	
	public function googleMapAddress(){
		
		$fullAddress = $this->Address;
		$fullAddress .= ' '.$this->Suburb;
		$fullAddress .= ' '.$this->State;
		$fullAddress .= ' '.$this->Postcode;
		
		return urlencode($fullAddress);
	}

	public function LoadDate(){
		if($this->Start && $this->End){	
			$startSTR = strtotime($this->Start);
			$start = date('jS M Y', $startSTR);

			$endSTR = strtotime($this->End);
			$end = date('jS M Y', $endSTR);
			
			if($start == $end) return $start;
		
			return $start.' - '.$end;
		}
		
		if($this->Start && !$this->End){
			$startSTR = strtotime($this->Start);
			$start = date('jS M Y', $startSTR);
		
			return $start;
		}
		
		return false;
	}
	
	public function LoadAddress(){
		if($this->Address && $this->Suburb && $this->State) 
			return $this->Address.', '.$this->Suburb.', '.$this->State;

		return false;
	}
	
	public function Status(){
		if($this->isNew()){
			return 'New Page';
		}
// 		elseif($this->getIsModifiedOnStage()){
// 			return 'Modified';
// 		}
		elseif($this->isPublished()){
			return 'Published';
		}else{
			return 'Unpublished';
		}
	}
	
	public function Categories(){
		$categories = $this->getManyManyComponents('Categories');
		if(Versioned::current_stage() == 'Live'){
			$categories = $categories->filter('Approved', true);
		}
		return $categories;
	}
	
	public function DisplayCategories(){
		$categories = $this->Categories();
		if($categories){
			return implode(", ", $categories->map()->toArray());
		}
	}

	public function getStartMonth() {
		return date('F', strtotime($this->Start));
	}
	
	public function getStartYear() {
		return date('Y', strtotime($this->Start));
	}
	
	public function OneDay(){
		return date('d/m/Y', strtotime($this->Start)) == date('d/m/Y', strtotime($this->End));
	}
	
}

class CalendarEvent_Controller extends Page_Controller {
	
	public function init () {
		parent::init ();
		Requirements::block('timedropdownfield/javascript/TimeDropdownField.js');
	}
	
	public function BackLink(){
		$request = $this->getRequest();
		$url 	 = false;
	
		if($request->requestVar('_REDIRECT_BACK_URL')) {
			$url = $request->requestVar('_REDIRECT_BACK_URL');
		} else if($request->getHeader('Referer')) {
			$url = $request->getHeader('Referer');
			//need to check the referer isnt the same page
			if($url == Director::absoluteURL($this->Link())){
				$url = false;
			}
		}
	
		if(!$url){
			$page = EventsPage::get()->first();
			$url = $page ? $page->Link() : false;
		}
		return $url;
	}
	
	
}