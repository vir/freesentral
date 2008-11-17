<div class="content wide">
<?
global $module,$method,$action;

if(!$method)
	$method = $module;

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($method == "edit_admin")
	$method = "edit_user";

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

$call();

function admins()
{
	global $method;
	$method = "admins";

	// select all the users in the system order by username
	$users = Model::selection("user", NULL, 'username');

	tableOfObjects($users,array("username","firstname","lastname","email"),"admin",array("&method=edit_user"=>'<img src="images/edit.gif" title="Edit" alt="edit"/>', "&method=delete_user"=>'<img src="images/delete.gif" title="Delete" alt="delete"/>'),array("&method=add_user"=>"Add admin"));
}

//generate form to edit or add a user
function edit_user($error = NULL)
{
	if($error)
		errornote($error);

	$user = new User;
	$user->user_id = getparam("user_id");
	$user->select();

	$fields = array(
						"username"=>array("display"=>"fixed", "compulsory"=>true), 
						"password"=>array("display"=>"password", "comment"=>"Minimum 5 digits. Insert only if you wish to change."),
						"email"=>array("compulsory"=>true),
						"firstname"=>"",
						"lastname"=>"",
						"description"=>array("display"=>"textarea")
				);
	if(!$user->user_id)
	{
		$fields["username"]["display"] = "text";
		$fields["password"]["compulsory"] = true;
		$fields["password"]["comment"] = "Minimum 5 digits.";
		$title = "Add admin";

		$var_names = array("username", "email", "firstname", "lastname", "description");
		for($i=0; $i<count($var_names); $i++)
			$user->{$var_names[$i]} = getparam($var_names[$i]);
	}else
		$title = "Edit admin ".$user->username;

	start_form();
	addHidden("database", array("user_id"=>$user->user_id));
	editObject($user, $fields, $title, "Save", true);
	end_form();	
}

//make the database operation associated to adding/editing a user
function edit_user_database()
{
	global $module;

	$user = new User;
	$user->user_id = getparam("user_id");
	if(!$user->user_id){
		$user->username = getparam("username");
		if($user->objectExists()) {
			edit_user('Admin '.$user->username.' already exists.');
			return;
		}
	}
	$user->select();
	if(!$user->user_id)
		$user->username = getparam("username");

	if(!$user->user_id && !getparam("password")) {
		edit_user("You must insert the password for the new admin.");
		return;
	}

	if(getparam("password")) {
		$user->password = getparam("password");
		if(strlen($user->password)<5) {
			edit_user('Password must be at least 5 digits long');
			return;
		}
	}

	$user->email = getparam("email");
	if(!check_valid_mail($user->email)) {
		edit_user('Email address that was inserted is not valid.');
		return;
	}
	$user->firstname = getparam("firstname");
	$user->lastname = getparam("lastname");
	$user->description = getparam("description");
//	if($user->user_id) 
//		notify($user->update());
//	else 
//		notify($user->insert(false));

	$res = ($user->user_id) ? $user->update() : $user->insert(false);
	notice($res[1], $module, $res[0]);
}

// user must acknowledge delete 
function delete_user()
{
	$user = new User;
	$user->user_id = getparam("user_id");
	$user->select();
	ack_delete('admin',$user->username,''/*$user->ackDelete()*/,"user_id",getparam("user_id"));
}

// perfom the delete option in the database
function delete_user_database()
{
	global $module;

	$user = new User;
	$user->user_id = getparam("user_id");
	if(!$user->user_id)
	{
		//errormess("Don't have user_id for performing the delete operation.");
		notice("Don't have user_id for performing the delete operation.", $module, false);
		return;
	}
	//notify($user->objDelete());
	$res = $user->objDelete();
	notice($res[1], $module, $res[0]);
}

?>
</div>