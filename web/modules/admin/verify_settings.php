<div class="content wide">
<?
global  $module, $method, $path, $action, $page, $limit, $fields_for_extensions, $operations_for_extensions, $upload_path;

if(!isset($_SESSION["verify_admin_pass"]))
{
	if(!$action)
		verify_admin_pass();
	else{
		$call = "verify_admin_pass_".$action;
		$call();
	}
}
$_SESSION["verified_settings"] = "clear";
echo '<meta http-equiv="refresh" content="10">'; 

function verify_admin_pass()
{
	$admin = Model::selection("user", array("username"=>"admin", "password"=>"admin"));

	if(!count($admin)) {
		$_SESSION["verify_admin_pass"] = "clear";
		return;
	}

	notice("System has detected that you are still using the default user and default password. Please use the below form in order to change the password for the default user.", 'no');

	$array = array(
					"new_password" => array("display"=>"password", "compulsory"=>true),
					"retype_new_password" => array("display"=>"password", "compulsory"=>true)
				);

	start_form();
	addHidden("database");
	editObject(NULL, $array, "Change default password.", "Save");
	end_form();
}

function verify_admin_pass_database()
{
	$password = getparam("new_password");
	$retype = getparam("retype_new_password");
	if($password != $retype) {
		notice("The two passwords don't match", "verify_admin_pass", false);
		return;
	}
	if(strlen($password)<6) {
		notice("Password must be at least 6 digits long", "verify_admin_pass", false);
		return;
	}

	$admin = Model::selection("user", array("username"=>"admin"));
	if(!count($admin)) {
		$_SESSION["verify_admin_pass"] = "clear";
		return;
	}
	$admin = $admin[0];
	$admin->password = $password;
	$res = $admin->update();
	if($res[0]) {
		$_SESSION["verify_admin_pass"] = "clear";
		notice("Password was changed", 'no');
	}else{
		notice("Could not change password", "verify_admin_pass", false);
	}
	return;
}
?>
</div>