<?php

class ApproveCalendarEventManyManyRelations extends BuildTask
{
	protected $title 		= 'Approve Calendar Event Many Many Relations';
	protected $description 	= 'Approve Calendar Event Many Many Relations';
	
	public function run($request) {
		increase_time_limit_to();
		
		DB::query("UPDATE \"EventCategory_Events\" SET \"Approved\" = 1");
		
		DB::alteration_message("Job Done. Set approved");
	}

}
