<?php
/**
 * A page that lists all events and allows users to view details about them.
 * 
 * @package event
 * @author 	Internetrix
 */
class EventsPage extends Page {
	
	private static $icon = 'events/images/icons/eventsholder';
	private static $description = 'Page that lists all upcoming Events for selected calendars.';
	private static $singular_name = 'Events Holder';
	private static $plural_name = 'Events Holders';
	
	private static $extensions = array(
		"ExcludeChildren"
	);
	
	private static $excluded_children = array(
		'CalendarEvent'
	);
	
	private static $db = array(
		'PaginationLimit' 			=> 'Int',
		'ViewMoreText' 				=> 'Varchar(255)',
		'SearchEventsPlaceholder' 	=> 'Varchar(255)',
		'EventsListTitle' 			=> 'Varchar(255)',
		'NoEventsText' 				=> 'HTMLText',
		'FinishedMessage' 			=> 'HTMLText',
		'HideSearchBox'				=> 'Boolean',
		'AddEventEmailTo'			=> 'Varchar(255)',
		'AddEventEmailFrom'			=> 'Varchar(255)',
		'PrintTitle'				=> 'Varchar(255)',
		'PDFFooterContent'			=> 'HTMLText',
		'HidePDFHeaderImage'			=> 'Boolean',
		'HidePDFFooterImage'			=> 'Boolean',
		'HidePDFFooterBackgroundImage'	=> 'Boolean'
	);
	
	private static $has_one = array(
		'PDFHeaderImage' 			=> 'Image',
		'PDFFooterImage' 			=> 'Image',
		'PDFFooterBackgroundImage' 	=> 'Image'
	);
	
	private static $defaults = array(
		'PaginationLimit' 			=> 5,
		'SearchEventsPlaceholder' 	=> 'Search Events...',
		'ViewMoreText' 				=> 'View Event',
		'EventsListTitle' 			=> 'Viewing All',
		'NoEventsText' 				=> '<p>Sorry there are no events</p>',
		'FinishedMessage' 			=> '<p>Your event has been submitted and is under review.</p>',
		'AddEventEmailTo' 			=> '',
		'AddEventEmailFrom' 		=> '',
		'PrintTitle'				=> 'Events Calendar for',
		'PDFFooterContent'			=> '<p>Events Calendar</p>'
	);
	
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
	
		if (!EventsPage::get()->First()) {
			$page = new EventsPage();
			$page->Title      				= 'Events';
			$page->URLSegment 				= 'events';
			$page->PaginationLimit 			= 5;
			$page->SearchEventsPlaceholder 	= 'Search Events...';
			$page->ViewMoreText 			= 'View Event';
			$page->EventsListTitle 			= 'Viewing All';
			$page->NoEventsText 			= '<p>Sorry there are no events</p>';
			$page->FinishedMessage 			= '<p>Your event has been submitted and is under review.</p>';
			$page->AddEventEmailTo 			= '';
			$page->AddEventEmailFrom 		= '';
			$page->PrintTitle				= 'Events Calendar for';
			$page->write();
			$page->publish('Stage', 'Live');
		}
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		// Makes sure the Listing Summary Toggle is present before
		$configBefore = Config::inst()->get('Events', 'event_fields_before');
		$configBefore = ($configBefore ? $configBefore : "Content");
		
		$putBefore = ($fields->fieldByName('Root.Main.ListingSummaryToggle') ? "ListingSummaryToggle" : $configBefore);
		
		$fields->removeByName('Right');
		$fields->addFieldToTab('Root.Main', NumericField::create('PaginationLimit', 'Pagination Limit'), $putBefore);
		$fields->addFieldToTab('Root.Main', TextField::create('ViewMoreText', 'View More Text'), $putBefore);
		$fields->addFieldToTab('Root.Main', TextField::create('SearchEventsPlaceholder', 'Search Events Placeholder'), $putBefore);
		$fields->addFieldToTab('Root.Main', TextField::create('EventsListTitle', 'Events List Title'), $putBefore);
		$fields->addFieldToTab('Root.Main', TextField::create('PrintTitle', 'Print Title'), $putBefore);
		$fields->addFieldToTab('Root.Main', CheckboxField::create('HideSearchBox', 'Hide  the search box?'), $putBefore);
		
