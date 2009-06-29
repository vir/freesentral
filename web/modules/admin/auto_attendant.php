<!--<div class="content wide">-->
<?
global $module, $method, $path, $action, $page, $target_path, $iframe;

require_once("lib_auto_attendant.php");

if(!getparam("method") || getparam("method") == "auto_attendant")
	$method = "wizard";

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

$explanation = array(
	"default" => "Auto Attendant: Calls within the PBX are answered and directed to their desired destination by the auto attendant system.<br/><br/>The keys you define must match the prompts you uploaded. <br/><br/>The Auto Attendant has two states: online and offline. Each of this states has its own prompt.<br/><br/>If your online prompt says: Press 1 for Sales, then you must select type: online, key: 1, and insert group: Sales (you must have defined Sales in the Groups section). Same for offline state. <br/><br/>If you want to send a call directly to an extension or another number, you should insert the number in the 'Number(x)' field from Define Keys section.", 
	"keys" => "If your online prompt says: Press 1 for Sales, then you must select type: online, key: 1, and insert group: Sales (you must have defined Sales in the Groups section). Same for offline state. <br/><br/>If you want to send a call directly to an extension or another number, you should insert the number in the 'Number(x)' field from Define Keys section.", 
	"prompts" => "The Auto Attendant has two states: online and offline. Each of this states has its own prompt.", 
	"scheduling" => "Schedulling online Auto Attendant. The time frames when the online auto attendant is not schedulled, the offline one is used."
);

explanations("images/auto-attendant.png", "", $explanation);

print '<div class="content">';
$call();
print '</div>';

function wizard($error = NULL)
{
	if($error)
		errornote($error);

	prompts(true);

	keys(true);

	scheduling(NULL,true);

	activate();
}

?>
<!--</div>-->
