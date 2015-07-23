<?php

define('EVENTCALENDAR_DIR', 'events');

$dir = basename(dirname(__FILE__));
if($dir != "events") {
	user_error('Directory name must be "events" (currently "'.$dir.'")',E_USER_ERROR);
}

// Change the relationship of EditableFormField
if(class_exists('EditableFormField')) {
	$editableHasOne = Config::inst()->get('EditableFormField', 'has_one');
	if(isset($editableHasOne['Parent'])){
		$editableHasOne['Parent'] = 'Page';
	}
	Config::inst()->remove('EditableFormField', 'has_one');
	Config::inst()->update('EditableFormField', 'has_one', $editableHasOne);
}

// Change the relationship of SubmittedForm
if(class_exists('SubmittedForm')) {
	$editableHasOne = Config::inst()->get('SubmittedForm', 'has_one');
	if(isset($editableHasOne['Parent'])){
		$editableHasOne['Parent'] = 'Page';
	}
	Config::inst()->remove('SubmittedForm', 'has_one');
	Config::inst()->update('SubmittedForm', 'has_one', $editableHasOne);
}