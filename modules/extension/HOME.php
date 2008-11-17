<?

global $module, $method, $path, $action, $page, $target_path;

if(!$method)
	$method = $module;

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

if($call != "HOME")
	print '<div class="content wide">';
$call();
if($call != "HOME")
	print '</div>';

function home()
{
	print '<div class="title wide">:: Voicemail ::</div>';
	print '<div class="content wide">';
	voicemail(5);
	print '</div>';
	print '<div class="title wide">:: Activity ::</div>';
	print '<div class="content wide">';
	activity();
	print '</div>';
}

function activity()
{
	$call_log = new Call_Log;
	$conditions = array();

	$conditions[0] = array("caller"=>$_SESSION["user"], "called"=>$_SESSION["user"]);
	$conditions["direction"] = "outgoing";

	$call_logs = Model::selection("call_log",$conditions,"time DESC",5);

	$columns = array("time"=>true, "address"=>false, "billid"=>false, "caller"=>true, "called"=>true, "duration"=>true, "billtime"=>false, "ringtime"=>false, "status"=>true, "reason"=>false, "ended"=>false);

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
	$formats["function_getdirection:direction"] = "caller,called";

	tableOfObjects($call_logs, $formats, "call log");
}

?>