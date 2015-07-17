<?php
class EventPageExtension extends DataExtension {
	
	private static $db = array(
		'ShowCalendar' 		 	=> 'Boolean',
		'HideMonthJumper' 		=> 'Boolean',
		'ShowUpcomingEvents' 	=> 'Boolean',
		'UpcomingEventsCount' 	=> 'Int'
	);
	
	private static $many_many = array(
		'ForcedCalendarCategories' => 'EventCategory'
	);
	
	private static $defaults = array(
			'UpcomingEventsCount' 	=> 3
	);
	
	public function IRXupdateCMSFields(FieldSet &$fields, $tab = 'Root.SideBar', $insertBefore = 'PageBannersHeading') {
		$tab = ($tab ? $tab : 'Root.SideBar');

		$fields->addFieldToTab($tab, HeaderField::create('EventOptions', 'Event Options'), $insertBefore);
		
		$fields->addFieldToTab($tab, CheckboxField::create('ShowCalendar', 'Show the calendar?'), $insertBefore);
		$fields->addFieldToTab($tab, CheckboxSetField::create(
				'ForcedCalendarCategories',
				'Only show events belonging to the following categories in the calendar',
				EventCategory::get(),
				$this->owner->ForcedCalendarCategories()
		)->displayIf("ShowCalendar")->isChecked()->end(), $insertBefore);

		$fields->addFieldToTab($tab, CheckboxField::create('HideMonthJumper', 'Hide the month jumper?'), $insertBefore);

		$fields->addFieldToTab($tab, CheckboxField::create('ShowUpcomingEvents', 'Show upcoming events?'), $insertBefore);
		$fields->addFieldToTab($tab, NumericField::create('UpcomingEventsCount', 'How many upcoming events to show in the sidebar?')
				->displayIf('ShowUpcomingEvents')->isChecked()->end(), $insertBefore);
		
		return $fields;
	}
	
	public function UpcomingEvents(){
		if($this->owner->ShowUpcomingEvents) {
			$limit = $this->owner->UpcomingEventsCount? $this->owner->UpcomingEventsCount : 3;
			return CalendarEvent::get()->filter(array('Start:GreaterThan' => date('Y-m-d H:i:s')))->limit($limit)->sort("Start");
		}
	}
	
	public function eventcalendar() {
		$calendar = new EventsPageCalendar($this->owner, 'eventcalendar');
		return $calendar;
	}
	
	public function onBeforeWrite(){
		parent::onBeforeWrite();
	
		if(!$this->owner->UpcomingEventsCount){
			$this->owner->UpcomingEventsCount = 3;
		}
	}
	
	/**
	 * @return Form
	 */
	public function MonthForm() {
		$months = array_flip(range(1, 12));
	
		foreach ($months as $num => &$name) {
			$name = date('F', mktime(null, null, null, $num, 1));
		}
	
		$monthField = DropdownField::create('Month', '', $months)
			->setEmptyString('Month...')
			->addExtraClass('dropdown');
		$yearField = DropdownField::create('Year', '', $this->getYearRange())
			->setEmptyString('Year...')
			->addExtraClass('dropdown');
		$categoryField = SelectAllCheckboxSetField::create('Categories', 'Categories', EventCategory::get(), $this->owner->getCalendarCategories());
	
	
		$fields = new FieldList($monthField, $yearField, $categoryField);
	
		$formAction = new FormAction('search', ' ');
		$formAction->addExtraClass('events-submit');
	
		$actions = new FieldList( $formAction);
	
		$form = new Form($this->owner, 'MonthForm', $fields, $actions, new RequiredFields( 'Year'));
		$form->disableSecurityToken();
		$form->setFormMethod('GET');
		
		$eventsPage = EventsPage::get()->first();
		if($eventsPage){
			$form->setFormAction($eventsPage->Link('search'));
		}

	
		if($data = Session::get("MonthForm")){
			Session::clear("MonthForm");
			$form->loadDataFrom($data);
		}
	
// 		$this->owner->extend('updateMonthForm', $form);
	
		return $form;
	}
	
	/**
	 * Returns an array of years that encompasses all events as well as the
	 * current year.
	 *
	 * @return array
	 */
	protected function getYearRange() {
		$conn = DB::getConn();
		$year = date('Y');
	
		$min   = $conn->formattedDatetimeClause('MIN("Start")', '%Y');
		$max   = $conn->formattedDatetimeClause('MAX("End")', '%Y');
		$range = DB::query("SELECT $min AS \"min\", $max AS \"max\" FROM \"CalendarEvent\"")->record();
	
		$min = min($range['min'], $year - 1);
		$max = max($range['max'], $year + 1);
		return ArrayLib::valuekey(range($max, $min));
	}
	
	public function getCalendarCategories(){
		$forced = $this->owner->ForcedCalendarCategories();
		if($forced->Count() > 0){
			return $forced->map('ID', 'ID')->toArray();
		}
	}
	
// 	public function contentcontrollerInit() {
// 		Requirements::css(EVENTCALENDAR_DIR . '/thirdparty/qtip/jquery.qtip-2.0.0.css');
	
// 		Requirements::block(THIRDPARTY_DIR . '/jquery/jquery.js');
// 		Requirements::block(FRAMEWORK_DIR .'/thirdparty/jquery/jquery.js');
	
// 		Requirements::combine_files(EVENTCALENDAR_DIR . '.js', array(
// 		EVENTCALENDAR_DIR . '/javascript/EventsPageCalendar.js',
// 		EVENTCALENDAR_DIR . '/thirdparty/qtip/jquery.qtip-2.0.0.min.js'
// 				));
// 	}

}

class EventPageExtension_Controller extends Extension {
	public function onAfterInit() {
		
		Requirements::css(EVENTCALENDAR_DIR . '/thirdparty/qtip/jquery.qtip-2.0.0.css');
		
		Requirements::block(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::block(FRAMEWORK_DIR .'/thirdparty/jquery/jquery.js');
		
		Requirements::combine_files(EVENTCALENDAR_DIR . '.js', array(
		EVENTCALENDAR_DIR . '/thirdparty/qtip/jquery.qtip-2.0.0.min.js',
		EVENTCALENDAR_DIR . '/javascript/EventsPageCalendar.js'
				));
	}
}
