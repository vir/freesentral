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
/*
function equipments()
{
	$equipments = Model::selection("equipment", NULL, "equipment");
	$formats = array("equipment","description");

	tableOfObjects($equipments, $formats, "equipment", array("&method=edit_equipment"=>'<img src="images/edit.gif" alt="edit" title="Edit"/>', "&method=delete_equipment"=>'<img src="images/delete.gif" alt="delete" title="Delete"/>'), array("&method=add_equipment"=>"Add equipment"));
}

function edit_equipment($error = NULL)
{
	if($error)
		errornote($error);

	$equipment = new Equipment;
	$equipment->equipment_id = getparam("equipment_id");
	$equipment->select();

	$fields = array(
					"equipment"=>array("compulsory"=>true),
					"description"=>array("display"=>"textarea")
				);

	if($equipment->equipment_id)
		$title = "Edit equipment";
	else
		$title = "Add equipment";

	start_form();
	addHidden("database", array("equipment_id"=>$equipment->equipment_id));
	editObject($equipment,$fields,$title,"Save",true);
	end_form();
}

function edit_equipment_database()
{
	global $path;
	$path .= "&method=equipments";

	$equipment = new Equipment;
	$equipment->equipment_id = getparam("equipment_id");
	$equipment->equipment = getparam("equipment");

	if(!$equipment->equipment)
	{
		edit_equipment("Field 'Equipment' is required");
		return;
	}
	if($equipment->objectExists())
	{
		edit_equipment("This equipment is already in the database");
		return;
	}
	$equipment->description = getparam("description");
	if($equipment->equipment_id)
		notify($equipment->update(),$path);
	else
		notify($equipment->insert(),$path);
}

function delete_equipment()
{
	ack_delete("equipment", getparam("equipment"), NULL, "equipment_id", getparam("equipment_id"));
}

function delete_equipment_database()
{
	global $path;
	$path .= "&method=equipments";

	$equipment = new Equipment;
	$equipment->equipment_id = getparam("equipment_id");
	if(!$equipment->equipment_id) 
	{
		errormess("Don't have equipment_id specified, can't delete equipment.");
		return;
	}
	notify($equipment->objDelete(),$path);
}
*/
function general()
{
	settings();
}

function settings()
{
	global $method;
	$method = "settings";

	$settings = Model::selection("setting",array("param"=>array("!=version", "!=wizard")),"param");

	$formats = array("setting"=>"param", "value", "description");
	tableOfObjects($settings,$formats,"setting", array("&method=edit_setting"=>'<img src="images/edit.gif" title="Edit" alt="Edit"/>'));
}

function edit_setting($error = NULL)
{
	if($error)
		errornote($error);

	$setting = new Setting;
	$setting->setting_id = getparam("setting_id");
	$setting->select();

	$fields = array(
					"setting"=>array("value"=>$setting->param, "display"=>"fixed"),
					"value"=>array(),
					"description"=>array("display"=>"textarea")
				);

	start_form();
	addHidden("database", array("setting_id"=>$setting->setting_id));
	editObject($setting, $fields, "Edit setting", "Save");
	end_form();
}

