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
	$short_name->short_name = getparam("short_name");
	if(!$short_name->short_name) {
		edit_short_name("Field 'Short name' is required.");
		return;
	}
	if($short_name->objectExists()) {
		edit_short_name("This 'Short name' is already defined.");
		return;
	}
	$short_name->select();
	$short_name->short_name = strtolower(getparam("short_name"));
	$short_name->name = getparam("name");
	$short_name->number = getparam("number");
	if(!$short_name->number) {
		edit_short_name("Field 'Number' is required.");
		return;
	}
	if(!is_numeric($short_name->number)) {
		edit_short_name("Field 'Number' must be numeric.");
		return;
	}

	$match_number = get_matching_number($short_name->short_name);
	if(!$match_number) {
		//errormess("You used invalid characters. You are only allowed to use the letters that you see on your phone's keypad.");
		notice("You used invalid characters. You are only allowed to use the letters that you see on your phone's keypad.", $module, false);
		return;
	}
	$options = get_possible_options($match_number);
	$query = "SELECT count(*) FROM short_names WHERE short_name IN ($options)";
	if($short_name->short_name_id)
		$query .= " AND short_name_id!=".$short_name->short_name_id;
	$res = Database::query($query);
	$res = query_to_array($res);

	if($res[0]["count"]) {
		//errormess("This short name could be confused with another name. Please use another combination.");
		notice("This short name could be confused with another name. Please use another combination.",  $module, false);
		return;
	}

//	if($short_name->short_name_id)
//		notify($short_name->update());
//	else
//		notify($short_name->insert());

	$res = ($short_name->short_name_id) ? $short_name->update() : $short_name->insert();
	notice($res[1], $module, $res[0]);
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
	if(!$short_name->short_name_id) {
		//errormess("Don't have id in order to delete short name");
		notice("Don't have id in order to delete short name",$module,true);
		return;
	}

	//notify($short_name->objDelete());
	$res = $short_name->objDelete();
	notice($res[1], $module, $res[0]);
}

function get_matching_number($name)
{
	$alph = array(
				2 => array("a", "b", "c"),
				3 => array("d", "e", "f"),
				4 => array("g", "h", "i"),
				5 => array("j", "k", "l"),
				6 => array("m", "n", "o"),
				7 => array("p", "q", "r", "s"),
				8 => array("t", "u", "v"),
				9 => array("w", "x", "y", "z")
			);

	$number = '';
	for($l=0; $l<strlen($name); $l++)
	{
		$found = false;
		for($i=2; $i<10; $i++)
		{
			if(in_array($name[$l],$alph[$i])) {
				$found = true;
				break;
			}
		}
		if(!$found)
			return false;
		$number .= $i;
	}
	return $number;
}

function get_possible_options($number)
{
	$posib = array();

	$alph = array(
				2 => array("a", "b", "c"),
				3 => array("d", "e", "f"),
				4 => array("g", "h", "i"),
				5 => array("j", "k", "l"),
				6 => array("m", "n", "o"),
				7 => array("p", "q", "r", "s"),
				8 => array("t", "u", "v"),
				9 => array("w", "x", "y", "z")
			);

	for($i=0; $i<strlen($number); $i++)
	{
		$digit = $number[$i];
		$letters = $alph[$digit];
		if(!count($posib)) {
			$posib = $letters;
			continue;
		}
		$s_posib = $posib;
		for($k=0; $k<count($letters); $k++)
		{
			if($k==0)
				for($j=0; $j<count($posib); $j++)
					$posib[$j] .= $letters[$k];
			else
				for($j=0; $j<count($s_posib); $j++)
					array_push($posib, $s_posib[$j].$letters[$k]);
		}
	}
	$options = implode("', '",$posib);
	return "'$options'";
}
?>
</div>