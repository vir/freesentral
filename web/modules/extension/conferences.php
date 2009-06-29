<div class="content wide">
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

$call();

function conferences()
{
	$conferences = Model::selection("did", array("destination"=>"LIKEconf/"), "did");
	$fields = array("function_get_conf_from_did:conference"=>"did", "number", "function_conference_participants:participants"=>"number");
	tableOfObjects($conferences, $fields, "conference",array());
}

?>
</div>