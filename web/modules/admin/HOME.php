<?
global $module,$method,$action;

if(!$method)
	$method = strtolower($module);

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($method == "edit_admin")
	$method = "edit_user";

if($method == "manage")
	$method = "home";

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

$call = strtolower($call);

if($call != "home")
	print '<div class="content wide">';
$call();
if($call != "home")
	print '</div>';

function home()
{
	print '<table><tr><td class="topvalign">';
	print '<div class="homecontent">';
	//$actions = array("&module=auto_attendant&method=wizard"=>array());

	print '<table class="hometable" cellspacing="0" cellpadding="0">';
		print '<tr>';
			print '<td>
							<table class="home_opt" cellspacing="0" cellpadding="0" onClick="location.href=\'main.php?module=auto_attendant&method=wizard\'">
								<tr onMouseover="this.bgColor=\'#dcf0f2\'" onMouseout="this.bgColor=\'#EEEEEE\'">';
									print '<td class="hometable">';
										print '<img src="images/auto-attendant.png"/>';
									print '</td>';
									print '<td class="hometable description">';
										print 'Auto&nbsp;Attendant';
									print '</td>';
								print '</tr>
							</table>
					</td>';
					print '<td>
								<table class="home_opt" cellspacing="0" cellpadding="0" onClick="location.href=\'main.php?module=extensions&method=add_extension\'">
									<tr onMouseover="this.bgColor=\'#dcf0f2\'" onMouseout="this.bgColor=\'#EEEEEE\'">';
										print '<td class="hometable">';
											print '<img src="images/extension.png"/>';
										print '</td>';
										print '<td class="hometable description">';
												print 'Add&nbsp;Extension';
										print '</td>';
									print '</tr>
								</table>
						</td>';
		print '</tr>';

		print '<tr>';
			print '<td>
							<table class="home_opt" cellspacing="0" cellpadding="0" onClick="location.href=\'main.php?module=outbound&method=add_gateway\'">
								<tr onMouseover="this.bgColor=\'#dcf0f2\'" onMouseout="this.bgColor=\'#EEEEEE\'">';
									print '<td class="hometable">';
										print '<img src="images/gateways.png"/>';
									print '</td>';
									print '<td class="hometable description">';
										print 'Add&nbsp;Gateway';
									print '</td>';
								print '</tr>
							</table>
					</td>';
			print '<td>
							<table class="home_opt" cellspacing="0" cellpadding="0" onClick="location.href=\'main.php?module=address_book&&method=add_short_name\'">
								<tr onMouseover="this.bgColor=\'#dcf0f2\'" onMouseout="this.bgColor=\'#EEEEEE\'">';
									print '<td class="hometable">';
										print '<img src="images/address_book.png"/>';
									print '</td>';
									print '<td class="hometable description">';
										print 'New&nbsp;Address&nbsp;Book&nbsp;Entry';
									print '</td>';
								print '</tr>
							</table>
					</td>';
		print '</tr>';

		print '<tr>';
			print '<td>
							<table class="home_opt" cellspacing="0" cellpadding="0" onClick="location.href=\'main.php?module=outbound&method=add_dial_plan\'">
								<tr onMouseover="this.bgColor=\'#dcf0f2\'" onMouseout="this.bgColor=\'#EEEEEE\'">';
									print '<td class="hometable">';
										print '<img src="images/dial_plan.png"/>';
									print '</td>';
									print '<td class="hometable description">';
										print 'Add&nbsp;Dial&nbsp;Plan';
									print '</td>';
								print '</tr>
							</table>
				</td>';
			print '<td>
							<table class="home_opt" cellspacing="0" cellpadding="0" onClick="location.href=\'main.php?module=dids&method=add_did\'">
								<tr onMouseover="this.bgColor=\'#dcf0f2\'" onMouseout="this.bgColor=\'#EEEEEE\'">';
									print '<td class="hometable">';
										print '<img src="images/dids.png"/>';
									print '</td>';
									print '<td class="hometable description">';
										print 'Add&nbsp;DID';
									print '</td>';
								print '</tr>
							</table>
				</td>';
		print '</tr>';
	print '</table>';
	print '</div>';
	print '</td><td class="topvalign">';
	print '<div class="copac copachome">';
	$status = exec("/etc/init.d/yate status");
	print '<div class="titlu">SYSTEM STATUS</div>';
	print '<div class="systemstatus"> '.
			
		'
			<div style="float:right;"> Today, '.date('h:i a').'

		</div>Yate: '.$status;
	print '</div>';
print '<br/><br/>';
	print '</td></tr></table>';
}

/*
function home()
{
	print '<div class="title wide">:: Ongoing Calls ::</div>';
	print '<div class="content wide">';
	ongoing_calls(5);
	print '</div>';
	print '<div class="title wide">:: Logs ::</div>';
	print '<div class="content wide">';
	logs(5);
	print '</div>';
}*/

function logs($lim = NULL)
{
	global $limit,$page;

	$use_limit = ($lim) ? $lim : $limit;

	if(!$lim)
	{
		$total = getparam("total");
		$actionlog = new ActionLog;
		$total = $actionlog->fieldSelect("count(*)");
		items_on_page();
		pages($total);
	}

	$logs = Model::selection("actionlog",NULL,"date DESC",$use_limit,$page);
	tableOfObjects($logs,array("function_select_date:date"=>"date", "function_select_time:time"=>"date","performer", "log"),"log");
}

function ongoing_calls($lim = NULL)
{
	global $limit,$page;

	$use_limit = ($lim) ? $lim : $limit;
	$total = getparam("total");
	$call_log = new Call_Log;
	$total = $call_log->fieldSelect("count(*)",array("ended"=>false));
	if(!$lim)
	{
		items_on_page();
		pages($total);
	}
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
	$call_logs = Model::selection("call_log",array("ended"=>false), "time DESC", $use_limit, $page);

	if(!$total)
		$total = count($call_logs);
	if($total)
		if($total != 1)
			print "There are ".$total." ongoing calls in the system.<br/><br/>";
		else
			print "There is 1 ongoing call in the system.<br/><br/>";

	tableOfObjects($call_logs,$formats, "ongoing call");
}

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