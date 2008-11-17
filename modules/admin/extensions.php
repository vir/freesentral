<script language="javascript">
function groupsClick(lin,col,role)
{
    if (copacClick(lin,col,role)) {
		if (role.substring(0,8) == 'group')
			window.location = "main.php?module=extensions&method=group_members&group="+copacValoare(lin,col);
		if (role == 'extension')
			window.location = "main.php?module=extensions&method=edit_extension&extension_name="+copacValoare(lin,col)+"&group="+copacParinte(lin,col);
    }
}
</script>
<?
global $module, $method, $path, $action, $page, $limit, $fields_for_extensions, $operations_for_extensions, $upload_path;

$fields_for_extensions = array("function_detect_busy:currently"=>"inuse_count,location", "extension", "firstname", "lastname");
$operations_for_extensions = array("&method=edit_extension"=>'<img src="images/edit.gif" title="Edit" alt="edit"/>', "&method=delete_extension"=>'<img src="images/delete.gif" title="Delete" alt="delete">', "&method=join_group"=>'<img src="images/join_group.gif" title="Join Group" alt="join group"/>', "&method=impersonate"=>'<img src="images/impersonate.gif" alt="impersonate" title="Impersonate"/>');

if(!$method || $method == "manage")
	$method = $module;

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

draw_tree();
if (getparam("group"))
	print "<script language=\"javascript\">copacComuta('".getparam("group")."');</script>\n";
print '<div class="content">';
$call();
print '</div>';

function draw_tree()
{
	$group_member = new Group_Member;
	$group_member->extend(array("extension"=>"extensions", "group"=>array("table"=>"groups", "join"=>"RIGHT")));
	$members = $group_member->extendedSelect(NULL, "\"group\",extension");

	//get an array with only the group and extension columns 
	$members = Model::objectsToArray($members, array("group"=>"","extension"=>""),true);
	$members = array_merge(array(array("group"=>"All extensions", "extension"=>"")), $members);
	tree($members,"groupsClick");
}

function extensions()
{
	global $limit, $page, $fields_for_extensions, $module,  $operations_for_extensions, $method, $action;

	$module = "extensions";
	$method = "extensions";
	$action = NULL;

	$total = getparam("total");
	if(!$total)
	{
		$extension = new Extension;
		$total = $extension->fieldSelect("count(*)");
	}
	$extensions = Model::selection("extension",NULL,"extension", $limit, $page);

	items_on_page();
	pages($total);
	tableOfObjects($extensions, $fields_for_extensions, "extension",  $operations_for_extensions, array("&method=add_extension"=>"Add extension"));
}

function group_members()
{
	global $fields_for_extensions, $operations_for_extensions, $module;

	$group = getparam("group");
	if(!$group)
	{
		errormess("Don't have name of group to make selection.");
		return;
	}
	if($group == "All extensions")
	{
		extensions();
		return;
	}
	$group = Model::selection("group", array("group"=>$group));
	if(!count($group))
	{
		errormess("Invalid group name");
		return;
	}

	$operations_for_extensions["&method=remove_from_group"] = '<img src="images/remove_from_group.gif" title="Remove from group" alt="remove from group"/>';
	$group = $group[0];
	$member = new Group_Member;
	$member->extend(array("extension"=>"extensions", "firstname"=>"extensions", "lastname"=>"extensions", "location"=>"extensions", "inuse_count"=>"extensions"));
	$members = $member->extendedSelect(array("group_id"=>$group->group_id), "extension");
	
	tableOfObjects($members, $fields_for_extensions, "extension",  $operations_for_extensions, array("&method=add_extension"=>"Add extension"), "main.php?module=$module&group_id=".$group->group_id);
}