		$fields->addFieldToTab('Root', new Tab('Messages', 'Messages & Emails'), 'Header');
		$fields->addFieldToTab('Root.Messages', HtmlEditorField::create('NoEventsText', 'No Events Text')->setRows(6)->addExtraClass('withmargin'));
		$fields->addFieldToTab('Root.Messages', HtmlEditorField::create('FinishedMessage', 'Message after adding an event from the website')->setRows(6)->addExtraClass('withmargin'));
		$fields->addFieldToTab('Root.Messages', TextField::create('AddEventEmailTo', '"Add event" email goes to?'));
		$fields->addFieldToTab('Root.Messages', TextField::create('AddEventEmailFrom', '"Add event" email comes from?'));
		
		$this->extend('updateEventsPageCMSFields', $fields);
		
		return $fields;
	}
	
	public function Children(){
		$children = parent::Children();
	
		foreach($children as $c){
			if($c->ClassName == 'CalendarEvent'){
				$children->remove($c);
			}
		}
		
		$this->extend('updateEventsPageChildren', $children);
	
		return $children;
	}
}

class EventsPage_Controller extends Page_Controller {
	
	protected $start;
	protected $end;
	protected $category;
	protected $categoryurl;
	protected $searchQuery;
	protected $requiredAddFormFields = array('Title','Start','End', 'Categories', 'SubmitterFirstName', 'SubmitterSurname', 'SubmitterEmail', 'SubmitterPhoneNumber');
	protected $printer = false;
	protected $showimages = false;
	
	protected $types;
	protected $typesDL;
	protected $typesurl;
	
	protected $ranges;
	protected $rangesDL;
	protected $rangesurl;
	
	public function init()
	{
		parent::init();
		
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/ssui.core.js');
		Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');
		
		Requirements::javascript(EVENTCALENDAR_DIR . "/javascript/EventsPageCalendar.js");
		Requirements::javascript(EVENTCALENDAR_DIR . "/thirdparty/qtip/jquery.qtip-2.0.0.min.js");
		Requirements::css(EVENTCALENDAR_DIR . "/thirdparty/qtip/jquery.qtip-2.0.0.css");
		
		if(Config::inst()->get('Events', 'pagination_type') == "ajax") {
			Requirements::javascript("events/javascript/eventspage.js");
		}
		
		if(Config::inst()->get('Events', 'page_search_type') == "refine") {
			Requirements::javascript("events/javascript/refine.js");
		}
		
		$request = $this->getRequest();
		
		$getParams = $request->getVars();
		
		$this->start 		= isset($getParams['startd']) 	? $getParams['startd'] 						: null;
		$this->end 			= isset($getParams['end']) 		? $getParams['end'] 						: null;
		$this->categoryurl 	= isset($getParams['category']) ? Convert::raw2sql($getParams['category']) 	: null;
		$this->searchQuery 	= isset($getParams['searchQuery']) 	? Convert::raw2sql($getParams['searchQuery']) 	: null;
		$this->showimages	= isset($getParams['images']) 	? Convert::raw2sql($getParams['images']) 	: false;
		
		$this->typesurl 	= isset($getParams['types']) ? Convert::raw2sql($getParams['types']) 		: null;
		
		if($this->categoryurl){
			$catURLs = explode(".", $this->categoryurl);
			$category 		= EventCategory::get()->filter('URLSegment', $catURLs);
			$this->category = implode(",", $category->map("ID", "ID")->toArray());
		}
		
		if($this->typesurl){
			$typesURLs 		= explode(".", $this->typesurl);
			$types 			= EventCategory::get()->filter('URLSegment', $typesURLs);
			$this->typesDL 	= $types;
			$this->types 	= implode(",", $types->map("ID", "ID")->toArray());
		}
		
	}

	private static $allowed_actions = array(
		'index',
		'add',
		'AddForm',
		'doAdd',
		'finished',
		'eventcalendar'
	);

	public function index() {
		
		if(Director::is_ajax()) {
			$this->response->addHeader("Vary", "Accept"); // This will enable pushState to work correctly
			return $this->renderWith('EventsList');
		}
		
		$customTitle = $this->EventsListTitle;
		
		if($this->start || $this->end || $this->category > 0 || $this->content){
			$customTitle = "Search Results";
		}
		
		return $this->customise(array(
			'EventsListTitle' 	=> $customTitle
		));
	}
	
	public function getOffset() {
		if(!isset($_REQUEST['start'])) {
			$_REQUEST['start'] = 0;
		}
	
		return $_REQUEST['start'];
	}
	
