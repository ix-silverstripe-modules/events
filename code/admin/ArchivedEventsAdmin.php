<?php
class ArchivedEventsAdmin extends VersionedModelAdmin {
	
	private static $title       = 'Archived Events';
	private static $menu_title  = 'Archived';
	private static $url_segment = 'archivedevents';
	private static $menu_icon 	=  'irxeventcalendar/images/icons/calendar_icon.png';

	private static $managed_models  = array('CalendarEvent');
	private static $model_importers = array();
	
	private static $menu_priority = -0.6;
	
	public function getList() {
		$list = parent::getList();
		$list = $list->filter(array('Start:LessThan' => date('Y-m-d H:i:s')));
		$list = $list->sort("Start DESC");
		return $list;
	}
	
	public function getEditForm($id = null, $fields = null) {
		$form = parent::getEditForm($id, $fields);
	
		if($this->modelClass == 'CalendarEvent') {
			$gridField = $form->Fields()->fieldByName($this->sanitiseClassName($this->modelClass));
			$gridField->getConfig()->removeComponentsByType('GridFieldAddNewButton');  
		} 
	
		return $form;
	}
	
}