function edit_setting_database()
{
	$setting = new Setting;
	$setting->setting_id = getparam("setting_id");
	$setting->select();

	$setting->value = getparam("value");
	if(!$setting->value)
	{
		edit_setting("Field 'Value' can not be empty");
		return;
	}
	$setting->description = getparam("description");
	$res = $setting->update();
	notice($res[1],NULL,$res[0]);
}
/*
function settings()
{
	global $module;

	$prompts = Model::selection("mainprompt",NULL,"mainprompt");
	$prompts = Model::objectsToArray($prompts,array("mainprompt"=>"", "in_use"=>"", "description"=>""));

	table($prompts, "main prompt", "main.php?module=$module", array("&method=edit_prompt"=>"Edit", "&method=delete_prompt"=>"Delete"), "upload_prompt", "Upload prompt");

	$settings = Model::selection("setting",NULL,"param");
	$settings = Model::objectsToArray($settings, array("param"=>"setting", "value"=>""));

	table($settings, "setting" ,"main.php?module=$module", array("&method=edit_setting"=>"Edit"));
}

function edit_setting()
{
	$setting = new Setting;
	$setting->setting_id = getparam("setting_id");
	$setting->select();

	$array = array("ff_setting"=>$setting->param, "value"=>$setting->value);
	start_form();
	addHidden("database",array("setting_id"=>$setting->setting_id));
	edit_form($array, "Edit setting", "Save");
	end_form();
}

function edit_setting_database()
{
	$setting = new Setting;
	$setting->setting_id = getparam("setting_id");
	$setting->select();

	$setting->value = getparam("value");
	notify($setting->update());
}

function edit_prompt()
{
	$mainprompt = new MainPrompt;
	$mainprompt->mainprompt_id = getparam("mainprompt_id");
	$mainprompt->select();

	$array = array("ff_mainprompt"=>$mainprompt->mainprompt, "description"=>$mainprompt->description, "in_use"=>($mainprompt->in_use == 't') ? 't' : 'f');

	start_form();
	addHidden("database", array("mainprompt_id"=>$mainprompt->mainprompt_id));
	edit_form($array, "Edit main prompt", "Save");
	end_form();
}

function edit_prompt_database()
{
	$mainprompt = new MainPrompt;
	$mainprompt->mainprompt_id = getparam("mainprompt_id");
	$mainprompt->select();

	$mainprompt->description = getparam("description");

	if(getparam("in_use") == "on" && $mainprompt->in_use) {
		$prompt = Model::selection("mainprompt", array("in_use"=>true));
		for($i=0; $i<count($prompt); $i++) {
			$prompt[$i]->in_use = 'f';
			$prompt[$i]->update();
		}
	}
	$mainprompt->in_use = (getparam("in_use") == "on") ? 't' : 'f';

	notify($mainprompt->update());
}

function delete_prompt()
{
	if(getparam("mainprompt") == "default_menu.u") {
		errormess("This is the default prompt. You are not allowed to delete it.");
		return;
	}
	ack_delete("prompt",getparam("mainprompt"), NULL, "mainprompt_id", getparam("mainprompt_id"));
}

function delete_prompt_database()
{
	$path = Model::selection("setting", array("param"=>"path"));
	if(!count($path)) {
		errormess("Please set the defualt path.");
		return;
	}
	$path = $path[0]->value .'/';

	$mainprompt = new MainPrompt;
	$mainprompt->mainprompt_id = getparam("mainprompt_id");
	$mainprompt->select();

	unlink($path.$mainprompt->mainprompt);

	message("Selected main prompt was deleted");
}

function upload_prompt()
{
	$array = array("fileselect_upload_main_menu_prompt"=>"", "description"=>"", "in_use"=>'f');

	//start the form, third param true show that this form will be used for uploading
	start_form(NULL,NULL,true);
	addHidden("database",array("MAX_FILE_SIZE"=>"500000000", "destination_id"=>getparam("destination_id"), "letter"=>getparam("letter")));
	edit_form($array,"Upload Main Menu prompt","Submit",8);
	end_form();	
}

function upload_prompt_database()
{
	global $target_path, $path;

	$file = $filename =  basename($_FILES['upload_main_menu_prompt']['name']);

	$brfile = $filename;
	while(true) {
		$mps = Model::selection("mainprompt",array("mainprompt"=>$brfile));
		if(!count($mps))
			break;
		$brfile = explode(".", $filename);
		$brfile[0] .= '1';
		$brfile = implode('.',$brfile);
	}
	$filename = $brfile;

	if(!is_dir($target_path))
		mkdir($target_path);
	$file = "$target_path/$filename";

	if (!move_uploaded_file($_FILES["upload_main_menu_prompt"]['tmp_name'],$file)) {
		errormess("Could not upload file",$path);
		return;
	}

	$mainprompt = new MainPrompt;
	$mainprompt->mainprompt = $filename;
	$mainprompt->description = getparam("description");
	$mainprompt->in_use = (getparam("in_use") == "on") ? 't' : 'f';

	if($mainprompt->in_use == 't') {
		$prompt = Model::selection("mainprompt", array("in_use"=>true));
		if(count($prompt)) {
			for($i=0; $i<count($prompt); $i++) {
				$prompt[$i]->in_use = 'f';
				$prompt[$i]->update();
			}
		}
	}

	$mainprompt->insert();

	message("File was uploaded");
}
*/

function network()
{	
	$fields = array("DEVICE"=>"network_interface", "BOOTPROTO"=>"protocol", "IPADDR"=>"ip_address", "NETMASK"=>"netmask", "GATEWAY"=>"gateway");

	$ninterfaces = array();
	$dir = "/etc/sysconfig/network-scripts";
	if ($handle = opendir("$dir"))
	{
		while (false !== ($file = readdir($handle)))
		{
			if (substr($file,0,6) != "ifcfg-")
				continue;
			$ninterfaces[] = str_replace('ifcfg-','',$file);
		}
		closedir($handle);
	}

	$interfaces = array();
	for($i=0; $i<count($ninterfaces); $i++)
	{
		$filename = 'ifcfg-'.$ninterfaces[$i];
		$f = new ConfFile("$dir/$filename");
		$interfaces[$i] = array();
		foreach($fields as $name_in_conf=>$name_to_display)
		{
			if($name_to_display == "")
				$name_to_display = $name_in_conf;

			$interfaces[$i][$name_to_display] = (isset($f->sections[$name_in_conf])) ? $f->sections[$name_in_conf] : '';
		}
	}

	table($interfaces,$fields,"network interface","",array("&method=edit_network_interface"=>'<img src="images/edit.gif" title="Edit" alt="Edit"/>'), array("&method=edit_network_interface"=>"Add network interface"));
}

