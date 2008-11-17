<div class="content wide">
<?
global $module,$method,$action;

if(!$method)
	$method = $module;

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

$call();

function outbound()
{
	gateways();
}

function gateway_status($enabled,$status,$username)
{
	if(!$username)
	{
		print "&nbsp;";
		return;
	}
	if($enabled != "t")
		print '<img src="images/gray_dot.gif" title="Not enabled" alt="Not enabled"/>';
	elseif($status == "online")
		print '<img src="images/green_dot.gif" title="Online" alt="Online"/>';
	else
		print '<img src="images/red_dot.gif" title="Offline" alt="Offlibe"/>';
}

function gateway_type($username)
{
	if ($username)
		return "Yes";
	return "No";
}

function registration_status($status,$username)
{
	if(!$username)
		return "&nbsp;";
	elseif(!$status)
		return "offline";
	else
		return $status;
}

function gateways()
{
	global $method, $action;
	$method = "gateways";
	$action = NULL;

	$gateways = Model::selection("gateway", NULL, "gateway");
	$formats = array("function_gateway_status:&nbsp;"=>"enabled,status,username", "gateway", "function_gateway_type:requires_registration"=>"username","server", "protocol", "function_registration_status:status"=>"status,username", "enabled");

	tableOfObjects($gateways, $formats, "gateway", array("&method=edit_gateway"=>'<img src="images/edit.gif" title="Edit" alt="edit"/>', "&method=delete_gateway"=>'<img src="images/delete.gif" title="Delete" alt="delete"/>'), array("&method=add_gateway"=>"Add gateway"));
}