	public function add() {
		return $this->customise(array('Finished' => false))->renderWith(array('EventsPage_add', 'EventsPage', 'Page'));
	}
	
	public function finished() {
		return $this->customise(array('Finished' => true))->renderWith(array('EventsPage_add', 'EventsPage', 'Page'));
	}
	
	public function PrintTitle(){
		$pt 		= $this->data()->PrintTitle;
		$start 		= str_replace("/", "-", $this->start);
		$end 		= str_replace("/", "-", $this->end);
		$startTime 	= strtotime($start);
		$endTime 	= strtotime($end);
		
		$dates = date("d M Y", $startTime) . ' - ' . date("d M Y", $endTime);
		
		if(!($start && $end)){
			return 'Upcoming Events';
		}
		
		$startMonth = date("m", $startTime);
		$endMonth = date("m", $endTime);
		
		if($startMonth == $endMonth){
			//lets see if it entire month
			$startDay = date("j", $startTime);
			if($startDay == '1'){
				$endDay = date("j", $endTime);
				
				if($endDay == date("t", $endTime)){
					$dates = date("F Y", $endTime);
				}
			}
		}
		
		return $pt . " " . $dates;
	}
	
	public function EventsCategories(){
		$categories =  EventCategory::get();
		$this->extend('updateEventsCategories', $categories);
		return $categories;
	}
	
	public function AllEvents(){
	
		$events = CalendarEvent::get();
	
// 		if($this->SubsiteID == 0){
// 			$events = $events->setDataQueryParam('Subsite.filter' , false);
// 		}
	
		if($this->start){
			$startAu = str_replace('/', '-', $this->start);
			$startAu = date('Y-m-d', strtotime($startAu));
			$events = $events->filterAny(array('Start:GreaterThan' => $startAu, 'End:GreaterThan' => $startAu));
		}else{
			$events = $events->filter(array('End:GreaterThan' => date('Y-m-d H:i:s')));
		}
	
		if($this->end){
			//we need to add one day so that end date is included
			$endPlus = str_replace('/', '-', $this->end);
			$endPlus = date('Y-m-d', strtotime($endPlus . "+1 day"));
			$events	 = $events->filter(array('End:LessThan' => $endPlus));
		}
	
		if($this->searchQuery){
			$eventTable = 'SiteTree';
			if(Versioned::current_stage() == 'Live'){
				$eventTable .= '_Live';
			}
			$events = $events->where("\"$eventTable\".\"Title\" LIKE '%" . $this->searchQuery . "%' OR \"$eventTable\".\"Content\" LIKE '%" . $this->searchQuery . "%'");
		}
	
		if($this->category){
			$eventTable = 'CalendarEvent';
			$extraWhere = "";
			if(Versioned::current_stage() == 'Live'){
				$eventTable .= '_Live';
				$extraWhere = ' AND "EventCategory_Events"."Approved" = 1 ';
			}
			$str  = "(" . $this->category . ")" ;
			
	
			$events = $events->where('(SELECT COUNT("EventCategory_Events"."ID") FROM "EventCategory_Events" WHERE "EventCategory_Events"."CalendarEventID" = "'. $eventTable .'"."ID" AND "EventCategory_Events"."EventCategoryID" IN '. $str . $extraWhere . ')');
		}
		
		if($this->types){
			$eventTable = 'CalendarEvent';
			$extraWhere = "";
			if(Versioned::current_stage() == 'Live'){
				$eventTable .= '_Live';
				// 				$extraWhere = ' AND "EventCategory_Events"."Approved" = 1 ';
			}
			$str  = "(" . $this->types . ")" ;
				
			$events = $events->where('(SELECT COUNT("EventCategory_Events"."ID") FROM "EventCategory_Events" WHERE "EventCategory_Events"."CalendarEventID" = "'. $eventTable .'"."ID" AND "EventCategory_Events"."EventCategoryID" IN '. $str . $extraWhere . ')');
		}
		
		return $events;
	}
	
