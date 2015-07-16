<?php

define('EVENTCALENDAR_DIR', 'events');

$dir = basename(dirname(__FILE__));
if($dir != "events") {
	user_error('Directory name must be "events" (currently "'.$dir.'")',E_USER_ERROR);
}