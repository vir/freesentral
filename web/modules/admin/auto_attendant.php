<?php
/**
 * auto_attendant.php
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
<?php
global $module, $method, $path, $action, $page, $target_path, $iframe;

require_once("lib/lib_auto_attendant.php");

if(!getparam("method") || getparam("method") == "auto_attendant")
	$method = "wizard";

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

$explanation = array(
	"default" => "Auto Attendant: Calls within the PBX are answered and directed to their desired destination by the auto attendant system.<br/><br/>The keys you define must match the prompts you uploaded (<font class=\"bold\">step 2</font>). If no key is pressed the call will be sent to the extension or group defined as the default destination when activating the DID for Auto Attendant (<font class=\"bold\">step 4</font>).<br/><br/>The Auto Attendant has two states: online and offline. Each of these states has its own prompt (<font class=\"bold\">step 1</font>).<br/><br/>If your online prompt says: Press 1 for Sales, then you must select type: online, key: 1, and insert group: Sales (you must have defined Sales in the Groups section). Same for offline state. <br/><br/>If you want to send a call directly to an extension or another number, you should insert the number in the 'Number(x)' field from Define Keys section (<font class=\"bold\">step 2</font>).", 
	"keys" => "If your online prompt says: Press 1 for Sales, then you must select type: online, key: 1, and insert group: Sales (you must have defined Sales in the Groups section). Same for offline state. <br/><br/>If you want to send a call directly to an extension or another number, you should insert the number in the 'Number(x)' field from Define Keys section.", 
	"prompts" => "The Auto Attendant has two states: online and offline. Each of these states has its own prompt.", 
	"scheduling" => "Schedulling online Auto Attendant. The time frames when the online auto attendant is not schedulled, the offline one is used."
);

explanations("images/auto-attendant.png", "", $explanation);

print '<div class="content">';
$call();
print '</div>';

function wizard($error = NULL)
{
	if($error)
		errornote($error);

	prompts(true);

	keys(true);

	scheduling(NULL,true);

	activate();
}

?>