function edit_extension($error = NULL)
{
	global $module;

	if($error)
		errornote($error);

/*	$equipments = Model::selection("equipment", NULL, "equipment");
	$equipments = Model::objectsToArray($equipments, array("equipment_id"=>"", "equipment"=>""));*/

	$extension = new Extension;
	$extension->extension_id = getparam("extension_id");
	if(getparam("extension_name")) {
		$extension->extension = getparam("extension_name");
		$extension->select('extension');
	}else
		$extension->select();

	if(!$extension->extension_id)
	{
		$extension->extension = getparam("extension");
		$extension->password = getparam("password");
		$extension->firstname = getparam("firstname");
		$extension->lastname = getparam("lastname");
		$extension->address = getparam("address");
		$extension->max_minutes = getparam("max_minutes");
	/*	$extension->mac_address = getparam("mac_address");
		$extension->equipment_id = getparam("equipment");*/
	}
	$equipments["selected"] = $equipments;
	$max_minutes = interval_to_minutes($extension->used_minutes);
	$max_minutes = (!$max_minutes) ? NULL : $max_minutes;
	$fields = array(
					"extension"=>array("compulsory"=>true, "comment"=>"Must have minimum 3 digits."),
					"password"=>array("compulsory"=>true, "comment"=>"Password must be numeric and have at least 6 digits. You can either insert it or use the 'Generate&nbsp;Password' option."),
					"generate_password"=>array("display"=>"checkbox", "comment"=>"Check to generate random password"),
					"firstname"=>"",
					"lastname"=>"",
					"address"=>"",
					"max_minutes"=>array("value"=>$max_minutes,"comment"=>"Leave this field empty for unlimited number of minutes"),
					"used_minutes"=>array("value"=>interval_to_minutes($extension->used_minutes), "display"=>"fixed", "comment"=>'<a href="main.php?module='.$module.'&extension_id='.$extension->extension_id.'">Reset&nbsp;Used&nbsp;Minutes</a>'),
			/*		"mac_address"=>array("comment"=>"Insert mac address here if you wish to provision a certain type of equipment."),
					"equipment"=>array($equipments,"display"=>"select")*/
				);
	if($extension->extension_id)
		$title = "Edit extension";
	else{
		$title = "Add extension";
		unset($fields["used_minutes"]);
	}

	start_form();
	addHidden("database", array("extension_id"=>$extension->extension_id));
	editObject($extension,$fields,$title, "Save",true);
	end_form();
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
	notice($res[1],$module,$res[0]);
}

function groups()
{
	global $method, $action;
	$method = "groups";
	$action = NULL;

	$groups = Model::selection("group", NULL, '"group"');
	tableOfObjects($groups, array("group", "extension"), "group",array("&method=edit_group"=>'<img src="images/edit.gif" title="Edit" alt="edit"/>', "&method=delete_group"=>'<img src="images/delete.gif" title="Delete" alt="delete"/>', "&method=group_members"=>'<img src="images/group_members.gif" title="Members" alt="members"/>'), array("&method=add_group"=>"Add group"));
}

function edit_group($error = NULL)
{
	if($error)
		errornote($error);

	$playlists = Model::selection("playlist",NULL,"playlist");
	$playlists = Model::objectsToArray($playlists, array("playlist_id"=>"", "playlist"=>""), true);

	$group = new Group;
	$group->group_id = getparam("group_id");
	$group->select();
	$playlists["selected"] = $group->playlist_id;
	$fields = array(
					"group" => array("compulsory"=>true),
					"extension" => array("compulsory"=>true, "comment"=>"Ex: 01 for Sales(Must be 2 digits long)"),
					"playlist" => array($playlists, "display"=>"select", "comment"=>"Music on hold playlist for this group."),
					"description" => array("display"=>"textarea")
				);
	if($group->group_id)
		$title = "Edit group";
	else
		$title = "Add group";
	start_form();
	addHidden("database", array("group_id"=>$group->group_id));
	editObject($group, $fields, $title, "Save", true);
	end_form();
}

function edit_group_database()
{
	global $path;
	$path .= "&method=groups";
	$group = new Group;
	$group->group_id = getparam("group_id");
	$group->group = getparam("group");
	if(!$group)
	{
		edit_group("Field 'Group' is required");
		return;
	}
	if($group->objectExists())
	{
		edit_group("There is already a group with this name");
		return;
	}
	$group->select();
	$group->group = getparam("group");
	$group->extension = getparam("extension");
	if(!$group->extension)
	{
		edit_group("Field 'Extension' is required");
		return;
	}
	if(strlen($group->extension) != 2)
	{
		edit_group("Field 'Extension' must be at least 2 digits long");
		return;
	}
	$group2 = new Group;
	$group2->group_id = $group->group_id;
	$group2->extension = $group->extension;
	if($group2->objectExists())
	{
		edit_group("A group with this extension already exists.");
		return;
	}
	$group->playlist_id = getparam("playlist");
	if(!$group->playlist_id || $group->playlist_id == "Not selected")
	{
		edit_group("Please select a file for music on hold. If you don't have any file uploaded go to Settings >> Music on Hold in order to upload the songs and create playlists.");
		return;
	}
//	if($group->group_id)
//		notify($group->update(),$path);
//	else
//		notify($group->insert(),$path);
	$res = ($group->group_id) ? $group->update() : $group->insert();
	notice($res[1],"groups",$res[0]);
}

