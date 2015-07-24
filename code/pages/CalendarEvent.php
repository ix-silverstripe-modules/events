<?php
/**
 * An event that is displayed on the events page.
 *
 * @package irxeventcalendar
 * @author 	Internetrix
 */
class CalendarEvent extends Page {
	
	private static $icon = 'events/images/icons/eventspage';
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
		'EnableRegistrationPage'	=> 'Boolean',
		'ForcedFieldsCreated'		=> 'Boolean'
	);
	
	private static $default_sort = '"Start" ASC'; // broke the modelAdmin

	private static $defaults = array(
		'ShowListingImageOnPage' => true,
		'ShowShareIcons'		 => true,
		'HideDatePosted'		 => true
	);
		
	private static $has_one = array(
		'CreatedBy' 	=> 'Member'
	);
	
	private static $has_many = array(
		"Fields"		=> 'EditableFormField',
		"Submissions" 	=> "SubmittedForm"
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
	
	private static $block_frontend_fields = array(
		"ListingSummaryToggle",
		"Metadata",
		"URLSegment",
		"HideStartAndEndTimes",
		"HideDatePosted",
		"MenuTitle"
	);
	
	public function populateDefaults(){
		parent::populateDefaults();
		
		$this->setField('Start', date('Y-m-d', strtotime('now')));
		$this->setField('End', date('Y-m-d', strtotime('now')));
	
	}
	

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		// Makes sure the Listing Summary Toggle is present before
		$configBefore = Config::inst()->get('Events', 'event_fields_before');
		$configBefore = ($configBefore ? $configBefore : "Content");
		
		$putBefore = ($fields->fieldByName('Root.Main.ListingSummaryToggle') ? "ListingSummaryToggle" : $configBefore);
		
		// If an image has not been set, open the toggle field to remind user
		if(class_exists('ListingSummary') && $this->ListingImageID == 0){
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
		
		$fields->addFieldToTab('Root.Main', $start, $configBefore);
		$fields->addFieldToTab('Root.Main', $end, $configBefore);
		
		$fields->addFieldToTab('Root.Main', CheckboxField::create('HideStartAndEndTimes', 'Hide start and end times'), $configBefore);
		$fields->addFieldToTab('Root.Main', CheckboxField::create('HideDatePosted', 'Hide date posted'), $configBefore);
		$fields->addFieldToTab('Root.Main', CheckboxField::create('EnableRegistrationPage', 'Enable visitors to register for event?'), $configBefore);
		
		if($this->ID == 0) {
			$regoinfoField = LiteralField::create('EnableRegistrationPage2', '<span style="color: red; margin-left: 184px;">Please save the event to show configuration options</span>');
			$fields->addFieldToTab('Root.Main', DisplayLogicWrapper::create($regoinfoField)->displayIf("EnableRegistrationPage")->isChecked()->end(), $configBefore);
		}
		
		$fields->addFieldToTab('Root.Main', TextField::create('Cost', 'Cost (Leave it blank if cost is free)'), $configBefore);
		
		$fields->addFieldToTab('Root.Main', $cats = ListboxField::create('Categories', 'Categories', EventCategory::get()->map()->toArray())
				->setMultiple(true)
				,  $configBefore);
		
		$fields->addFieldToTab('Root.Main', $contactToggle = ToggleCompositeField::create('ContactToggle', 'Contact Details', array(
			TextField::create('Website', 'Website'),
			TextField::create('Email', 'Email'),
			TextField::create('Contact', 'Contact'),
			TextField::create('Phone', 'Phone')
		)), $configBefore);
		
		$address = $fields->findOrMakeTab('Root.Address');
		$fields->removeByName('Address');
		$address->removeByName('AddressHeader');
		$fields->addFieldToTab('Root.Main', $addressToggle = ToggleCompositeField::create('AddressToggle', 'Address', $address), $configBefore);
		
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
		
		// Show this tab only if the Registration Page has been enabled
		if($this->EnableRegistrationPage) {
			$fields->addFieldToTab("Root.RegistrationForm", FieldEditor::create("Fields", 'Fields', "", $this ));
			
			// view the submissions
			$submissions = new GridField(
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
				
			/**
			 * Support for {@link https://github.com/colymba/GridFieldBulkEditingTools}
			*/
			if(class_exists('GridFieldBulkManager')) {
				$config->addComponent(new GridFieldBulkManager());
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
	
	public function onAfterWrite() {
		parent::onAfterWrite();
		
		// If a submission form is desired, we need to ensure it has the default fields
		if($this->EnableRegistrationPage && !$this->ForcedFieldsCreated) {
			$forcedFields = Config::inst()->get("Events", "RequiredEventFields");
			
			// Only attempt to create the fields if the config exists
			if($forcedFields) {
				foreach($forcedFields as $fieldname => $field) {
					$title = $field["Title"];
					$type = $field["Type"];
					
					$checkFields = $this->Fields()->filter(array("Title" => $title));
					
					if($checkFields->Count() == 0) {
						$fieldObj = new $type();
						$fieldObj->ParentID = $this->ID;
						$fieldObj->ForcedField = 1;
						$fieldObj->Name = $fieldObj->class . $fieldObj->ID;
						$fieldObj->Title = $title;
						$fieldObj->write();
					}
					
				}
				
				$this->ForcedFieldsCreated = 1;
			}
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
	
	public function getLeadingImage() {
		if($this->ListingImageID) {
			return $this->ListingImage();
		}elseif($this->Categories()->first() && $this->Categories()->first()->ImageID) {
			return $this->Categories()->first()->Image();
		} else {
			return false;
		}
	}
	
}

class CalendarEvent_Controller extends Page_Controller {
	
	private static $allowed_actions = array('RegistrationForm');
	
	public function init () {
		parent::init ();
		Requirements::block('timedropdownfield/javascript/TimeDropdownField.js');
	}
	
	public function ShareLinksEnabled() {
		return Config::inst()->get('Events', 'enable_sharing');
	}
	
	public function BackLink(){
		$url 	 = false;
 		$value = Session::get('EventsOffset'.$this->ParentID);
 		
 		// check the referrer first. If they came from a filtered page, the back link needs to be formulated a little different
 		$referer = $this->request->getHeaders();
 		
 		if(isset($referer["Referer"])) {
	 		$parseReferer = parse_url($referer["Referer"]);
	
	 		if(isset($parseReferer['query'])) {
	 			// Get parent
	 			$parent = $this->Parent;
	 			$url = $parent->Link("?".$parseReferer['query']."&start=$value".'#'.$this->URLSegment);
	 		}
 		}
 		
 		if(!$url && $value) {
 			// Get parent
 			$parent = $this->Parent;
 			$url = $parent->Link("?start=$value".'#'.$this->URLSegment);
 		}
	
		if(!$url){
			$page = $this->Parent();
			$url = $page ? $page->Link('#'.$this->URLSegment) : false;
		}
		
		return $url;
	}
	
	public function PrevNextPage($Mode = 'next') {
	
		if($Mode == 'next'){
			$Direction = "Start:GreaterThan";
			$Sort = "Start ASC";
		}
		elseif($Mode == 'prev'){
			$Direction = "Start:LessThan";
			$Sort = "Start DESC";
		}
		else{
			return false;
		}
		
		// Filter out events that start before this
		$PrevNext = CalendarEvent::get()->filter(array('Start:GreaterThan' => date('Y-m-d H:i:s')));
		
		$PrevNext = $PrevNext->filter(array(
				$Direction => $this->Start
			))
			->sort($Sort)
			->first();
	
		if ($PrevNext){
			return $PrevNext->Link();
		}
	}
	
	private function getRegistrationFormFields() {
		$fields 	= new FieldList();

		if($this->Fields()) {
			foreach($this->Fields() as $editableField) {
				// get the raw form field from the editable version
				$field = $editableField->getFormField();
				if(!$field) break;
	
				// set the error / formatting messages
				$field->setCustomValidationMessage($editableField->getErrorMessage());
	
				// set the right title on this field
				if($right = $editableField->getSetting('RightTitle')) {
					$field->setRightTitle($right);
				}
	
				// if this field is required add some
				if($editableField->Required) {
					$field->addExtraClass('required');
	
					if($identifier = UserDefinedForm::config()->required_identifier) {
	
						$title = $field->Title() ." <span class='required-identifier'>". $identifier . "</span>";
						$field->setTitle($title);
					}
				}
				// if this field has an extra class
				if($editableField->getSetting('ExtraClass')) {
					$field->addExtraClass(Convert::raw2att(
							$editableField->getSetting('ExtraClass')
					));
				}
	
				// 				// set the values passed by the url to the field
				// 				$request = $this->getRequest();
				// 				if($var = $request->getVar($field->name)) {
				// 					$field->value = Convert::raw2att($var);
				// 				}
	
				$fields->push($field);
			}
		}
		$this->extend('updateRegistrationFormFields', $fields);
	
		return $fields;
	}
	
	private function getRequiredFields(FieldList $fields) {
		$requiredfields = array();
		
		foreach($fields as $editableField) {
			if($editableField->Required) {
				$requiredfields[] = $editableField->Name;
			}
		}
		
		return $requiredfields;
	}
	
	public function RegistrationForm() {
		$fields = $this->getRegistrationFormFields();
		
		if($fields) {
			$required = $this->getRequiredFields($this->Fields());
			$validator = new RequiredFields($required);
			
			$actions = new FieldList(
					new FormAction('doRegister', 'Submit')
			);
			
			$this->extend('RegistrationFormFields', $fields);
			$this->extend('RegistrationFormActions', $actions);
			$this->extend('RegistrationFormValidator', $validator);
			
			return new Form($this, 'RegistrationForm', $fields, $actions, $validator);
		}
		return;
	}
	
	public function doRegister($data, $form) {
		Session::set("FormInfo.{$form->FormName()}.data",$data);
		Session::clear("FormInfo.{$form->FormName()}.errors");
		
		// Store the submitted form
		$submittedForm = Object::create('SubmittedForm');
		$submittedForm->SubmittedByID = ($id = Member::currentUserID()) ? $id : 0;
		$submittedForm->ParentID = $this->ID;
		$submittedForm->write();
		
		$submittedFields = new ArrayList();
		
		foreach($this->Fields() as $field) {
				
			$submittedField = $field->getSubmittedFormField();
			$submittedField->ParentID = $submittedForm->ID;
			$submittedField->Name = $field->Name;
			$submittedField->Title = $field->getField('Title');
				
			// save the value from the data
			if($field->hasMethod('getValueFromData')) {
				$submittedField->Value = $field->getValueFromData($data);
			} else {
				if(isset($data[$field->Name])) {
					$submittedField->Value = $data[$field->Name];
				}
			}
		
			if(!empty($data[$field->Name])){
				if(in_array("EditableFileField", $field->getClassAncestry())) {
					if(isset($_FILES[$field->Name])) {
						$foldername = $field->getFormField()->getFolderName();
		
						// create the file from post data
						$upload = new Upload();
						$file = new File();
						$file->ShowInSearch = 0;
						try {
							$upload->loadIntoFile($_FILES[$field->Name], $file, $foldername);
						} catch( ValidationException $e ) {
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
		
		$this->redirect($this->Link("finished"));
	}
	
	
	
}