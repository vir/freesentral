<div class="content wide">
<?
global $module, $method, $path, $action, $page, $target_path, $iframe;

require_once("lib_auto_attendant.php");

if(!$method || $method == "auto_attendant")
	$method = "wizard";

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

$call();

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
</div>
