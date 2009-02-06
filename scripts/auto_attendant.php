#!/usr/bin/php -q
<?
require_once("lib_queries.php");
require_once("libyate.php");


$ourcallid = "auto_attendant/" . uniqid(rand(),1);

$wait_time = 4; //number of seconds that script has to wait after user input in order to see if another digit will be pressed
$hold_keys = '';
$count = false; //start to count seconds since a digit has been pressed
$state = "enter";

/* Perform machine status transitions */
function setState($newstate)
{
    global $ourcallid;
    global $partycallid;
    global $state;
	global $uploaded_prompts;
	global $keys;
	global $wait_time;
	global $caller;
	global $hold_keys;
	global $destination;
	global $called;
	global $ev;

    // are we exiting?
    if ($state == "")
	return;

    Yate::Debug("setState('$newstate') state: $state");

	$state = $newstate;
    // always obey a return to prompt
    switch ($newstate) {
		case "greeting":
			// check what prompt to use for this time of day
			$state = $newstate;
			$query = "select prompts.prompt_id, prompts.file as prompt from time_frames, prompts where numeric_day=extract(dow from now()) and cast(start_hour as integer)<=extract(HOUR FROM now()) AND cast(end_hour as integer)>extract(HOUR FROM now()) and time_frames.prompt_id=prompts.prompt_id UNION select prompt_id,  file as prompt from prompts where status='offline'";
			$res = query_to_array($query);
			if(!count($res))
			{
				Yate::Output("Auto-Attendant is not configured!!");
				setState("goodbye");
				return;
			}
			$prompt_id = $res[0]["prompt_id"];
			$prompt =  $res[0]["prompt"];
			// here we must have ".au"
			$prompt = str_ireplace(".mp3", ".slin", $prompt);
			$query = "SELECT key, destination FROM keys WHERE prompt_id=$prompt_id";
			$keys = query_to_array($query);
			$m = new Yate("chan.attach" );
			$m->params["source"] = "wave/play/$uploaded_prompts/auto_attendant/$prompt";
			$m->params["notify"] = $ourcallid;
			$m->Dispatch();
			break;
		case "prolong_greeting":
			$m = new Yate("chan.attach");
			$m->params["consumer"] = "wave/record/-";
			$m->params["notify"] = $ourcallid;
			$m->params["maxlen"] = $wait_time*1000;
			$m->Dispatch();
			break;
		case "goodbye":
			$m = new Yate("chan.attach");
			$m->params["source"] = "tone/congestion";
			$m->params["consumer"] = "wave/record/-";
			$m->params["maxlen"] = 32000;
			$m->params["notify"] = $ourcallid;
			$m->Dispatch();
			break;
		case "call.route":
		//	$called = $hold_keys;
			for($i=0; $i<count($keys); $i++)
			{
				if($keys[$i]["key"] == $hold_keys)
				{
					$called = $keys[$i]["destination"];
					break;
				}
			}
			if($hold_keys == '')
			{
				$query = "SELECT (CASE WHEN default_destination='extension' THEN (SELECT extension FROM extensions WHERE extensions.extension_id=dids.extension_id) ELSE (SELECT extension FROM groups WHERE groups.group_id=dids.group_id) END) as called FROM dids WHERE number='$called'";
				$res = query_to_array($query);
				if(!count($res)) {
					// this should never happen
					setState("goodbye");
					return;
				}
				$called = $res[0]["called"];
			}
			$m = new Yate("call.route");
			$m->params["caller"] = $caller;
			$m->params["called"] = $called;
			$m->params["id"] = $ourcallid;
			$m->Dispatch();
		case "send_call":
			$m = new Yate("chan.masquerade");
			$m->params = $ev->params;
			$m->params["message"] = "call.execute";
			$m->params["id"] = $partycallid;
			$m->params["callto"] = $destination;
			$m->Dispatch();
	}
}

/* Handle all DTMFs here */
function gotDTMF($text)
{
	global $state;
	global $keys;
	global $destination;
	global $hold_keys;
	global $count;

	Yate::Debug("gotDTMF('$text') state: $state");

	$count = true;
	switch ($state) {
	case "greeting":
	case "prolong_greeting":
		if($text != "#" && $text != "*")
			$hold_keys .= $text;
		else {
			//i will consider that this are accelerating keys
			setState("call.route");
			break;
		}
		return;
	}
}

function gotNotify($reason)
{
	global $state;

	Yate::Debug("gotNotify('$reason') state: $state");
	if ($reason == "replaced")
		return;

	switch($state)
	{
		case "greeting":
			setState("prolong_greeting");
			break;
		case "prolong_greeting":
			setState("call.route");
			
	}	
}

Yate::Init();
Yate::Debug(true);
/* Install filtered handlers for the wave end and dtmf notify messages */
// chan.dtmf should have a greater priority than that of pbxassist(by default 15)
Yate::Install("chan.dtmf",10,"targetid",$ourcallid);
Yate::Install("chan.notify",100,"targetid",$ourcallid);
Yate::Install("engine.timer",100);

/* The main loop. We pick events and handle them */
while ($state != "") {
    $ev=Yate::GetEvent();
    /* If Yate disconnected us then exit cleanly */
    if ($ev === false)
	break;
    /* No need to handle empty events in this application */
    if ($ev === true)
	continue;
    /* If we reached here we should have a valid object */
    switch ($ev->type) {
	case "incoming":
	    switch ($ev->name) {
		case "call.execute":
		    $partycallid = $ev->GetValue("id");
			$caller = $ev->GetValue("caller");
			$called = $ev->GetValue("called");
		    $ev->params["targetid"] = $ourcallid;
		    $ev->handled = true;
		    /* We must ACK this message before dispatching a call.answered */
		    $ev->Acknowledge();
		    /* Prevent a warning if trying to ACK this message again */
		    $ev = false;

		    /* Signal we are answering the call */
		    $m = new Yate("call.answered");
		    $m->params["id"] = $ourcallid;
		    $m->params["targetid"] = $partycallid;
		    $m->Dispatch();

			setState("greeting");
		    break;

		case "chan.notify":
		    gotNotify($ev->GetValue("reason"));
		    $ev->handled = true;
		    break;

		case "chan.dtmf":
		    $text = $ev->GetValue("text");
		    for ($i = 0; $i < strlen($text); $i++)
			gotDTMF($text[$i]);
		    $ev->handled = true;
		    break;

		case "engine.timer":
			if(!$count)
				break;
			if($count === true)
				$count = 1;
			else
				$count++;
			if($count == $wait_time)
				setState("call.route");
			break;
	    }
	    /* This is extremely important.
	       We MUST let messages return, handled or not */
	    if ($ev)
		$ev->Acknowledge();
	    break;
	case "answer":
	     Yate::Debug("PHP Answered: " . $ev->name . " id: " . $ev->id);
	    if ($ev->name == "call.route") {
			$destination = $ev->retval;
			setState("send_call");
		}
	    break;
	case "installed":
	    // Yate::Debug("PHP Installed: " . $ev->name);
	    break;
	case "uninstalled":
	    // Yate::Debug("PHP Uninstalled: " . $ev->name);
	    break;
	default:
	    // Yate::Output("PHP Event: " . $ev->type);
    }
}

Yate::Output("Auto Attendant: bye!");

/* vi: set ts=8 sw=4 sts=4 noet: */
?>