function edit_network_interface($error = NULL)
{
	if($error)
		errornote($error);

	$dir = "/etc/sysconfig/network-scripts";
	$network_interface = getparam("network_interface");
	$file = "/etc/sysconfig/network-scripts/ifcfg-$network_interface";

	if(is_file($file)) {
		$conf = new ConfFile($file);
		$display = "fixed";
	}else{
		$conf = array();
		$display = "text";
	}
	$fields = array("DEVICE"=>"network_interface", "BOOTPROTO"=>"protocol", "IPADDR"=>"ip_address", "NETMASK"=>"netmask", "GATEWAY"=>"gateway");

	foreach($fields as $name_in_conf=>$name_to_display)
	{
		if($name_to_display == "")
			$name_to_display = $name_in_conf;

		$interface[$name_to_display] = (isset($conf->sections[$name_in_conf])) ? $conf->sections[$name_in_conf] : '';
	}

	$protocols = array("static", "dhcp", "none");
	$protocols["selected"] = $interface["protocol"];
	$interface = array(
						"network_interface" => array("value"=>$interface["network_interface"], "display"=>"$display"),
						"protocol" => array($protocols, "display"=>"select", "javascript"=>'onChange="dependant_fields();"'),
						"ip_address" => array("value"=>$interface["ip_address"],"display"=>($protocols["selected"] == "dhcp") ? "dependant_field_noedit" : "dependant_field_edit"),
						"netmask" => array("value"=>$interface["netmask"],"display"=>($protocols["selected"] == "dhcp") ? "dependant_field_noedit" : "dependant_field_edit"),
						"gateway" => array("value"=>$interface["gateway"],"display"=>($protocols["selected"] == "dhcp") ? "dependant_field_noedit" : "dependant_field_edit")
					);

	start_form();
	addHidden("database",array("network_interface"=>$network_interface));
	editObject(NULL,$interface,"Set network interface", "Save");
	end_form();
}

function edit_network_interface_database()
{
	global $path;
	$path .= "&method=network";

	$dir = "/etc/sysconfig/network-scripts";
	$network_interface = getparam("network_interface");
	$file = "/etc/sysconfig/network-scripts/ifcfg-$network_interface";

	$conf = new ConfFile($file);
	$network_interface = getparam("network_interface");
	if(!isset($conf->structure["DEVICE"]))
		$conf->structure["DEVICE"] = $network_interface;
	
	$protocol = getparam("protocol");

	if(!$protocol || $protocol == "Not selected")
	{
		edit_network_interface("Please select a protocol when defining the interface.");
		return;
	}

	$ip_address = getparam("ip_address");
	$netmask = getparam("netmask");
	$gateway = getparam("gateway");

	$conf->structure["BOOTPROTO"] = $protocol;
	if($protocol == "static") {
		if (!$ip_address) {
			edit_network_interface("Field Ip Address is required when Protocol is static.");
			return;
		}
		if (!$netmask) {
			edit_network_interface("Field Netmask is required when Protocol is static.");
			return;
		}
		if (!$gateway) {
			edit_network_interface("Field Gateway is required when Protocol is static.");
			return;
		}
		$conf->structure["IPADDR"] = $ip_address;
		$conf->structure["NETMASK"] = $netmask;
		$conf->structure["GATEWAY"] = $gateway;
	}else{
		$conf->structure["IPADDR"] = '';
		$conf->structure["NETMASK"] = '';
		$conf->structure["GATEWAY"] = '';
	}

	$conf->save();
	exec("chmod +x ".$conf->filename);
	exec("/etc/init.d/network restart");
//	message("Network interface was configured.",$path);
	notice("Network interface was configured.", "network");
}

function dependant_field_edit($value, $name)
{
	print '<div id="div_'.$name.'" style="display:table-cell;"><input name="'.$name.'" type="'.$name.'" value="'.$value.'"/></div>';
	print '<div id="text_'.$name.'" style="display:none;">&nbsp;'.$value."</div>";
}

function dependant_field_noedit($value, $name)
{
	print '<div id="div_'.$name.'" style="display:none;"><input name="'.$name.'"  type="'.$name.'" value="'.$value.'"/></div>';
	print '<div id="text_'.$name.'" style="display:table-cell;">&nbsp;'.$value."</div>";
}