function edit_gateway($error=NULL, $protocol = NULL, $gw_type = '')
{
	if($error)
		errornote($error);

	if($gw_type == "reg")
		$gw_type = "Yes";
	elseif($gw_type == "noreg")
		$gw_type = "No";

	$gateway_id = getparam("gateway_id");

	$gateway = new Gateway;
	$gateway->gateway_id = $gateway_id;
	$gateway->select();

//fields for gateway with registration
	$sip_fields = array(
						"gateway"=>array("compulsory"=>true), 
						"username"=>array("compulsory"=>true), 
						"password"=>array("comment"=>"Insert only when you wish to change", "display"=>"password"),
						"server"=>array("compulsory"=>true),
						"description"=>array("display"=>"textarea"), 
						"authname"=>array("advanced"=>true), 
						"outbound"=>array("advanced"=>true),
						"domain"=>array("advanced"=>true),
						"localaddress"=>array("advanced"=>true),
						"interval"=>array("advanced"=>true),
 						"number"=>array("advanced"=>true),
						"formats"=>array("advanced"=>true,"display"=>"include_formats", "comment"=>"If none of the formats is checked then server will try to negociate formats automatically"), 
						"rtp_forward"=> array("advanced"=>true,"display"=>"checkbox", "comment"=>"Check this box so that the rtp won't pass  through yate(when possible)"),
						"enabled"=>array("comment"=>"Check this field to mark that you wish to register to this server"),
						"default_dial_plan"=>array("display"=>"checkbox", "comment"=>"Check this box if you wish to automatically add a dial plan for this gateway. The new dial plan is going to match all prefixed and will have the smallesc priority.")
						);

	$h323_fields = $iax_fields = array(
						"gateway"=>array("compulsory"=>true),
						"username"=>array("compulsory"=>true), 
						"password"=>array("comment"=>"Insert only when you wish to change", "display"=>"password"),
						"server"=>array("compulsory"=>true),
						"description"=>array("display"=>"textarea"), 
						"interval"=>array("advanced"=>true), 
						"number"=>array("advanced"=>true),
						"formats"=>array("advanced"=>true,"display"=>"include_formats", "comment"=>"If none of the formats is checked then server will try to negociate formats automatically"), 
						"rtp_forward"=> array("advanced"=>true,"display"=>"checkbox", "comment"=>"Check this box so that the rtp won't pass  through yate(when possible)"),
						"enabled"=>array("comment"=>"Check this field to mark that you wish to register to this server"),
						"default_dial_plan"=>array("display"=>"checkbox", "comment"=>"Check this box if you wish to automatically add a dial plan for this gateway. The new dial plan is going to match all prefixed and will have the smallesc priority.")
					);
	unset($iax_fields["rtp_forward"]);

// fields for gateways without registration
	$sip = $h323 = array(
							"gateway"=>array("compulsory"=>true),
							'server'=>array("compulsory"=>true), 
							'port'=>array("compulsory"=>true), 
							'formats'=>array("advanced"=>true,"display"=>"include_formats", "comment"=>"If none of the formats is checked then server will try to negociate formats automatically"), 
						//	'check_not_to_specify_formats' => array($check_not_to_specify_formats, "display"=>"checkbox"), 
							'rtp_forward'=> array("advanced"=>true,"display"=>"checkbox", "comment"=>"Check this box so that the rtp won't pass  through yate(when possible)"),
							"default_dial_plan"=>array("display"=>"checkbox", "comment"=>"Check this box if you wish to automatically add a dial plan for this gateway. The new dial plan is going to match all prefixed and will have the smallesc priority.")
						);

	$wp = $zap = array(
						"gateway"=>array("compulsory"=>true),
						'chans_group'=>array("advanced"=>true), 
						'formats'=>array("advanced"=>true,"display"=>"include_formats", "comment"=>"If none of the formats is checked then server will try to negociate formats automatically") ,
						"default_dial_plan"=>array("display"=>"checkbox", "comment"=>"Check this box if you wish to automatically add a dial plan for this gateway. The new dial plan is going to match all prefixed and will have the smallesc priority.")
					//	'check_not_to_specify_formats' => array($check_not_to_specify_formats, "display"=>"checkbox"), 
					);

	$iax = array(
					"gateway"=>array("compulsory"=>true),
					'server'=>array("compulsory"=>true), 
					'port'=>array("compulsory"=>true), 
					'iaxuser'=>array("advanced"=>true), 
					'iaxcontext'=>array("advanced"=>true), 
					'formats'=>array("advanced"=>true,"display"=>"include_formats", "comment"=>"If none of the formats is checked then server will try to negociate formats automatically") ,
					"default_dial_plan"=>array("display"=>"checkbox", "comment"=>"Check this box if you wish to automatically add a dial plan for this gateway. The new dial plan is going to match all prefixed and will have the smallesc priority.")
				//	'check_not_to_specify_formats' => array($check_not_to_specify_formats, "display"=>"checkbox"), 
				);

	start_form();
	addHidden("database",array("gateway_id"=>$gateway_id));
	if(!$gateway_id) 
	{
		$sip_fields["password"]["compulsory"] = true;
		$h323_fields["password"]["compulsory"] = true;
		$iax_fields["password"]["compulsory"] = true;
		unset($sip_fields["password"]["comment"]);
		unset($h323_fields["password"]["comment"]);
		unset($iax_fields["password"]["comment"]);
		$protocols = array("sip", "h323", "iax");
		$allprotocols = array("sip", "h323", "iax", "wp", "zap");

		if($protocol && $gw_type == "Yes")
		{
			$protocols["selected"] = $protocol;
			$fields = $protocol."_fields";
			foreach(${$fields} as $fieldname=>$fieldformat)
			{
				if($gateway->variable($fieldname))
					$gateway->{$fieldname} = getparam("reg_".$protocol . $fieldname);
			}
			if($gateway->enabled == "on")
				$gateway->enabled = "t";
			$gateway->formats = get_formats("reg_".$protocol."formats");
		}elseif($protocol && $gw_type == "No"){
			$allprotocols["selected"] = $protocol;
			$fields = $protocol;
			foreach(${$fields} as $fieldname=>$fieldformat)
			{
				if($gateway->variable($fieldname))
					$gateway->{$fieldname} = getparam("noreg_".$protocol . $fieldname);
			}
			if($gateway->enabled == "on")
				$gateway->enabled = "t";
			$gateway->formats = get_formats("noreg_".$protocol."formats");
		}

		$gw_types = array("Yes","No");
		$gw_types["selected"] = $gw_type;
		$step1 = array("gateway_with_registration"=>array($gw_types, "display"=>"radios", "javascript"=>'onChange="gateway_type();"', "comment"=>"A gateway with registration is a gateway for which you need an username and a password that will be used to autentify."));

		editObject($gateway,$step1,"Select type of gateway to add","no");

		//select protocol for gateway with registration
		?><div id="div_Yes" style="display:<? if (getparam("gateway_with_registration") != "Yes") print "none;"; else print "block;";?>"><?
		editObject(
					$gateway,
					array(
							"protocol"=>array(
												$protocols,
												"display"=>"select",
												"javascript"=>'onChange="form_for_gateway(\'reg\');"'
											)
						),
					"Select protocol for new gateway",
					"no",null,null,null,"reg"
					);
		?></div><?

		//select protocol for gateway without registration
		?><div id="div_No" style="display:<? if (getparam("gateway_with_registration") != "No") print "none;"; else print "block;";?>"><?
		editObject(
					$gateway,
					array(
							"protocol"=>array($allprotocols,
							"display"=>"select",
							"javascript"=>'onChange="form_for_gateway(\'noreg\');"')
					),
					"Select protocol for new gateway",
					"no",null,null,null,"noreg"
				);
		?></div><?

		// display all the divs with fields for gateway with registration depending on the protocol
		for($i=0; $i<count($protocols); $i++)
		{
			if(!isset($protocols[$i]))
				continue;
			if(!isset(${$protocols[$i]."_fields"}))
				continue;

			?><div id="div_reg_<?print $protocols[$i]?>" style="display:<? if ($protocol == $protocols[$i] && $gw_type == "Yes") print "block;"; else print "none;";?>"><?
			editObject(
						$gateway,
						${$protocols[$i]."_fields"}, 
						"Define ".strtoupper($protocols[$i])." gateway", 
						"Save",true,null,null,"reg_".$protocols[$i]
					);
			?></div><?
		}
		// display all the div with fields for gateway without registration on the protocol
		for($i=0; $i<count($allprotocols); $i++)
		{
			if(!isset($allprotocols[$i]))
				continue;
			if(!isset(${$allprotocols[$i]}))
				continue;
			switch($allprotocols[$i]) {
						case 'sip':
							$gateway->port = '5060';
							break;
						case 'iax':
							$gateway->port = '4569';
							break;
						case 'h323':
							$gateway->port = '1720';
							break;
			}
			?><div id="div_noreg_<?print $allprotocols[$i]?>" style="display:<? if ($protocol == $allprotocols[$i] && $gw_type == "No") print "block;"; else print "none;";?>"><?
			editObject(
						$gateway,
						${$allprotocols[$i]}, 
						"Define ".strtoupper($allprotocols[$i])." gateway",
						"Save",true,null,null,"noreg_".$allprotocols[$i]
					);
			?></div><?
		}
	}else{
		$function = ($gateway->username) ? $gateway->protocol . "_fields" : $gateway->protocol;
		$gw_type = ($gateway->username) ? "reg" : "noreg";

		unset(${$function}["default_dial_plan"]);
		editObject($gateway,${$function}, "Edit ".strtoupper($gateway->protocol). " gateway", "Save", true,null,null,$gw_type."_".$gateway->protocol);
	}
	end_form();
}

