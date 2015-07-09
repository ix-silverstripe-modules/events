<?php

define('IRXEVENTCALENDAR_DIR', 'irxeventcalendar');

$dir = basename(dirname(__FILE__));
if($dir != "irxeventcalendar") {
	user_error('Directory name must be "irxeventcalendar" (currently "'.$dir.'")',E_USER_ERROR);
}