function join_group()
{
	$extension = new Extension;
	$extension->extension_id = getparam("extension_id");
	$extension->select();
	if(!$extension->extension)
	{
		errormess("Missing extension id or invalid one.");
		return;
	}
	$groups = Model::selection("group",NULL,'"group"',NULL,NULL,NULL,array("column"=>"group_id", "other_table"=>"group_members", "relation"=>"NOT IN", "conditions"=>array("extension_id"=>$extension->extension_id)));
	$groups = Model::objectsToArray($groups, array("group_id"=>"", "group"=>""),true);
	if(!count($groups))
	{
		errormess("There are no groups that this extension can join.");
		return;
	}

	start_form();
	addHidden("database", array("extension_id"=>$extension->extension_id));
	editObject(NULL, array("group"=>array($groups,"display"=>"select")), "Select group to join for extension ".$extension->extension, "Save");
	end_form();
}

function join_group_database()
{
	global $path;
	$path .= "&method=groups";
	$extension_id = getparam("extension_id");
	if(!$extension_id)
	{
		//errormess("Missing extension id");
		notice("Missing extension id", "groups", false);
		return;
	}
	$group_id = getparam("group");
	$group = new Group;
	$nr = $group->fieldSelect("count(*)", array("group_id"=>$group_id));
	if(!$nr)
	{
		//errormess("Invalid group id");
		notice("Invalid group id", "groups", false);
		return;
	}
	$group_member = new Group_Member;
	$group_member->extension_id = $extension_id;
	$group_member->group_id = $group_id;
	$res = $group_member->insert();

	$extension = new Extension;
	$extension->extension_id = $extension_id;
	$extension->select();

	if(!$res[0])
		//errormess("Could not join selected group", $path);
		notice("Could not join selected group", "groups", false);
	else
		//message("Extension ".$extension->extension." joined selected group", $path);
		notice("Extension ".$extension->extension." joined selected group", "groups");
}

//adding a range and setting a random password
function edit_range($error=NULL)
{
	if($error) 
		errornote($error);

	$fields = array(
					"from"=>array("value"=>getparam("from"), "compulsory"=>true, "comment"=>"Numeric value. Minimum 3 digits"),
					"to"=>array("value"=>getparam("to"), "compulsory"=>true, "comment"=>"Numeric value, higher than that inserted in the 'From' field. Must be the same number of digits as the 'From' field."),
					"generate_passwords"=>array("value"=>"t","display"=>"checkbox", "comment"=>"Check in order to generate random 6 digits passwords for the newly added extensions")
				);

	start_form();
	addHidden("database");
	editObject(NULL, $fields, "Add range", "Save");
	end_form();
}

function edit_range_database()
{
	global $module;

	$from = getparam("from");
	$to = getparam("to");
	if(strlen($from) < 3)
	{
		edit_range("Field 'From' must have minimum 3 digits");
		return;
	}
	if(Numerify($from) == "NULL")
	{
		edit_range("Field 'From' must be numeric");
		return;
	}
	if(strlen($to) != strlen($from))
	{
		edit_range("Field 'To' must have the same number of digits as the 'From' field.");
		return;
	}
	if(Numerify($to) == "NULL")
	{
		edit_range("Field 'To' must be numeric");
		return;
	}
	if($from > $to)
	{
		edit_range("Field 'From' must be smaller than 'To'");
		return;
	}

	$generate_password = getparam("generate_passwords");
	for($i=Numerify($from); $i<Numerify($to); $i++)
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
	notice("Finished inserting free extensions is range $from-$to", $module);
}

function delete_group()
{
	ack_delete("group", getparam("group"), NULL, "group_id", getparam("group_id"));
}

function delete_group_database()
{
	global $path;
	$path .= "&method=groups";

	$group = new Group;
	$group->group_id = getparam("group_id");
	//notify($group->objDelete(),$path);
	$res = $group->objDelete();
	notice($res[1], "groups", $res[0]);
}

function remove_from_group()
{
	global $module;

	$group_id = getparam("group_id");
	$extension_id = getparam("extension_id");
	if(!$group_id)
	{
		//errormess("Don't have group id");
		notice("Don't have group id", $module, false);
		return;
	}
	if(!$extension_id)
	{
		//errormess("Don't have extension id");
		notice("Don't have extension id", $module, false);
		return;
	}

	$member = Model::selection("group_member", array("group_id"=>$group_id, "extension_id"=>$extension_id));
	if(!count($member))
	{
		//errormess("This extension is not a member of the group");
		notice("This extension is not a member of the group", $module, false);
		return;
	}
	$res = $member[0]->objDelete();
	if($res[0])
		//message("Succesfully removed extension from group");
		notice("Succesfully removed extension from group", $module);
	else
		//errormess("Could not remore extension from group");
		notice("Could not remore extension from group", $module, false);
}