function edit_gateway_database()
{
	global $module, $method, $path;
	$path .= "&method=gateways";

	$gateway_id = getparam("gateway_id");
	if(!$gateway_id) {
		$gw_type = getparam("gateway_with_registration");
		$gw_type = ($gw_type == "Yes") ? "reg" : "noreg";
		$protocol = getparam($gw_type."protocol");
	}else{
		$gw = new Gateway;
		$gw->gateway_id = $gateway_id;
		$gw->select();
		$protocol = $gw->protocol;
		$gw_type = ($gw->username && $gw->username != '') ? "reg" : "noreg";
	}
	if(!$protocol)
	{
		//errormess("Can't make this operation. Don't have a protocol setted.",$path);
		notice("Can't make this operation. Don't have a protocol setted.", "gateways", false);
		return;
	}

	$gateway = new Gateway;
	$gateway->gateway_id = $gateway_id;
	$gateway->gateway = getparam($gw_type."_".$protocol."gateway");
	if($gateway->objectExists() && $gateway->gateway)
	{
		edit_gateway("A gateway named ".$gateway->gateway." is already is use.",$protocol,$gw_type);
		return;
	}
	$gateway->select();
	$gateway->gateway = getparam($gw_type."_".$protocol."gateway");

	if($gw_type == "reg")
	{
		if($gateway->gateway_id)
			$compulsory = array("gateway", "username", "server");
		else
			$compulsory = array("gateway", "username", "password", "server");
		for($i=0; $i<count($compulsory); $i++)
		{
			if(!($gateway->{$compulsory[$i]} = getparam($gw_type."_".$protocol.$compulsory[$i])))
			{
				edit_gateway("Field ".$compulsory[$i]." is compulsory.",$protocol,$gw_type);
				return;
			}
		}
		$sip = array('authname','outbound', 'domain', 'localaddress', 'description', 'interval', 'number');
	
		$h323 = $iax = array('description', 'interval', 'number');
	
		for($i=0; $i<count(${$protocol}); $i++) {
			$gateway->{${$protocol}[$i]} = getparam($gw_type."_".$protocol.${$protocol}[$i]);
		}
		if(getparam($gw_type."_".$protocol."password"))
			$gateway->password = getparam($gw_type."_".$protocol."password");
	}else{
		switch($protocol)
		{
			case "iax":
				$gateway->iaxuser = getparam($gw_type."_".$protocol."iaxuser");
				$gateway->iaxcontext = getparam($gw_type."_".$protocol."iaxcontext");
			case "sip":
			case "h323":
				$gateway->server = getparam($gw_type."_".$protocol."server");
				if(!$gateway->server)
				{
					edit_gateway("Field server is compulsory for the selected protocol.",$protocol,$gw_type);
					return;
				}
				$gateway->port = getparam($gw_type."_".$protocol."port");
				if(!$gateway->port)
				{
					edit_gateway("Field Port is compulsory for the selected protocol.",$protocol,$gw_type);
					return;
				}
				if(Numerify($gateway->port) == "NULL")
				{
					edit_gateway("Field Port must be numeric.",$protocol,$gw_type);
				}
			case "wp":
			case "zap":
				$gateway->chans_group = getparam($gw_type."_".$protocol."chans_group");
				break;
		}
	}

	$gateway->protocol = $protocol;
	$gateway->formats = get_formats($gw_type."_".$protocol."formats");
	$gateway->enabled = (getparam($gw_type."_".$protocol."enabled") == "on") ? "t" : "f";
	$gateway->rtp_forward = (getparam($gw_type."_".$protocol."rtp_forward") == "on") ? "t" : "f";
	$gateway->modified = "t";

//	if(!$gateway->gateway_id)
//		notify($gateway->insert(true), $path);
//	else
//		notify($gateway->update(),$path);
	$res = (!$gateway->gateway_id) ? $gateway->insert() : $gateway->update();

	if(!$gateway_id && $gateway->gateway_id) {
		if (getparam($gw_type."_".$protocol."default_dial_plan") == "on") {
			$dial_plan = new Dial_Plan;
			$prio = $dial_plan->fieldSelect("max(priority)") + 10;
			$dial_plan->gateway_id = $gateway->gateway_id;
			$dial_plan->priority = $prio;
			$dial_plan->dial_plan = "default for ".$gateway->gateway;
			$dial_plan->insert(false);
		}
	}
	notice($res[1], "gateways", $res[0]);
}

