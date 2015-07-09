<?php
/**
 * @package irxeventcalendar
 * @author 	Internetrix
 */
class EventsAdmin extends VersionedModelAdmin {
	
	private static $menu_icon = 'events/images/icons/calendar_icon.png';
	
	
	public static $title       = 'Events';
	public static $menu_title  = 'Upcoming';
	public static $url_segment = 'events';

	public static $managed_models  = array('CalendarEvent', 'EventCategory');
	public static $model_importers = array();

	public function init() {
		parent::init();
		
	}
	
	public function getSearchContext() {
		$context = parent::getSearchContext();
		
		if($this->modelClass == 'CalendarEvent') {
			$categories = EventCategory::get()->sort("Sort");
			$fields 	= $context->getFields();

			$fields->push(DropdownField::create('q[Category]', 'Category', $categories->map()->toArray())
				->setHasEmptyDefault(true)
			);
			$fields->push(CheckboxField::create('q[UserSubmitted]', 'Submitted by a user?'));
		}
		return $context;
	}
	
	public function getList() {
		$list = parent::getList();

		if($this->modelClass == 'CalendarEvent') { //ignore categories

			$params = $this->request->requestVar('q'); // use this to access search parameters
			
// 			$list = $list->filter(array('Start:GreaterThan' => date('Y-m-d H:i:s', strtotime("05-11-2014")), 'LegacyID' => 0));
			$list = $list->filter(array('Start:GreaterThan' => date('Y-m-d H:i:s')));
			$list = $list->leftJoin('CalendarEvent', '"SiteTree"."ID" = "EventsModelAdmin"."ID"', "EventsModelAdmin");
			$list = $list->sort('"EventsModelAdmin"."Start"');
			
			if ($this->action !="EditForm") { //only apply filters to the listing page
				if(isset($params['Category']) && $params['Category']){
					$list = $list->innerJoin("EventCategory_Events", '"EventCategory_Events"."CalendarEventID" = "CalendarEvent"."ID"');
					$list = $list->filter("EventCategory_Events.EventCategoryID", $params['Category']);
				}
				
				if(isset($params['UserSubmitted']) && $params['UserSubmitted']){
					$list = $list->where('"CalendarEvent"."SubmitterFirstName" IS NOT NULL OR "CalendarEvent"."SubmitterSurname" IS NOT NULL OR "CalendarEvent"."SubmitterEmail" IS NOT NULL OR "CalendarEvent"."SubmitterPhoneNumber" IS NOT NULL');
				}
			}
			
		}
		return $list;
	}
	
	
	public function getEditForm($id = null, $fields = null) {
		$form = parent::getEditForm($id, $fields);
	
		if($this->modelClass == 'EventCategory') {
			$gridField = $form->Fields()->fieldByName($this->sanitiseClassName($this->modelClass));
			$gridField->getConfig()->addComponent(new GridFieldOrderableRows('Sort')); //GridFieldSortableRows GridFieldOrderableRows
		}
	
		return $form;
	}
	
	/**
	 * @return array Map of class name to an array of 'title' (see {@link $managed_models})
	 */
	public function getManagedModels() {
		$models = $this->stat('managed_models');
		if(is_string($models)) {
			$models = array($models);
		}
		if(!count($models)) {
			user_error(
			'ModelAdmin::getManagedModels():
				You need to specify at least one DataObject subclass in public static $managed_models.
				Make sure that this property is defined, and that its visibility is set to "public"',
					E_USER_ERROR
			);
		}
	
		// Normalize models to have their model class in array key
		foreach($models as $k => $v) {
			if(is_numeric($k)) {
				if($v == 'EventCategory') {
					if(Subsite::currentSubsiteID() == 0){
						$models[$v] = array('title' => singleton($v)->i18n_singular_name());
					}
				}else{
					$models[$v] = array('title' => singleton($v)->i18n_singular_name());
				}
				
				unset($models[$k]);
			}
		}
	
		return $models;
	}
	
}