class ConfFile
{
	public $sections = array();
	public $filename;
	public $structure = array();

	function __construct($file_name)
	{
		$this->filename = $file_name;
		if(!is_file($this->filename))
			return;
		$file=fopen($this->filename,"r");
		$last_section = "";
		while(!feof($file))
		{
			$row = fgets($file);
			$row = trim($row);
			if(!strlen($row))
				continue;
			if($row == "")
				continue;
			// new section started
			// the second paranthesis is kind of weird but i got both cases
			if(substr($row,0,1) == "[" && substr($row,-1,1)) {
				$last_section = substr($row,1,strlen($row)-2);
				$this->sections[$last_section] = array();
				$this->structure[$last_section] = array();
				continue;
			}
			if(substr($row,0,1) == ";") {
				if($last_section == "")
					array_push($this->structure, $row);
				else
					array_push($this->structure[$last_section], $row);
				continue;
			}
			// this is not a section (it's part of a section or file does not have sections)
			$params = explode("=", $row, 2);
			if(count($params)>2 || count($params)<2)
				// skip row (wrong format)
				continue;
			if($last_section == ""){
				$this->sections[$params[0]] = trim($params[1]);
				$this->structure[$params[0]] = trim($params[1]);
			}else{
				$this->sections[$last_section][$params[0]] = trim($params[1]);
				$this->structure[$last_section][$params[0]] = trim($params[1]);
			}
		}
		fclose($file);
	}

	function save()
	{
		$file = fopen($this->filename,"w") or exit("Could not open ".$this->filename." for writting");
		foreach($this->structure as $name=>$value)
		{
			if(!is_array($value)) {
				if(substr($value,0,1) == ";" && is_numeric($name)) {
					//writing a comment
					fwrite($file, $value."\n");
					continue;
				}
				fwrite($file, "$name=".ltrim($value)."\n");
				continue;
			}else
				fwrite($file, "[".$name."]\n");
			$section = $value;
			foreach($section as $param=>$value)
			{
				//writing a comment
				if(substr($value,0,1) == ";" && is_numeric($param)) {
					fwrite($file, $value."\n");
					continue;
				}
				fwrite($file, "$param=".ltrim($value)."\n");
			}
			fwrite($file, "\n");
		}
		fclose($file);
	}
}

function address_book()
{
	global $method, $action;
	$method = "address_book";
	$action = NULL;
	$short_names = Model::selection("short_name", NULL, "short_name");	
	tableOfObjects($short_names, array("short_name", "number", "name"), "short_name", array("&method=edit_short_name"=>'<img src="images/edit.gif" title="Edit" alt="Edit"/>', "&method=delete_short_name"=>'<img src="images/delete.gif" title="Edit" alt="Edit"/>'), array("&method=add_short_name"=>"Add shortcut"));
}

function edit_short_name($error=NULL)
{
	if($error)
		errornote($error);

	$short_name = new Short_Name;
	$short_name->short_name_id = getparam("short_name_id");
	$short_name->select();

	$fields = array(
					"short_name" => array("comment"=>"Name to be dialed", "compulsory"=>true),
					"number" => array("comment"=>"Number where to place the call", "compulsory"=>true),
					"name" => ''
				);

	$title = ($short_name->short_name_id) ? "Edit shortcut" : "Add shortcut ";

	start_form();
	addHidden("database",array("short_name_id"=>$short_name->short_name_id));
	editObject($short_name, $fields, $title, "Save");
	end_form();
}

function edit_short_name_database()
{
	global $module;

	$short_name = new Short_Name;
	$short_name->short_name_id = getparam("short_name_id");
	$params = form_params(array("short_name", "number", "name"));;
	$res = ($short_name->short_name_id) ? $short_name->edit($params) : $short_name->add($params);
	notice($res[1], "address_book", $res[0]);
}

function delete_short_name()
{
	ack_delete("short_name", getparam("short_name"), NULL, "short_name_id", getparam("short_name_id"));
}

function delete_short_name_database()
{
	global $module;

	$short_name = new Short_Name;
	$short_name->short_name_id = getparam("short_name_id");
	$res = $short_name->objDelete();
	notice($res[1], "address_book", $res[0]);
}

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
	$params = form_params(array("email", "firstname", "lastname", "description"));
	if(!getparam("user_id"))
		$params["username"] = getparam("username");
	if (($password = getparam("password")))
		$params["password"] = $password;

	$res = ($user->user_id) ? $user->edit($params) : $user->add($params);
	notice($res[1], "admins", $res[0]);
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
	$res = $user->objDelete();
	notice($res[1], "admins", $res[0]);
}

?>
</div>