function delete_gateway()
{
	$gateway = new Gateway;
	$gateway->gateway_id = getparam("gateway_id"); 
	ack_delete("gateway", getparam("gateway"), $gateway->ackDelete(), "gateway_id", $gateway->gateway_id);
}

function delete_gateway_database()
{
	global $path;
	$path .= "&method=gateways";

	$gateway = new Gateway;
	$gateway->gateway_id = getparam("gateway_id");
	//notify($gateway->objDelete(),$path);
	$res = $gateway->objDelete();
	notice($res[1], "gateways", $res[0]);
}

function dial_plan()
{
	global $method, $action;
	$method = "dial_plan";
	$action = NULL;
	//$dial_plans = Model::selection("dial_plan",NULL,"prefix,priority");

	$dial_plan = new Dial_Plan;
	$dial_plan->extend(array("gateway"=>"gateways", "protocol"=>"gateways"));
	$dial_plans = $dial_plan->extendedSelect(array(), "prefix,priority");

	$formats = array("dial_plan","prefix","priority","gateway","protocol");
	tableOfObjects($dial_plans, $formats, "dial plan", array("&method=edit_dial_plan"=>'<img src="images/edit.gif" title="Edit" alt="edit"/>', "&method=delete_dial_plan"=>'<img src="images/delete.gif" title="Delete" alt="delete"/>', "&method=modify_number"=>'<img src="images/modify_numbers.gif" title="Modify number" alt="modify number"/>'), array("&method=add_dial_plan"=> "Add Dial Plan"));
}

function edit_dial_plan($error = NULL)
{
	if($error)
		errornote($error);

	$dial_plan = new Dial_Plan;
	$dial_plan->dial_plan_id = getparam("dial_plan_id");
	$dial_plan->select();

	$gateways = Model::selection("gateway", NULL, "gateway");
	$gateways = Model::objectsToArray($gateways, array("gateway_id"=>"", "gateway"=>""), "all");
	$gateways["selected"] = $dial_plan->gateway_id;

	$check_to_match_everything = (($dial_plan->prefix == "" && $dial_plan->dial_plan_id) || (getparam("check_to_match_everything") == "on")) ? 't' : 'f';

	$fields = array(
					"dial_plan" => array("compulsory" => true),
					"gateway" => array("compulsory" => true, $gateways, "display"=>"select"),
					"priority" => array("comment" => "Numeric. Priority 1 is higher than 10","compulsory"=>true), 
					"prefix" => array("compulsory" => true), 
					"check_to_match_everything" => array("value" => $check_to_match_everything, "display" => "checkbox", "comment" => "If you wish this route to match all prefixes"),
				);

	if($error)
	{
		foreach($fields as $field_name=>$field_format)
		{
			$fields[$field_name]["value"] = getparam($field_name);
		}
		$fields["gateway"][0]["selected"] = getparam("gateway");
	}

	$title = ($dial_plan->dial_plan_id) ? "Edit Dial Plan" : "Add Dial Plan";

	start_form();
	addHidden("database",array("dial_plan_id"=>$dial_plan->dial_plan_id));
	editObject($dial_plan,$fields,$title,"Save",true);
	end_form();
}

function modify_number()
{
	$dial_plan = new Dial_Plan;
	$dial_plan->dial_plan_id = getparam("dial_plan_id");
	$dial_plan->select();

	$fields2 = array(
					"examples" => array("value"=>"Click on the question mark to show/hide the examples.","display"=>"fixed","comment"=>"
Number: 0744224022<br/>
You wish to send the number in international format: +40744334011<br/>
You should set :
Position to start adding : 1<br/>
Digits to add: +4<br/><br/>

Number: 5550744224011<br/>
You wish to send the number in international format: +40744334011<br/>

You can achieve this in 2 ways:<br/>
1)<br/>
Position to start replacing: 1<br/>
Nr of digits to replace: 3<br/>
Digits to replace with: +4<br/>
2)<br/>
Position to start cut: 1<br/>
Nr of digits to cut: 3<br/>
Position to start add: 1<br/>
Digits to add: +4<br/>
<br/>

Number: 0744224022555<br/>
You wish to send the number without the last 555 like this: 0744224022<br/>

Position to start cutting: -3<br/>
Nr of digits to cut: 3
"),
					"position_to_start_cutting" => array("comment" => "The first position in the number is 1. If inserted number is negative, position will be taken from the end of the number. Unless you insert the 'Nr of digits to cut' this field will be ignored. Order for performing operations on the phone number : cut, replace, add."),
					"nr_of_digits_to_cut" => array("comment" => "Number of digits you wish to remove from the number starting from the position inserted above. Unless you insert the 'position to start cutting' this field will be ignored."),
					"position_to_start_replacing" => array("comment" => "The first position in the number is 1. If inserted number is negative, position will be taken from the end of the number.Unless you insert the 'No of digits to replace' and 'Digits to replace with' this field will be ignored"),
					"nr_of_digits_to_replace" => array("comment" => "Unless you insert the Position to start replacing and the Digits to replace with, this field will be ignored"),
					"digits_to_replace_with" => array("comment" => "Digits that will replace the Number of digits to replace starting at 'Position to start replacing'"),
					"position_to_start_adding" => array("comment" => "If inserted number is negative, position will be taken from the end of the number.Unless 'Digits' to add is inserted this field will be ignored"),
					"digits_to_add" => array("comment"=>"Digits that will be added in the 'Position to start adding'"),
				);

	start_form();
	addHidden("database",array("dial_plan_id"=>$dial_plan->dial_plan_id));
	editObject($dial_plan,$fields2,"Options for modifying phone number <br/>when call is sent through this gateway","Save",true);
	end_form();
}

