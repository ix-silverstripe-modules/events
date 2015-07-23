<?php

define('EVENTCALENDAR_DIR', 'events');

$dir = basename(dirname(__FILE__));
if($dir != "events") {
	user_error('Directory name must be "events" (currently "'.$dir.'")',E_USER_ERROR);
}

// Change the relationship
if(class_exists('EditableFormField')) {
	$editableHasOne = Config::inst()->get('EditableFormField', 'has_one');
	if(isset($editableHasOne['Parent'])){
		$editableHasOne['Parent'] = 'Page';
	}
	Config::inst()->remove('EditableFormField', 'has_one');
	Config::inst()->update('EditableFormField', 'has_one', $editableHasOne);
}