	public function Events(){
		
		$events = $this->AllEvents();
		$toreturn = null;
		
		$paginationType = Config::inst()->get('Events', 'pagination_type');
		
		if($paginationType == "ajax") {
			$startVar = $this->request->getVar("start");
				
			if($startVar && !Director::is_ajax()) { // Only apply this when the user is returning from the article OR if they were linked here
				$toload = ($startVar / $this->PaginationLimit); // What page are we at?
				$limit = (($toload + 1) * $this->PaginationLimit); // Need to add 1 so we always load the first page as well (articles 0 to 5)
			
				$list = $events->limit($limit, 0);
				$next = $limit;
			} else {
				$offset = $this->getOffset();
				$limit = $this->PaginationLimit;
			
				$list = $events->limit($limit, $offset);
				$next = $offset + $this->PaginationLimit;
			}
				
			$all_news_count 	= $events->count();
			$this->MoreEvents 	= ($next < $all_news_count);
			$this->MoreLink 	= HTTP::setGetVar("start", $next);
				
			$toreturn = $list;
		} else {
			$toreturn = PaginatedList::create($events, $this->request)->setPageLength($this->PaginationLimit);
		}
		
		Session::set('EventsOffset'.$this->ID, $this->getOffset());
		
		return $toreturn;
	}
	
	public function StartDateField(){
		$now = date('d/m/Y');
		
		$start = DateField::create('startd', 'Start');
		$start->setConfig('showcalendar', true);
		$start->setConfig('dateformat', 'dd/MM/YYYY');
		$start->setConfig('jQueryUI.changeMonth', true);
		
		if(!$this->printer){
			$start->setConfig('jQueryUI.minDate', $now);
		}
		
		if($this->start){
			$start->setValue($this->start);
		}else if(!$this->printer){
			$start->setValue($now);
		}

		return $start->SmallFieldHolder();
	}
	
	public function EndDateField(){
		$now = date('d/m/Y');
		
		$end = DateField::create('end', 'End');
		$end->setConfig('showcalendar', true);
		$end->setConfig('dateformat', 'dd/MM/YYYY');
		$end->setConfig('jQueryUI.changeMonth', true);
		
		if(!$this->printer){
			$end->setConfig('jQueryUI.minDate', $now);
		}
		
		if($this->end){
			$end->setValue($this->end);
		}
		
		return $end->SmallFieldHolder();
	}
	
	public function CategoriesField(){
		$categories = DropdownField::create('category', 'Filter By:')
			->setSource(EventCategory::get()->map("URLSegment", "Title")->toArray())
			->setEmptyString("");
		if($this->categoryurl){
			$categories->setValue($this->categoryurl);
		}
		$this->extend('updateCategoriesField', $categories);
		
		return $categories->FieldHolder();
	}
	
	public function HiddenCategoriesField(){
		$categories = HiddenField::create('category', 'Filter By:');
		if($this->categoryurl){
			$categories->setValue($this->categoryurl);
		}
		$this->extend('updateHiddenCategoriesField', $categories);
	
		return $categories->FieldHolder();
	}
	
	public function searchQueryField(){
		$searchQuery = TextField::create('searchQuery', 'Search Query')
			->addExtraClass('search-events');
		
		if($this->searchQuery){
			$searchQuery->setValue($this->searchQuery);
		}
			
		if($this->SearchEventsPlaceholder){
			$searchQuery->setAttribute('placeholder', $this->SearchEventsPlaceholder);
		}
		
		$this->extend('updateSearchQueryField', $searchQuery);
		
		return $searchQuery->Field();
	}
	
	public function ShowImagesField(){
		$showImages = CheckboxField::create('images', 'Show Images?');
		if($this->showimages){
			$showImages->setValue(true);
		}
		$this->extend('updateShowImagesField', $showImages);
	
		return $showImages->FieldHolder();
	}
	
	public function SearchEventsFormAction(){
		$action = $this->Link();
		return $action;
	}
	
	public function CurrentCategory(){
		return $this->categoryurl;
	}
	
	public function ShowImages(){
		return $this->showimages;
	}
	
	public function eventcalendar() {
		$calendar = new EventsPageCalendar($this, 'eventcalendar', $this->month, $this->year, $this->day);
		return $calendar;
	}
	