function modify_number_database()
{
	global $path;
	$path .= "&method=dial_plan";

	$dial_plan = new Dial_Plan;
	$dial_plan->dial_plan_id = getparam("dial_plan_id");
	$dial_plan->select();
	$fields = array("position_to_start_cutting"=>"int", "nr_of_digits_to_cut"=>"int", "position_to_start_replacing"=>"int", "nr_of_digits_to_replace"=>"int", "digits_to_replace_with"=>"", "position_to_start_adding"=>"", "digits_to_add"=>"");

	foreach($fields as $field_name=>$field_type)
	{
		$value = getparam($field_name);
		if($field_type == "int" && $value) {
			if(Numerify($value) == "NULL") {
				edit_dial_plan("Field '".ucfirst(str_replace("_"," ",$field_name))."' must be numeric when inserted.");
				return;
			}
		}
		$dial_plan->{$field_name} = $value;
	}
	//notify($dial_plan->update());
	$res = $dial_plan->update();
	notice($res[1], "dial_plan", $res[0]);
}

function edit_dial_plan_database()
{
	global $path;
	$path .= "&method=dial_plan";

	$dial_plan = new Dial_Plan;
	$dial_plan->dial_plan_id = getparam("dial_plan_id");
	$dial_plan->dial_plan = getparam("dial_plan");
	if(!$dial_plan->dial_plan)
	{
		edit_dial_plan("Field 'Dial Plan' is required");
		return;
	}
	if($dial_plan->objectExists())
	{
		edit_dial_plan("A dial plan with this name already exists");
		return;
	}
	$dial_plan->dial_plan = NULL;
	$dial_plan->priority = getparam("priority");
	if(!strlen($dial_plan->priority))
	{
		edit_dial_plan("Field 'Priority' is required");
		return;
	}
	if(Numerify($dial_plan->priority) == "NULL")
	{
		edit_dial_plan("Field 'Priority' must be numeric");
		return;
	}
	if($dial_plan->objectExists())
	{
		edit_dial_plan("This priority was already assigned to another gateway");
		return;
	}
	$dial_plan->dial_plan = getparam("dial_plan");
	if(getparam("check_to_match_everything") != "on" && !getparam("prefix"))
	{
		edit_dial_plan("Please insert the prefix you wish to match or check to match everything");
		return;
	}
	$dial_plan->prefix = (getparam("check_to_match_everything") == "on") ? NULL : getparam("prefix");
	$dial_plan->gateway_id = getparam("gateway");
	if($dial_plan->gateway_id == "Not selected" || !$dial_plan->gateway_id)
	{
		edit_dial_plan("You must select a gateway");
		return;
	}

//	if($dial_plan->dial_plan_id)
//		notify($dial_plan->update(),$path);
//	else
//		notify($dial_plan->insert(true),$path);
	$res = ($dial_plan->dial_plan_id) ? $dial_plan->update() : $dial_plan->insert(true);
	notice($res[1],"dial_plan",$res[0]);
}

function delete_dial_plan()
{
	ack_delete("dial_plan", getparam("dial_plan"), NULL, "dial_plan_id", getparam("dial_plan_id"));
}

function delete_dial_plan_database()
{
	global $path;
	$path .= "&method=dial_plan";

	$dial_plan = new Dial_Plan;
	$dial_plan->dial_plan_id = getparam("dial_plan_id");
	//notify($dial_plan->objDelete(),$path);
	$res = $dial_plan->objDelete();
	notice($res[1], "dial_plan", $res[0]);
}

