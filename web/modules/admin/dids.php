<?
/**
 * dids.php
 * This file is part of the FreeSentral Project http://freesentral.com
 *
 * FreeSentral - is a Web Graphical User Interface for easy configuration of the Yate PBX software
 * Copyright (C) 2008-2009 Null Team
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301, USA.
 */
?>
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

$explanation = array("default"=>"DIDs - A call can go directly to a phone from inside the FreeSentral, by definining the destination as a Did. The destination can be an extension, a group of extensions, a voicemail, etc. ", "conferences"=>"Conferences - use the number associated to each room to connect to the active conference rooms.");
$explanation["edit_conference"] = $explanation["conferences"];

explanations("images/dids.png", "", $explanation);

print '<div class="content">';
$call();
print '</div>';

function manage()
{
	dids();
}

function edit_conference()
{
	$did = new Did;
	$did->did_id = getparam("did_id");
	$did->select();

	$fields = array(
					"conference" => array("value"=>get_conf_from_did($did->did, $did->destination), "compulsory"=>true, "comment"=>"Name for conference chamber"),
					"number" => array("value"=>$did->number, "compulsory"=>true, "comment"=>"Number people need to call to enter the conference. This number must be unique in the system: must not match a did, extension or group.")
				);
	$title = ($did->did_id) ? "Edit conference" : "Add conference";

	start_form();
	addHidden("database", array("did_id"=>$did->did_id));
	editObject(null, $fields, $title, "Save");
	end_form();
}

function edit_conference_database()
{
	$did = new Did;
	$did->did_id = getparam("did_id");
	$params = array("did"=>"conference " . getparam("conference"), "number"=>getparam("number"), "destination"=>"conf/".getparam("conference"));
	$res = ($did->did_id) ? $did->edit($params) : $did->add($params);
	notice($res[1], "conferences", $res[0]);
}

function delete_conference()
{
	ack_delete("conference", get_conf_from_did(getparam("did"), getparam("destination")), null, "did_id", getparam("did_id"));
}

function delete_conference_database()
{
	$did = new Did;
	$did->did_id = getparam("did_id");
	$res = $did->objDelete();

	if($res[0])
		notice("Conference was deleted.", "conferences", true);
	else
		notice("Could not delete conference.", "conferences", false);
}

function conferences()
{
	$conferences = Model::selection("did", array("destination"=>"LIKEconf/"), "did");
	$fields = array("function_get_conf_from_did:conference"=>"did", "number", "function_conference_participants:participants"=>"number");
	tableOfObjects($conferences, $fields, "conference", array("&method=edit_conference"=>'<img src="images/edit.gif" alt="Edit" title="Edit" />', "&method=delete_conference"=>'<img src="images/delete.gif" alt="Delete" title="Delete" />'), array("&method=add_conference"=>"Add conference"));
}

function dids()
{
	global $method, $action;
	$method = "dids";
	$action = NULL;

	$did = new Did;
	$did->extend(array("extension"=>"extensions", "group"=>"groups"));
	$dids = $did->extendedSelect(array("destination"=>"NOT LIKEconf/"),"number");

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
					"default_destination"=>array($options, "display"=>"select", "comment"=>"Choose between an extension or a group. Use this field when 'Destination' is a script like Auto Attendant. If caller doesn't insert anything he will be sent to this default destination."),
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
	$did->did_id  = getparam("did_id");
	$params = form_params(array("did", "number", "destination", "default_destination", "extension", "group", "description"));
	$res = ($did->did_id) ? $did->edit($params) : $did->add($params);

	if($res[0])
		notice($res[1], $module, $res[0]);
	else
		edit_did($res[1]);
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
	$res = $did->objDelete();
	notice($res[1], $module, $res[0]);
}
?>