function delete_extension()
{
	ack_delete("extension",getparam("extension"),NULL,"extension_id",getparam("extension_id"));
}

function delete_extension_database()
{
	global $module;

	$extension = new Extension;
	$extension->extension_id = getparam("extension_id");
	//notify($extension->objDelete());
	$res = $extension->objDelete();
	notice($res[1],$module,$res[0]);
}

function search($error = NULL)
{
	if($error)
		errornote($error);

	$fields = array(
					"digits"=>array("value"=>getparam("digits"),"comment"=>"Insert digit or combination of digits to search after.", "compulsory"=>true),
					"start"=>array("display"=>"checkbox", "comment"=>"Check if extension should start with this digits"),
					"end"=>array("display"=>"checkbox", "comment"=>"Check if extension should end with this digits"),
					"all"=>array("display"=>"checkbox", "comment"=>"Check to get all extension that contain the inserted digits")
				);

	start_form();
	addHidden("database");
	editObject(NULL,$fields,"Search for extensions");
	end_form();
}

function search_database()
{
	global $fields_for_extensions, $operations_for_extensions;
	$digits = getparam("digits");
	$start = getparam("start");
	$end = getparam("end");
	$all = getparam("all");
	if(!$digits)
	{
		search("Please insert the digits to seach after");
		return;
	}
	if(Numerify($digits) == "NULL")
	{
		search("Field digits must be numeric");
		return;
	}
	if($all == "on")
		$conditions = array("extension"=>"LIKE%$digits%");
	elseif($start == "on")
		$conditions = array("extension"=>"LIKE$digits%");
	elseif($end == "on")
		$conditions = array("extension"=>"LIKE%$digits");
	else{
		search("Please check one of the options for matching");
		return;
	}

	$extensions = Model::selection("extension",$conditions,"extension");
	tableOfObjects($extensions,$fields_for_extensions,"extension", $operations_for_extensions,array("&method=extensions"=>"Return"));
}

function import($error = NULL)
{
	if($error)
		errornote($error);

	$fields = array(
					"insert_file_location" => array("display"=>"file", "comment"=>"File type must be .csv"),
					"sample_.xls_file" => array("display"=>"fixed", "value"=>'<a class="llink" href="extensions.xls">Download</a>', "comment"=>"Complete the sample file with the information you wish to import and then export it as a .csv file. If you want to insert more than one group for an extension you should add ; between the name of the groups. Unexisting groups will be ignored.")
				);

	start_form(NULL,"post",true);
	addHidden("database");
	editObject(NULL,$fields,"Import extensions from .csv file", "Upload");
	end_form();
}

function import_database()
{
	global $upload_path, $module;

	$filename = basename($_FILES["insert_file_location"]["name"]);
	if(strtolower(substr($filename,-4)) != ".csv")
	{
		import("File format must be .csv");
		return;
	}	

	if(!is_dir($upload_path))
		mkdir($upload_path);

	$file = "$upload_path/$filename";
	if (!move_uploaded_file($_FILES["insert_file_location"]['tmp_name'],$file)) {
		//errormess("Could not upload file.");
		notice("Could not upload file.", $module, false);
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
				errormess('Unknown group '.$groups[$i].'<br/>', 'no');
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
		//errormess($error);
		notice($error, $module, false);
	//message("Finished importing extensions");
	notice("Finished importing extensions", $module);
}

function export()
{
	$file = "exported_extensions.xls";
	$fh = fopen($file, 'w') or die("Can't open file for writting.");
	$extensions = Model::selection("extension",NULL,"extension");

	$names = "Extension,Firstname,Lastname,Address\n";
	fwrite($fh, $names);
	for($i=0; $i<count($extensions); $i++)
	{
		$string = $extensions[$i]->extension . "," . $extensions[$i]->firstname . "," . $extensions[$i]->lastname . "," . $extensions[$i]->address . "\n";
		fwrite($fh, $string);
	}
	fclose($fh);
//	message('Extensions were exported. <a class="llink" href="exported_extensions.xls">Download</a>');
	notice('Extensions were exported. <a class="llink" href="exported_extensions.xls">Download</a>');
}