/*function edit_dial_plan($error = NULL, $sel_protocol = NULL)
{
	if($error)
		errornote($error);

	$dial_plan = new Dial_Plan;
	$dial_plan->dial_plan_id = getparam("dial_plan_id");
	$dial_plan->select();

	if($sel_protocol)
	{
		$vars = $dial_plan->variables();
		foreach($vars as $var_name => $var)
		{
			if($var_name == "dial_plan_id")
				continue;
			if ($var->_type == "bool")
				$dial_plan->{$var_name} = (getparam($sel_protocol . $var_name) == "on") ? "t" : "f";
			else
				$dial_plan->{$var_name} = getparam($sel_protocol . $var_name);
		}
		if($sel_protocol != "for_gateway")
			$dial_plan->protocol = $sel_protocol;
	}

	$check_to_match_everything = (($dial_plan->prefix == "" && $dial_plan->dial_plan_id) || (getparam($sel_protocol . "check_to_match_everything") == "on")) ? 't' : 'f';
	$check_not_to_specify_formats = (!$dial_plan->formats && $dial_plan->dial_plan_id) ? 't':'f';

	$gateway = new Gateway;
	$gateways = $gateway->fieldSelect("gateway_id, gateway",NULL,NULL,"gateway");
	if($sel_protocol == "for_gateway")
		$gateways["selected"] = getparam("gateway");

	$protocols = array("sip","h323","iax","wp","zap");
	$protocols["selected"] = $dial_plan->protocol;
	$for_gateway = array(
						"dial_plan"=>array("compulsory"=>true),
						'priority'=>array("comment"=>"Numeric. Priority 1 is higher than 10","compulsory"=>true), 
						'prefix'=>array("compulsory"=>true), 
						'check_to_match_everything'=> array("value"=>$check_to_match_everything, "display"=>"checkbox", "comment"=>"If you wish this route to match all prefixes"),
						'rtp_forward'=>array("display"=>"checkbox", "comment"=>"Check this box so that the rtp won't pass  through yate(when possible)")
					);

	$sip = $h323 = array(
							"dial_plan"=>array("compulsory"=>true),
							'priority'=>array("comment"=>"Numeric. Priority 1 is higher than 10","compulsory"=>true), 
							'prefix'=>array("compulsory"=>true), 
							'check_to_match_everything'=> array("value"=>$check_to_match_everything, "display"=>"checkbox", "comment"=>"If you wish this route to match all prefixes"),
							'server'=>array("compulsory"=>true), 
							'port'=>array("compulsory"=>true), 
							'formats'=>array("display"=>"include_formats"), 
							'check_not_to_specify_formats' => array($check_not_to_specify_formats, "display"=>"checkbox"), 
							'rtp_forward'=> array("display"=>"checkbox", "comment"=>"Check this box so that the rtp won't pass  through yate(when possible)")
						);

	$wp = $zap = array(
						"dial_plan"=>array("compulsory"=>true),
						'priority'=>array("comment"=>"Numeric. Priority 1 is higher than 10","compulsory"=>true), 
						'prefix'=>array("compulsory"=>true), 
						'check_to_match_everything'=> array("value"=>$check_to_match_everything, "display"=>"checkbox", "comment"=>"If you wish this route to match all prefixes"),
						'chans_group'=>"", 
						'formats'=>array("display"=>"include_formats"), 
						'check_not_to_specify_formats' => array($check_not_to_specify_formats, "display"=>"checkbox"), 
					);

	$iax = array(
					"dial_plan"=>array("compulsory"=>true),
					'priority'=>array("comment"=>"Numeric. Priority 1 is higher than 10","compulsory"=>true), 
					'prefix'=>array("compulsory"=>true), 
					'check_to_match_everything'=> array("value"=>$check_to_match_everything, "display"=>"checkbox", "comment"=>"If you wish this route to match all prefixes"),
					'server'=>array("compulsory"=>true), 
					'port'=>array("compulsory"=>true), 
					'iaxuser'=>"", 
					'iaxcontext'=>"", 
					'formats'=>array("display"=>"include_formats"), 
					'check_not_to_specify_formats' => array($check_not_to_specify_formats, "display"=>"checkbox"), 
				);
	$orig_port = $dial_plan->port;
	start_form();
	addHidden("database", array("dial_plan_id"=>$dial_plan->dial_plan_id, "sprotocol"=>$dial_plan->protocol));
	if(!$dial_plan->dial_plan_id)
	{
		$fields = array(
						"gateway" => array($gateways, "display"=>"select", "javascript"=>'onChange="form_for_dialplan(\'for_gateway\');"', "comment"=>"Select gateway for adding a new DialPlan"),
						"protocol" => array($protocols, "display"=>"select", "javascript"=>'onChange="form_for_dialplan(\'protocol\');"', "comment"=>"If the new DialPlan won't be associated to a gateway, select desired protocol")
					);

		editObject($dial_plan, $fields, "Add Dial Plan", "no");
		?><div id="for_gateway" style="display:<? if($sel_protocol == "for_gateway") print "block'"; else print "none;";?>"><?
		editObject($dial_plan, $for_gateway, "Define DialPlan associated to a gateway", "Save",true,null,null,"for_gateway");
		?></div><?
		for($i=0; $i<count($protocols);$i++)
		{
			if(!isset($protocols[$i]))
				continue;
			?><div id="<?print $protocols[$i];?>" style="display:<? if ($sel_protocol == $protocols[$i]) print "block;"; else print "none;";?>"><?
			if(isset(${$protocols[$i]}['port'])) 
				if(!$orig_port)
					switch($protocols[$i]) {
						case 'sip':
							$dial_plan->port = '5060';
							break;
						case 'iax':
							$dial_plan->port = '4569';
							break;
						case 'h323':
							$dial_plan->port = '1720';
							break;
					}
			editObject($dial_plan,${$protocols[$i]}, "Define DialPlan for protocol ".strtoupper($protocols[$i]),"Save",true,null,null, $protocols[$i]);
			?></div><?	
		}
	}else{
		if($dial_plan->gateway_id) {
			$dial_plan->protocol = "for_gateway";
			$gateways["selected"] = $dial_plan->gateway_id;
			$additional = array("gateway"=>array($gateways, "display"=>"select", "compulsory"=>"true"));
		}else
			$additional = array("protocol"=>array("display"=>"fixed", "value"=>$dial_plan->protocol));
		editObject($dial_plan,array_merge($additional,${$dial_plan->protocol}), "Edit DialPlan","Save",true,null,null, $dial_plan->protocol);
	}
	end_form();
}

function edit_dial_plan_database()
{
	global $path;
	$path .= "&method=dial_plan";

	$dial_plan_id = getparam("dial_plan_id");
	$dial_plan = new Dial_Plan;
	$dial_plan->dial_plan_id = $dial_plan_id;
	$dial_plan->select();

	if($dial_plan_id)
		$protocol = ($dial_plan->gateway_id) ? "for_gateway" : $dial_plan->protocol;
	else
		$protocol = (getparam("gateway") != "Not selected") ? "for_gateway" : getparam("protocol");

	if(!$dial_plan_id)
	{
		if(getparam("gateway") == "Not selected" && getparam("protocol") == "Not selected")
		{
			edit_dial_plan("Incomplete information",$protocol);
			return;
		}
	}

	$dial_plan->priority = getparam($protocol."priority");
	if(!strlen($dial_plan->priority))
	{
		edit_dial_plan("Field priority is compulsory",$protocol);
		return;
	}
	if(Numerify($dial_plan->priority) == "NULL")
	{
		edit_dial_plan("Field Priority must be a positive numeric field between 1 and 32000.",$protocol);
		return;
	}

	//used to make a verifications
	$dial_plan2 = new Dial_Plan;
	$dial_plan2->dial_plan_id = $dial_plan_id;
	$dial_plan2->priority = getparam($protocol . "priority");
	if($dial_plan2->objectExists())
	{
		edit_dial_plan("This priority is already set to another dial plan",$protocol);
		return;
	}
	$dial_plan2->priority = NULL;
	$dial_plan2->dial_plan = getparam($protocol . "dial_plan");
	if($dial_plan2->objectExists()) 
	{
		edit_dial_plan("This DialPlan name is already used.",$protocol);
		return;
	}

	$dial_plan->dial_plan = getparam($protocol . "dial_plan");	

	$gateway = getparam("gateway");

	if($protocol == "for_gateway") 
	{
		$dial_plan->gateway_id = (getparam("gateway")) ? getparam("gateway") : getparam("for_gateway"."gateway");
		$gateway = new Gateway;
		$gateway->gateway_id = $dial_plan->gateway_id;
		$gateway->select();
		if(!$gateway->protocol) {
			edit_dial_plan("Please check to see if the gateway you selected is correctly defined.",$protocol);
			return;
		}
		//$dial_plan->protocol = $gateway->protocol;
	}else{
		switch($protocol)
		{
			case "iax":
				$dial_plan->iaxuser = getparam("iaxiaxuser");
				$dial_plan->iaxcontext = getparam("iaxiaxcontext");
			case "sip":
			case "h323":
				$dial_plan->server = getparam($protocol."server");
				if(!$dial_plan->server)
				{
					edit_dial_plan("Field server is compulsory for the selected protocol.",$protocol);
					return;
				}
				$dial_plan->port = getparam($protocol."port");
				if(!$dial_plan->port)
				{
					edit_dial_plan("Field Port is compulsory for the selected protocol.",$protocol);
					return;
				}
				if(Numerify($dial_plan->port) == "NULL")
				{
					edit_dial_plan("Field Port must be numeric.",$protocol);
				}
			case "wp":
			case "zap":
				$dial_plan->chans_group = getparam($dial_plan->protocol."chans_group");
				break;
		}
	}
	$dial_plan->formats = (getparam($protocol . "check_not_to_specify_formats")) ? NULL : get_formats($protocol."formats");
	$dial_plan->prefix = (getparam($protocol . "check_to_match_everything")) ? NULL : getparam($protocol . "prefix");
	$dial_plan->rtp_forward = (getparam($protocol . "rtp_forward") == "on") ? "t" : "f"; 

	if(getparam($protocol."check_to_match_everything")!="on" && !getparam($protocol."prefix"))
	{
		edit_dial_plan("You must either insert a prefix or Check to match everyting",$protocol);
		return;
	}

	if($dial_plan->dial_plan_id)
		notify($dial_plan->update(),$path);
	else{
		$dial_plan->protocol = ($protocol != "for_gateway") ? $protocol : NULL;
		notify($dial_plan->insert(),$path);
	}
}
*/

