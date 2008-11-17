<div class="content wide">
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

$call();

?>
</div>