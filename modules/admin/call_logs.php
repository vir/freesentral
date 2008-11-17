<div class="content wide">
<?

global $module, $method, $action;

if(!$method)
	$method = $module;

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

$call();

function call_logs($error = NULL)
{
	if($error)
		errornote($error);

	$caller = getparam("caller");
	$called = getparam("called");

	$direction = array("incoming", "outgoing", "both");
	$fields = array(
					"caller"=>array("value"=>$caller),
					"called"=>array("value"=>$called),
					"from_date"=>array("display"=>"month_day_year_hour"),
					"to_date"=>array("display"=>"month_day_year_hour_end"),
					"available_columns"=>array("display"=>"available_call_logs_columns", "comment"=>"Check the columns you wish to be displayed"),
					"direction"=>array($direction, "display"=>"select", "comment"=>"There are two call legs for each call:incoming and outgoing(both have the same billid). If you don't select anything, incoming calls will be displayed."),
				);

	start_form();
	addHidden("database");
	editObject(NULL,$fields,"Call Logs","Go",false,false,"widder_edit",NULL,array("left"=>"90px","right"=>"440px"));
	end_form();
}

function available_call_logs_columns()
{
	$columns = array("time"=>true, "chan"=>false, "address"=>false, "direction"=>false, "billid"=>false, "caller"=>true, "called"=>true, "duration"=>true, "billtime"=>false, "ringtime"=>false, "status"=>true, "reason"=>false, "ended"=>false);

	foreach($columns as $name=>$display)
	{
		print '<input type="checkbox" name="col_'.$name.'"';
		if(getparam("col_".$name) == "on" || $display == true)
			print ' CHECKED ';
		print '/>&nbsp;'.$name.' ';
	}
}

function call_logs_database()
{
	global $limit,$page;

	$from = get_date(getparam("from_datehour"),'00',"from_date");
	$to = get_date(getparam("to_datehour"),'59',"to_date");
	$conditions = array("time"=>array(">$from", "<$to"));

	$direction = getparam("direction");
	if($direction == "incoming" || $direction == "outgoing")
		$conditions["direction"] = $direction;
	elseif($direction == "Not selected" || $direction == "")
		$conditions["direction"] = "incoming";

	$caller = getparam("caller");
	if($caller)
		$conditions["caller"] = $caller;
	$called = getparam("called");
	if($called)
		$conditions["called"] = $called;

	$total = getparam("total");
	if(!$total)
	{
		$call_log = new Call_Log;
		$total = $call_log->fieldSelect('count(*)',$conditions);
	}

	$call_logs = Model::selection("call_log",$conditions,"time DESC",$limit,$page);

	$columns = array("time"=>true, "chan"=>false, "address"=>false, "direction"=>false, "billid"=>false, "caller"=>true, "called"=>true, "duration"=>true, "billtime"=>false, "ringtime"=>false, "status"=>true, "reason"=>false, "ended"=>false);

	$formats = array();
	foreach($columns as $key=>$display)
	{
		if(!(getparam("col_".$key)=="on" || $display == true))
			continue;
		if($key != "time")
			array_push($formats, $key);
		else{
			$formats["function_select_date:date"] = "time";
			$formats["function_select_time:time"] = "time"; 
		}
	}

	if(count($call_logs))
		items_on_page();
	pages($total);
	tableOfObjects($call_logs, $formats, "call log");
}

?>
</div>