/*
function edit_gateway($error=NULL, $protocol = NULL)
{
	if($error)
		errornote($error);

	$gateway_id = getparam("gateway_id");
	$protocol = getparam("protocol");

	$gateway = new Gateway;
	$gateway->gateway_id = $gateway_id;
	$gateway->select();

	$sip_fields = array(
						"gateway"=>array("compulsory"=>true), 
						"username"=>array("compulsory"=>true), 
						"password"=>array("comment"=>"Insert only when you wish to change", "display"=>"password"),
						"server"=>array("compulsory"=>true),
						"description"=>array("display"=>"textarea"), 
						"authname"=>"", 
						"outbound"=>"",
						"domain"=>"",
						"localaddress"=>"",
						"interval"=>"",
 						"number"=>"",
						"formats"=>array("display"=>"include_formats"), 
						"enabled"=>array("comment"=>"Check this field to mark that you wish to register to this server")
						);

	$h323_fields = $iax_fields = array(
						"gateway"=>array("compulsory"=>true),
						"username"=>array("compulsory"=>true), 
						"password"=>array("comment"=>"Insert only when you wish to change", "display"=>"password"),
						"server"=>array("compulsory"=>true),
						"description"=>array("display"=>"textarea"), 
						"interval"=>"", 
						"number"=>"",
						"formats"=>array("display"=>"include_formats"), 
						"enabled"=>array("comment"=>"Check this field to mark that you wish to register to this server")
					);

	start_form();
	addHidden("database", array("gateway_id"=>$gateway_id, "sprotocol"=>$gateway->protocol));
	if(!$gateway_id) 
	{
		$sip_fields["password"]["compulsory"] = true;
		$h323_fields["password"]["compulsory"] = true;
		$iax_fields["password"]["compulsory"] = true;
		unset($sip_fields["password"]["comment"]);
		unset($h323_fields["password"]["comment"]);
		unset($iax_fields["password"]["comment"]);
		$protocols = array("sip", "h323", "iax");

		if($protocol)
		{
			$protocols["selected"] = $protocol;
			$fields = $protocol."_fields";
			foreach(${$fields} as $fieldname=>$fieldformat)
			{
				if($gateway->variable($fieldname))
					$gateway->{$fieldname} = getparam($protocol . $fieldname);
			}
			if($gateway->enabled == "on")
				$gateway->enabled = "t";
			$gateway->formats = get_formats($protocol."formats");
		}

		$fields = array(
						"protocol"=>array($protocols,"display"=>"select","javascript"=>'onChange="form_for_gateway();"')
						);

		editObject($gateway,$fields,"Select protocol for new gateway","no");
		?><div id="div_sip" style="display:<? if ($protocol != "sip") print "none;"; else print "block;";?>"><?
		editObject($gateway,$sip_fields, "Define SIP gateway", "Save",true,null,null,"sip");
		?></div><?
		?><div id="div_h323" style="display:<? if ($protocol != "h323") print "none;"; else print "block;";?>"><?
		editObject($gateway,$h323_fields, "Define for H3232 gateway", "Save",true,null,null,"h323");
		?></div><?
		?><div id="div_iax" style="display:<? if ($protocol != "iax") print "none;"; else print "block;";?>"><?
		editObject($gateway,$iax_fields, "Define for IAX gateway", "Save",true,null,null,"iax");
		?></div><?
	}else{
		$function = $gateway->protocol . "_fields";
		editObject($gateway,${$function}, "Edit ".strtoupper($gateway->protocol). " gateway", "Save", true,null,null,$gateway->protocol);
	}
	end_form();
}

function edit_gateway_database()
{
	global $module, $method, $path;
	$path .= "&method=gateways";

	$gateway = new Gateway;
	$gateway->gateway_id = getparam("gateway_id");
	if($gateway->gateway_id)
		$gateway->gateway = getparam(getparam("sprotocol")."gateway");
	else
		$gateway->gateway = getparam(getparam("protocol")."gateway");
	if($gateway->objectExists() && $gateway->gateway) 
	{
		$protocol = ($gateway->protocol) ? $gateway->protocol : getparam("protocol");
		edit_gateway("A gateway named ".$gateway->gateway." is already is use.",$protocol);
		return;
	}
	$gateway->select();
	$protocol = ($gateway->protocol) ? $gateway->protocol : getparam("protocol");

	if($gateway->gateway_id)
		$compulsory = array("gateway", "username", "server");
	else
		$compulsory = array("gateway", "username", "password", "server");
	for($i=0; $i<count($compulsory); $i++)
	{
		if(!($gateway->{$compulsory[$i]} = getparam($protocol.$compulsory[$i])))
		{
			edit_gateway("Field ".$compulsory[$i]." is compulsory.",$protocol);
			return;
		}
	}
	$gateway->protocol = ($gateway->protocol) ? $gateway->protocol : $protocol;
	if(!$gateway->protocol)
	{
		errormess("Can't make this operation. Don't have a protocol setted.",$path);
		return;
	}

	$sip = array('authname','outbound', 'domain', 'localaddress', 'description', 'interval', 'number');

	$h323 = $iax = array('description', 'interval', 'number');

	for($i=0; $i<count(${$protocol}); $i++) {
		$gateway->{${$protocol}[$i]} = getparam($protocol.${$protocol}[$i]);
	}
	if(getparam($protocol."password"))
		$gateway->password = getparam($protocol."password");
	$gateway->formats = get_formats($protocol."formats");
	$gateway->enabled = (getparam($protocol."enabled") == "on") ? "t" : "f";
	$gateway->modified = "t";
	if(!$gateway->gateway_id)
		notify($gateway->insert(true), $path);
	else
		notify($gateway->update(),$path);
}
*/
?>
</div>