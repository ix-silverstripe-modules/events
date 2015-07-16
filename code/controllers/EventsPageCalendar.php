<?php
/**
 * @package irxeventcalendar
 * @author 	Internetrix
 */
class EventsPageCalendar extends Controller {

	private static $url_handlers = array(
		'' => 'index'
	);

	private static $allowed_actions = array(
		'index'
	);

	protected $parent;
	protected $name;
	protected $month;
	protected $year;
	protected $day;
	protected $widecalendar = false;
	protected $member;

	public function __construct($parent, $name, $month = null, $year = null, $day = null) {
		if (!$month) $month = date('m');
		if (!$year)  $year  = date('Y');
		if (!$day)  $day  = date('d');// or d

		$this->parent = $parent;
		$this->name   = $name;
		$this->month  = $month;
		$this->year   = $year;
		$this->day    = $day;
		
		parent::__construct();
	}

	public function index($r) {
		if ($m = $r->getVar('m')) $this->month = (int) $m;
		if ($y = $r->getVar('y')) $this->year  = (int) $y;

		return $this->forTemplate();
	}

	public function forTemplate() {

		$time = mktime(null, null, null, $this->month, 1, $this->year);
		$prev = mktime(null, null, null, $this->month - 1, 1, $this->year);
		$next = mktime(null, null, null, $this->month + 1, 1, $this->year);

		return $this->render(array(
			'MonthName' => date('F', $time),
			'Year'      => $this->year,
			'PrevLink'  => $this->Link(sprintf('%s?m=%d&y=%d', $this->name, date('m', $prev), date('Y', $prev))),
			'NextLink'  => $this->Link(sprintf('%s?m=%d&y=%d', $this->name, date('m', $next), date('Y', $next))),
		));
	}

	public function Weeks() {
		$weeks  = new ArrayList();
		$start  = mktime(0, 0, 0, $this->month, 1, $this->year);
		$finish = mktime(0, 0, 0, $this->month + 1, 0, $this->year);

		if (date('N', $start) == 1) {
			$curr = $start;
		} else {
			$curr = strtotime('last monday', $start);
		}

		// Figure out which days have events on them.
		$nWeeks = ceil(($finish - $curr) / (3600 * 24 * 7));
		
		$conn   = DB::getConn();
		$events = new SQLQuery();
		
		$eventTable = 'CalendarEvent';
		if(Versioned::current_stage() == 'Live'){
			$eventTable .= '_Live';
		}
		

		$events = CalendarEvent::get()->where(sprintf(
				'(("CalendarEvent"."Start" <= \'%1$s\' AND "CalendarEvent"."End" >= \'%1$s\') OR ("CalendarEvent"."Start" BETWEEN \'%1$s\' AND \'%2$s\'))',
				date('Y-m-d', $curr),
				date('Y-m-d', $curr + $nWeeks * 3600 * 24 * 7))
		)->sort("Start");
		
		//get the categories whether they are from the search or from forcing them through the admin
		//$categories = $this->parent->getCalendarCategories();
// 		if($categories){
// 			$str  = "(";
// 			$str .= implode(",", $categories);
// 			$str .= ")";
// 			$events = $events->where('(SELECT COUNT("EventCategory_Events"."ID") FROM "EventCategory_Events" WHERE "EventCategory_Events"."CalendarEventID" = "'. $eventTable .'"."ID" AND "EventCategory_Events"."EventCategoryID" IN '. $str .')');
// 		}

		$eventDays = array();

		foreach ($events as $event) {
			$start = strtotime($event->Start);
			$end   = strtotime($event->End);
			
			while ($start <= $end) {
				$colour = null;
				$extra 	= null;
				
				$category = $event->Categories()->first();
				if($category){
					$colour = $category->Colour;
					$extra = "style='color: #" .$colour. "'";
				}
				
				$title = "<li style='list-style: none;'><span $extra>" . $event->MenuTitle . "</span></li>";
				
				$eventDays[date('m', $start)][date('d', $start)]['Title'][]   	= $title;
				$eventDays[date('m', $start)][date('d', $start)]['ID'][] 		= $event->ID;
				$eventDays[date('m', $start)][date('d', $start)]['Colour'][] 	= $colour;
				
				$start += 3600 * 24;
			}
		}

		// Now loop through and build a set of weeks and days.
		while ($curr <= $finish) {
			$days = new ArrayList();

			for ($i = 0; $i < 7; $i++) {
				$d   = date('d', $curr);
				$m   = date('m', $curr);
				$ymd = date('Y-m-d', $curr);
				if($d == $this->day) $selected = true;
				else $selected = false;
				$curr = strtotime('+1 day', $curr);
				
				$ids = "";
				$titles = "";
				$colours = "";
				if(isset($eventDays[$m][$d])){
					$titles	 		= $eventDays[$m][$d]['Title'];
					$titles 		= implode(" ", $titles);
					$ids	 		= $eventDays[$m][$d]['ID'];
					$ids			= implode(" ", $ids);
					$colours 		= $eventDays[$m][$d]['Colour'];
					if(is_array($colours)){
						$assc = array();
						foreach ($colours as $c){
							$assc[] = ArrayData::create(array('Colour' => $c));
						}
						
						$colours = ArrayList::create($assc);
					}
// 					$uniqueColours 	= array_unique($colours);
// 					if(count($uniqueColours) > 1){
// 						$colours = SiteConfig::current_site_config()->MixedColour;
// 					}else{
// 						$colours = $colours[0];
// 					}
				}
				
				$days->push(new ArrayData(array(
					'Num'      => $d,
					'Link'     => $this->DayLink('day/' . $ymd),
					'InMonth'  => $m == $this->month,
					'Past'     => $m < date('m'),
					'Today'    => $ymd == date('Y-m-d'),
					'HasEvent' => isset($eventDays[$m][$d]),
					'Title'	   => $titles,
					'EventID'  => $ids,
					'Colours'  => $colours,
					'Selected' => $selected
				)));
			}

			$weeks->push($days);
		}

		return $weeks;
	}

	public function Link($action = null) {
		return Controller::join_links($this->parent->Link(), $action);
	}
	
	public function DayLink($action = null) {
		$eventsPage = EventsPage::get()->first();
		if($eventsPage){
			return Controller::join_links($eventsPage->Link(), $action);
		}
	}
	
	public function setForcedCategories($forcedCategories) {
		$this->forcedCategories = $forcedCategories;
	}
	
	public function setWideCalendar() {
		$this->widecalendar = true;
	}
	
	public function getWideCalendar(){
		return $this->widecalendar;
	}

}