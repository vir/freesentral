<?
require_once("set_debug.php");
require_once("config.php");
require_once("lib.php");
require_once("menu.php");
require_once("lib_custom.php");

include_classes();

if (!isset($_SESSION['user']) || !isset($_SESSION['level']))
	header ("Location: index.php");


if (isset($_GET['skin']))
    $_SESSION['skin']=$_GET['skin'];

$module = NULL;
$method = NULL;

if(getparam("method") == "impersonate") {
	if($_SESSION["level"] != "admin")
		forbidden();
	Model::writeLog("impersonate ".getparam("extension"));
	$_SESSION["real_user"] = $_SESSION["user"];
	$_SESSION["user"] = getparam("extension");
	$_SESSION["username"] = $_SESSION["user"];
	$_SESSION["level"] = "extension";
	$module = "HOME";
	$method = "HOME";
}elseif(getparam("method") == "stop_impersonate"){
	if(!isset($_SESSION["real_user"])) 
		forbidden();
	$user = new User;
	$user->select(array("username"=>$_SESSION["real_user"]));
	if(!$user->user_id)
		forbidden();
	$impersonated = $_SESSION["user"];
	$_SESSION["user"] = $_SESSION["real_user"];
	$_SESSION["username"] = $_SESSION["real_user"];
	Model::writeLog("stop impersonate ".$impersonated);
	$_SESSION["level"] = "admin";
	if(isset($_POST["method"]))
	$_POST["method"] = NULL;
	if(isset($_GET["method"]))
	$_GET["method"] = NULL;
	unset($_SESSION["real_user"]);
	$module = "HOME";
	$method = "HOME";
}

$dir = $_SESSION['level'];
$level = $_SESSION['level'];
testpath($dir);

$module = (!$module) ? getparam("module") : $module;
if(!$module)
	if(is_file("modules/$dir/HOME.php"))
		$module = "HOME";
testpath($module);

$path = $module;

$action = getparam("action");
$method = (!$method) ? getparam("method") : $method;

$page = getparam("page");
if(!$page)
	$page = 0;

$_SESSION["limit"] = (isset($_SESSION["limit"])) ? $_SESSION["limit"] : 20;
$limit = getparam("limit") ? getparam("limit") : $_SESSION["limit"];
$_SESSION["limit"] = $limit;

if($method == "manage")
	$method = $module;

$_SESSION["main"] = "main.php";
$iframe = getparam("iframe");
/*
if(!isset($_SESSION["verified_settings"]))
	$module = "verify_settings";
*/

if($level == "admin" && $_SESSION["wizard"] == "notused") {
	$module = "verify_settings";
}
?>

<html>
<head>
<title>FreeSentral</title>
<?    include "javascript.php"; ?>
<link type="text/css" rel="stylesheet" href="main.css"/>
</head>
<!-- <body style="margin: 0 0 0 0;" background="images/sigla.png" bgproperties="fixed"> -->
<body class="mainbody">
	<? get_content();?>
</body>
</html>
