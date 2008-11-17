<div class="content wide">
<?
global $module, $method, $path, $action, $page;

if(!$method)
	$method = $module;

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

$call();

function dids()
{
	global $method, $action;
	$method = "dids";
	$action = NULL;

	$did = new Did;
	$did->extend(array("extension"=>"extensions", "group"=>"groups"));
	$dids = $did->extendedSelect(NULL,"number");

	$formats = array("did","number","destination","function_get_default_destination:default_destination"=>"extension,group");
	tableOfObjects($dids, $formats, "did", array("&method=edit_did"=>'<img src="images/edit.gif" title="Edit" alt="edit"/>', "&method=delete_did"=>'<img src="images/delete.gif" title="Delete" alt="delete"/>'),array("&method=add_did"=>"Add DID"));
}

function edit_did($error=NULL)
{
	if($error)
		errornote($error);

	$did = new Did;
	$did->did_id = getparam("did_id");
	$did->select();

	if($error) {
		$did->number = getparam("number");
		$did->did = getparam("did");
		$did->destination = getparam("destination");
		$did->default_destination = getparam("default_destination");
	}

	$extensions = Model::selection("extension", NULL, "extension");
	$extensions = Model::objectsToArray($extensions,array("extension_id"=>"", "extension"=>""),true);
	$groups = Model::selection("group", NULL, '"group"');
	$groups = Model::objectsToArray($groups,array("group_id"=>"", "group"=>""),true);

	if($did->default_destination == "extension")
		$extensions["selected"] = $did->extension_id;
	if($did->default_destination == "group")
		$groups["selected"] = $did->group_id;

	$options = array("extension", "group");
	$options["selected"] = $did->default_destination;
	$fields = array(
					"did"=>array("compulsory"=>true, "comment"=>"Name used for identifing this DID"),
					"number"=>array("compulsory"=>true, "comment"=>"Incoming phone number. When receiving a call for this number, send(route) it to the inserted 'Destination'"),
					"destination"=>array("compulsory"=>true, "comment"=>"Ex: external/nodata/voicemail.php(or any other script), external/nodata/auto_attendant.php, 090(extension), 01(group)"),
					"default_destination"=>array($options, "display"=>"select", "comment"=>"Choose between an extension or a group. Use this field when 'Destination' is a script. If caller doesn't insert anything he will be sent to this default destination."),
					"extension"=>array($extensions, "display"=>"select", "comment"=>"Select only when default destination is an extension"),
					"group"=>array($groups,"display"=>"select", "comment"=>"Select only when default destination is a group"),
					"description"=>array("display"=>"textarea")
				);
	start_form();
	addHidden("database",array("did_id"=>$did->did_id));
	if($did->did_id)
		$title = "Edit Direct inward dialing";
	else
		$title = "Add Direct inward dialing";
	editObject($did,$fields,$title,"Save",true);
	end_form();
}

function edit_did_database()
{
	global $module;

	$did = new Did;
	$did->did_id = getparam("did_id");
	$did->number = getparam("number");
	if(!$did->number)
	{
		edit_did("Field 'Number' is required.");
		return;
	}
	if(Numerify($did->number) == "NULL")
	{
		edit_did("Field 'Number' must be numeric.");
		return;
	}
	if($did->objectExists())
	{
		edit_did("A DID for this number already exists.");
		return;
	}
	$did->did = getparam("did");
	$did->number = NULL;
	if($did->objectExists())
	{
		edit_did("A DID with this name already exists");
		return;
	}
	$did->select();
	$did->did = getparam("did");
	$did->number = getparam("number");
	$did->destination = getparam("destination");
	if(!$did->destination)
	{
		edit_did("Field 'Destination' is required");
		return;
	}
	$did->default_destination = getparam("default_destination");
	if(substr($did->destination,0,9) == "external/" && !$did->default_destination)
	{
		edit_did("Field 'Default destination' is compulsory if 'Destination' is a script");
		return;
	}
	if(substr($did->destination,0,9) != "external/" && $did->default_destination!="Not selected")
	{
		print "Field 'Default destination' was ignored. It is taken into account only when destination is a script.";
		$did->default_destination = NULL;
		$did->extension_id = NULL;
		$did->group_id = NULL;
	}
	if($did->default_destination=="Not selected")
		$did->default_destination= NULL;

	if($did->default_destination == "extension")
	{
		if(!getparam("extension") || getparam("extension") == "Not selected")
		{
			edit_did("Please select an extension");
			return;
		}
		$did->extension_id = getparam("extension");
		$did->group_id = NULL;
	}
	if($did->default_destination == "group")
	{
		if(!getparam("group") || getparam("group") == "Not selected")
		{
			edit_did("Please select a group");
			return;
		}
		$did->group_id = getparam("group");
		$did->extension_id = NULL;
	}

//	if($did->did_id)
//		notify($did->update());
//	else
//		notify($did->insert());
	$res = ($did->did_id) ? $did->update() : $did->insert();
	notice($res[1], $module, $res[0]);
}

function delete_did()
{
	ack_delete("did", getparam("did"), NULL, "did_id", getparam("did_id"));
}

function delete_did_database()
{
	global $module;

	$did = new Did;
	$did->did_id  = getparam("did_id");
	//notify($did->objDelete());
	$res = $did->objDelete();
	notice($res[1], $module, $res[0]);
}
?>
</div>