	public function AddForm(){
		
		$size 	= 0.25 * 1024 * 1024; //256KB
		$fields = new FieldList(array(
			HeaderField::create('EventDetails', 'Event Details'),
				
			DropdownField::create('Categories', 'Category', $this->EventsCategories()->map()->toArray())
				->setEmptyString("-- Select a Category --"),
			TextField::create('Title', 'Title')->addExtraClass('second'),
			$start = DatetimeField::create('Start', 'Start'),
			$end = DatetimeField::create('End', 'End'),
			TextField::create('Cost', 'Cost'),
			$upField = UploadField::create('ListingImage', 'Image')
				->setFolderName(Config::inst()->get('Upload', 'uploads_folder') . '/Events')
				->setCanAttachExisting(false)
				->setCanUpload(true)
				->setCanPreviewFolder(false)
				->setOverwriteWarning(false)
				->setRightTitle('Max file size - 256KB')
				->setAttribute('maxlength', 10)
				->setAttribute('data-tinymce-maxlength-indicator', true),
				
			HeaderField::create('EventContactDetails', 'Event Contact Details'),
			TextField::create('Website', 'Website'),
			EmailField::create('Email', 'Email')->addExtraClass('second'),
			TextField::create('Contact', 'Contact'),
			TextField::create('Phone', 'Phone')->addExtraClass('second'),
				
			HeaderField::create('EventAddress', 'Address'),
			TextField::create('Address', 'Address'),
			TextField::create('Suburb', 'Suburb')->addExtraClass('second'),
			DropdownField::create('State', 'State', array(
				'ACT' => 'Australian Capital Territory',
				'NSW' => 'New South Wales',
				'NT'  => 'Northern Territory',
				'QLD' => 'Queensland',
				'SA'  => 'South Australia',
				'TAS' => 'Tasmania',
				'VIC' => 'Victoria',
				'WA'  => 'Western Australia'
			)),
			$postcode = new RegexTextField('Postcode', 'Postcode'),
			HtmlEditorField::create('Content')
				->setRightTitle('Max characters - 1000'),
			HeaderField::create('YourDetails', 'Your Details'),
			$firstName = TextField::create('SubmitterFirstName', 'First Name'),
			$surname = TextField::create('SubmitterSurname', 'Surname')->addExtraClass('second'),
			$email = EmailField::create('SubmitterEmail', 'Email'),
			$phone = TextField::create('SubmitterPhoneNumber', 'Phone Number')->addExtraClass('second'),
		));
		
		$upField->getValidator()->setAllowedMaxFileSize($size);
		
		$start->getDateField()->setConfig('showcalendar', true);
		$start->getDateField()->setConfig('dateformat', 'dd/MM/YYYY');
		$start->setTimeField(TimeDropdownField::create('Start[time]' , 'Time'));
		
		$end->getDateField()->setConfig('showcalendar', true);
		$end->getDateField()->setConfig('dateformat', 'dd/MM/YYYY');
		$end->setTimeField(TimeDropdownField::create('End[time]' , 'Time'));
		
		$postcode->setRegex('/^[0-9]+$/');
		$postcode->addExtraClass('second');
	
		$validator = RequiredFields::create($this->requiredAddFormFields);
	
		$member = Member::currentUser();
		if($member){
			$firstName->setValue($member->FirstName);
			$surname->setValue($member->Surname);
			$email->setValue($member->Email);
			$phone->setValue($member->PhoneNumber);
		}
	
		$actions = FieldList::create(
			FormAction::create('doAdd', 'Add')
		);
		//Create form
		$form = Form::create($this, 'AddForm', $fields, $actions, $validator);
			
		return $form;
	}
	
	public function doAdd($data, Form $form){
		
		foreach($this->requiredAddFormFields as $fieldName){
			if(!isset($data[$fieldName])){
				return $this->httpError(404);
			}
		}
		
		foreach($data as $key => $value){
			$data[$key] = Convert::raw2sql($value);
		}
		
		$event = new CalendarEvent();
		$form->saveInto($event);
		$parent 		 = EventsPage::get()->first();
		$event->ParentID = $parent->ID;
		$event->writeToStage('Stage');
		
		$category = EventCategory::get()->byID((int) $data['Categories']);
		if($category){
			$event->Categories()->add($category);
			$event->writeToStage('Stage');
		}
		
		$toEmail 		= $this->AddEventEmailTo ? $this->AddEventEmailTo : ''; // TODO: Default emails
		$fromEmail 		= $this->AddEventEmailFrom ? $this->AddEventEmailFrom : ''; // TODO: Default emails
		$data['Event'] 	= $event;
		
		$email = new Email();
		$email->setSubject('New event submission from website');
		$email->setTo($toEmail);
		$email->setFrom($fromEmail);
		$email->setTemplate('PublicEventAddition');
		$email->populateTemplate($data);
		$email->send();
			
		return $this->redirect($this->Link('finished'));
	}
	
}