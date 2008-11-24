<?
global  $module, $method, $path, $action, $page, $limit, $fields_for_extensions, $operations_for_extensions, $upload_path;

if($_SESSION["level"] != "admin")
	forbidden();

function import_database()
{
	global $upload_path, $module;

	$filename = basename($_FILES["insert_file_location"]["name"]);
	if(strtolower(substr($filename,-4)) != ".csv")
	{
		notice("File format must be .csv", "no", false);
		extensions();
		return;
	}	

	if(!is_dir($upload_path))
		mkdir($upload_path);

	$file = "$upload_path/$filename";
	if (!move_uploaded_file($_FILES["insert_file_location"]['tmp_name'],$file)) {
		//errormess("Could not upload file.");
		notice("Could not upload file.", "no", false);
		extensions();
		return;
	}

	$handle = fopen($file,'r');
	$content = fread($handle,filesize($file));

	$content = eregi_replace('"','',$content); // in case they choose to mark text fields with "
	$content = eregi_replace("'",'',$content);  // in case they choose to mark text fields with '
	$content = explode("\n",$content);

	$names = explode(",",$content[0]);	
	$error = '';

	for($i=1; $i<count($content); $i++)
	{
		$line = $content[$i];
		$line = explode(",",$line);
		if(count($line) != count($names))
			continue;
		$fields = array();

		for($j=0; $j<count($names); $j++)
			$fields[strtolower($names[$j])] = $line[$j];
		if(!isset($fields["extension"]))
			continue;
		if(!($fields["extension"]) || $fields["extension"] == "")
			continue;
		if(Numerify($fields["extension"]) == "NULL")
		{
			$error .= "Field extension must be numeric. Wrong input ".$fields["extension"].".<nr/>";
			continue;
		}
		if(strlen($fields["extension"]) < 3)
		{
			$error .= "Field extension must be minimum 3 digits long. Wrong input ".$fields["extension"].".<nr/>";
			continue; 
		}
		Database::transaction();
		$extension = new Extension;
		$extension->extension = $fields["extension"];
		if($extension->objectExists("extension_id")) {
			print "Skipping ".$extension->extension." because it already exists.<br/>";
			Database::rollback();
			continue;
		}
		$extension->firstname = $fields["firstname"];
		$extension->lastname = $fields["lastname"];
		$extension->address = $fields["address"];
		$extension->password = rand(100000,999999);
		$res = $extension->insert(true);
		if(!$res[0]) {
			$error .= "Could not insert extension : ".$extension->extension;
			Database::rollback();
			continue;
		}
		$groups = $fields["groups"];
		$groups = explode(";", $groups);
		for($j=0; $j<count($groups); $j++) {
			$groups[$j] = trim($groups[$j]);
			if(!$groups[$j] || $groups[$j] == '')
				continue;
			$gr = new Group;
			$gr_index = $gr->fieldSelect("group_id", array('unblock_lower(groups."group")'=>strtolower($groups[$j])));
			if(!$gr_index) {
				errormess('Unknown group '.$groups[$j].'<br/>', 'no');
				continue;
			}
			$group_member = new Group_Member;
			$group_member->group_id = $gr_index;
			$group_member->extension_id = $extension->extension_id;
			$group_member->insert();
			if(!$group_member->group_member_id) {
				errromess("Could not make extension ".$extension->extension." a member to group ".$groups[$j].'<br/>', 'no');
				continue;
			}
		}
		Database::commit();
	}

	if($error != '')
		notice($error, "no", false);
	else
		notice("Finished importing extensions", "no");
	extensions();
}


function edit_extension_database()
{
	global $module;

	$extension = new Extension;
	$extension->extension_id = getparam("extension_id");
	$extension->extension = getparam("extension");
	if(!$extension->extension)
	{
		edit_extension("Field extension is compulsory.");
		return;
	}
	if(Numerify($extension->extension) == "NULL")
	{
		edit_extension("Field extension must be numeric");
		return;
	}
	if(strlen($extension->extension) < 3)
	{
		edit_extension("Field extension must be minimum 3 digits");
		return;
	}
	if($extension->objectExists())
	{
		edit_extension("This extension already exists");
		return;
	}
	$extension->select();
	$extension->extension = getparam("extension");
	$extension->firstname = getparam("firstname");
	$extension->lastname = getparam("lastname");
	$extension->address = getparam("address");
	if(getparam("max_minutes"))
		$extension->max_minutes = minutes_to_interval(getparam("max_minutes"));
/*	$extension->mac_address = getparam("mac_address");
	if($extension->mac_address)
	{
		if(getparam("equipment") != "Not selected")
			$extension->equipment_id = getparam("equipment");
		else{
			edit_extension("Please select equipment you wish to provision.");
			return;
		}
	}*/
	if(getparam("generate_password") == "on")
		$extension->password = rand(100000, 999999);
	elseif(getparam("password"))
		$extension->password = getparam("password");
	if(!$extension->password)
	{
		edit_extension("Field password is compulsory.");
		return;
	}
	if(strlen($extension->password) < 6)
	{
		edit_extension("Password must be at least 6 digits long");
		return;
	}
	if(Numerify($extension->password) == "NULL")
	{
		edit_extension("Field password must be numeric");
		return;
	}
		
//	if($extension->extension_id)
//		notify($extension->update());
//	else
//		notify($extension->insert());
	$res = ($extension->extension_id) ? $extension->update() : $extension->insert();
	notice($res[1],"no",$res[0]);
	extensions();
}


function edit_range_database()
{
	global $module;

	$call = ($_SESSION["wizard"] == "notused") ? "extensions" : "edit_range";

	$from = getparam("from");
	$to = getparam("to");
	if(strlen($from) < 3)
	{
		notice("Field 'From' must have minimum 3 digits", "no", false);
		$call();
		return;
	}
	if(Numerify($from) == "NULL")
	{
		notice("Field 'From' must be numeric", "no", false);
		$call();
		return;
	}
	if(strlen($to) != strlen($from))
	{
		notice("Field 'To' must have the same number of digits as the 'From' field.", "no", false);
		$call();
		return;
	}
	if(Numerify($to) == "NULL")
	{
		notice("Field 'To' must be numeric", "no", false);
		$call();
		return;
	}
	if($from > $to)
	{
		notice("Field 'From' must be smaller than 'To'", "no", false);
		$call();
		return;
	}

	$generate_password = getparam("generate_passwords");
	for($i=Numerify($from); $i<=Numerify($to); $i++)
	{
		$extension = new Extension;
		$extension->extension = Numerify($i);
		while(strlen($extension->extension) < strlen($from))
			$extension->extension = '0'.$extension->extension;
		if($extension->objectExists())
		{
			print 'Skipping extention '.$extension->extension.' because it was previously added.<br/>';
			continue;
		}
		if($generate_password == "on")
			$extension->password = rand(100000,999999);
		$extension->insert(true);
	}
	//message("Finished inserting free extensions is range $from-$to");
	notice("Finished inserting free extensions is range $from-$to", "no");
	$call();
}

?>