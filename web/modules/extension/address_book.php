<div class="content wide">
<?
global $module, $method, $path, $action, $page, $limit, $fields_for_extensions, $operations_for_extensions, $upload_path;

if(!$method || $method == "manage")
	$method = $module;

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

$call();

function address_book()
{
	$short_names = Model::selection("short_name", NULL, "short_name");	

	tableOfObjects($short_names, array("short_name", "number", "name"), "short_name");
}

?>
</div>