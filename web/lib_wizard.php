<?
require_once("conf_wizard.php");
global $trigger_name, $upload_path;

class Wizard
{
	public $logo;
	public $steps;
	public $title;
	public $finished_setting;
	public $fields;
	public $step_nr;
	public $error = '';
	public $finished_settings = false;

	public $reserved_names = array("step_description"=>"", "step_image"=>"", "step_name"=>"", "upload_form"=>"", "on_submit"=>"");

	function __construct($_steps, $_logo, $_title, $function_for_finish)
	{
		$this->steps = $_steps;
		$this->logo = $_logo;
		$this->title = $_title;
		
		if(!isset($_SESSION["wizard_step_nr"]))
			$_SESSION["wizard_step_nr"] = 0;
		$this->step_nr = $_SESSION["wizard_step_nr"];

		if(getparam("submit") == "Next") {
			// set the information that was setted in the previous step
			$this->setStep();
			if($this->error == '')
				$this->incStep();
		}elseif(getparam("submit") == "Previous")
			$this->decStep();
		elseif(getparam("submit") == "Skip" && ($this->step_nr < (count($this->steps)-1)))
		{
			$this->setStep(true); // set step allowing variables to be null, even if they were required for the completion of this step

			if ($this->error == '')
				$this->incStep();
		}elseif(getparam("submit") == "Finish" || ($this->step_nr == (count($this->steps) -1) && getparam("submit") != "Retry")) {
			if(getparam("submit") == "Skip")
				$this->setStep(true);
			else
				$this->setStep();
			// $function_for_finish() must return something like array(true/false, $message) where true shows that the process is finished, while false shows that it's wasn't finished, $message is a message returned by the function that will be printed
			if($this->error == '')
				$this->finished_settings = $function_for_finish();
		}//elseif(getparam("submit") == "Retry") {
			//print '<br/><br/>submit is Retry<br/><br/>';
		//}
		if(!$this->finished_settings)
			$this->loadStep();

		$this->htmlFrame();
	}

	// 
	function loadStep()
	{
		global $fields;
		global $trigger_name;
		// load the array of fields from the conf file for this step
		$fields = $this->steps[$this->step_nr];
		// set the reserved fields for this step (image, description, title) and stem remove them from the lists of fields
		foreach($this->reserved_names as $reserved_field_name=>$reserved_field_value)
		{
			$this->reserved_names[$reserved_field_name] = (isset($fields[$reserved_field_name])) ? $fields[$reserved_field_name] : '';
			if(isset($fields[$reserved_field_name]))
				unset($fields[$reserved_field_name]);
		}

		if(isset($_SESSION["fields"][$this->step_nr])) {
			foreach($fields as $field_name=>$field_description) {
				if(!isset($this->steps[$this->step_nr][$field_name]))
					continue;
				if(isset($field_description["display"])) { 
					if($field_description["display"] == "message" || $field_description["display"] == "fixed")
						continue;
					if($field_description["display"] == "file") {
						 $fields[$field_name]["value"] = (isset($_SESSION["fields"][$this->step_nr][$field_name]["orig_name"])) ? $_SESSION["fields"][$this->step_nr][$field_name]["orig_name"] : '';
						if($fields[$field_name]["value"] != '') {
							$fields["fake_".$field_name] = array();
							$fields["fake_".$field_name]["value"] =  $fields[$field_name]["value"];
							$fields["fake_".$field_name]["display"] = "hidden";
						}
						continue;
					}
				}

				$fields[$field_name]["value"] = (isset($_SESSION["fields"][$this->step_nr][$field_name])) ? $_SESSION["fields"][$this->step_nr][$field_name] : '';
				if(isset($field_description["display"]))
					if($field_description["display"] == "select")
						if(isset($fields[$field_name][0]))
							$fields[$field_name][0]["selected"] = $fields[$field_name]["value"];
				if($fields[$field_name]["value"] != "")
					if(isset($fields[$field_name]["triggered_by"])) {
						$trigged_by = $fields[$field_name]["triggered_by"];
						$former_trigger =  $trigged_by -1;;
						if(isset($fields[$trigger_name.$former_trigger]))
							unset($fields[$trigger_name.$former_trigger]);
						$fld = $fields;
						foreach($fld as $fldn=>$fldd) {
							if(isset($fldd["triggered_by"]))
								if($fldd["triggered_by"] == $trigged_by)
									unset($fields[$fldn]["triggered_by"]);
						}
						unset($fields[$field_name]["triggered_by"]);
					}
			}
		}
	}

	/**
	 * Set the current step. It takes the information that was setted before submiting 
	 * the page and sets the fields into the session. Checks for required fields and sets 
	 * $this->error in case some required fields are missing
	 * @param $skip Bool value, true when  you wish to skip the verifications with required fields
	 */
	function setStep($skip = false)
	{
		global $upload_path;
		$fields = $this->steps[$this->step_nr];

		foreach($fields as $field_name=>$field_def)
		{
			if(isset($this->reserved_names[$field_name]))
				continue;
			if(isset($field_def["display"]))
				if($field_def["display"] == "message" || $field_def["display"] == "fixed")
					continue;
			if(!isset($_SESSION["fields"]))
				$_SESSION["fields"] = array();

			$set = false;
			if(isset($field_def["display"]))
				if($field_def["display"] == "file" && $skip === false) {
					if(!$upload_path) {
						print "<br/>Upload directory is not set. Could not upload. ".$field_name."<br/>";
						continue;
					}

					if(!$_FILES[$field_name]["name"] && isset($_SESSION["fields"][$this->step_nr][$field_name]["path"]))
						continue;

					$is_required = false;
					if(isset($field_def["required"]))
						if($field_def["required"] === true || $field_def["required"] === "true")
							$is_required = true;
					if(isset($field_def["compulsory"]))
						if($field_def["compulsory"] === true || $field_def["compulsory"] === "true")
							$is_required = true;

					if(!$_FILES[$field_name]['tmp_name'] && !$is_required)
						continue;
					elseif(!$_FILES[$field_name]['name']){
						$this->error .= " Couldn't upload $field_name.";
						continue;
					}
					$file = basename($_FILES[$field_name]['name']);
					$new_filename = "$upload_path/$file";
					if(is_file($new_filename)) {
						$parts = explode(".", $new_filename);
						$extension = $parts[count($parts) - 1];
						unset($parts[count($parts) - 1]);
						$new_filename = implode(".",$parts);
						$new_filename .= "_";
						$new_filename .= ".".$extension;
					}

					if (!move_uploaded_file($_FILES[$field_name]['tmp_name'],$new_filename))
						$this->error .= " Couldn't upload $field_name.";
					else{
						$_SESSION["fields"][$this->step_nr][$field_name]["orig_name"] = $file;
						$_SESSION["fields"][$this->step_nr][$field_name]["path"] = $new_filename;
					}
					$set = true;
				}
			if(!$set)
				$_SESSION["fields"][$this->step_nr][$field_name] = getparam($field_name);
			if($skip === false) {
				if(isset($fields[$field_name]["required"]))
					if($fields[$field_name]["required"] === true || $fields[$field_name]["required"]=="true") {
						if(isset($fields[$field_name]["display"]))
							if($fields[$field_name]["display"] == "file")
								if(!isset($_FILES[$field_name]["name"])) {
									$this->error .= " Field '".ucfirst(str_replace("_"," ",$field_name))."' is required. Please upload file.";	
									continue;
								}
						if(!getparam($field_name) && !isset($fields[$field_name]["triggered_by"]))
							$this->error .= " Field '".ucfirst(str_replace("_"," ",$field_name))."' is required.";
					}
					
				if(isset($fields[$field_name]["compulsory"]))
					if($fields[$field_name]["compulsory"] === true || $fields[$field_name]["compulsory"]=="true") {
						if(isset($fields[$field_name]["display"]))
							if($fields[$field_name]["display"] == "file") {
								if(!isset($_FILES[$field_name]["name"])) 
									$this->error .= " Field '".ucfirst(str_replace("_"," ",$field_name))."' is required. Please upload file.";	
								continue;
							}
						if(!getparam($field_name) && !isset($fields[$field_name]["triggered_by"]))
							$this->error .= " Field '".ucfirst(str_replace("_"," ",$field_name))."' is required.";
					}
			}
		}
	}

	/**
	 * Decrement current step_nr
	 */
	function decStep()
	{
		if($this->step_nr > 0)
			$this->step_nr--;
		$_SESSION["wizard_step_nr"] = $this->step_nr;
	}

	/**
	 * Increment current step_nr
	 */
	function incStep()
	{
		$this->step_nr++;
		$_SESSION["wizard_step_nr"] = $this->step_nr;
	}

	/**
	 * Create the form for the current step_nr
	 */
	function htmlFrame()
	{
		global $fields;

		if($this->reserved_names["upload_form"] != "")
			start_form(NULL, "post", true);
		else
			start_form();
		addHidden();
		$fin = $this->finished_settings;

		if(!$fin) {
			print '<table class="wizard" cellspacing="0" cellpadding="0">';
			print '<tr>';
			print '<td class="fillall" colspan="2">';
			print '<table class="fillall" cellspacing="0" cellpadding="0">';
			print '<tr>';
			print '<td class="logo_wizard">';
			if(isset($this->logo))
				print '<img src="'.$this->logo.'">';
			print '</td>';
			print '<td class="title_wizard">';
			if(isset($this->title))
				print '<div class="title_wizard">'.$this->title.'</div>';
			print '</td>';
			print '</tr>';
			print '</table>';
			print '</td>';
			print '</tr>';
			print '<tr>';
			print '<td class="wiz_description">';
			if($this->reserved_names["step_image"] != '' || $this->reserved_names["step_description"] != '')
			{
				print '<table class="wizard_step_description" cellspacing="0" cellpadding="0">';
				if($this->reserved_names["step_image"] != '')
				{
					print '<tr><td class="step_image">';
					print '<img src="'.$this->reserved_names["step_image"].'" />';
					print '</td></tr>';
				}
				if($this->reserved_names["step_description"] != '') 
				{
					print '<tr><td class="step_description">';
					print $this->reserved_names["step_description"];
					print '</td></tr>';
				}
				print '</table>';
			}
			print '</td>';
			print '<td class="wizard_content">';
			print '<table class="wizard_content" cellspacing="0" cellpadding="0">';
			print '<tr>';
			print '<th class="wizard_content" colspan="2">';
			print $this->reserved_names["step_name"];
			print '</th>';
			print '</tr>';
			print '<tr>';
			print '<td class="wizard_content">';
			print '<table class="wizard_fields" cellspacing="0" cellpadding="0">';

			if($this->error != '') {
				print '<tr>';
				print '<td colspan="2" class="wizard_error">';
				errormess($this->error, 'no');
				print '</td>';
				print '</tr>';
			}
			foreach($fields as $field_name=>$field_format)
				display_pair($field_name, $field_format, null, null, 'wizedit', false, NULL, NULL);
			print '</table>';

			print '<table class="fillall wizard_submit" cellspacing="0" cellpadding="0">';
			print '<tr>';
			print '<td class="fillall wizard_submit">';
			if($this->step_nr != 0)
				print '<input type="submit" name="submit" value="Previous">&nbsp;&nbsp;';
			if($this->step_nr < (count($this->steps)-1)) {
				if($this->reserved_names["on_submit"] == '')
					print '<input type="submit" name="submit" value="Next"/>&nbsp;&nbsp;';
				else
					print '<input type="submit" name="submit" value="Next" onClick="return on_submit(\''.$this->reserved_names["on_submit"].'\');">&nbsp;&nbsp;';
				if($this->step_nr == 0)
					print '<input type="button" name="submit" value="Skip" onClick="location.href=\'main.php?module=HOME\'">';
				else
					print '<input type="submit" name="submit" value="Skip"/>';
			}else{
				if($this->reserved_names["on_submit"] == '')
					print '<input type="submit" name="submit" value="Finish">&nbsp;&nbsp;';
				else
					print '<input type="submit" name="submit" value="Finish" onClick="return on_submit(\''.$this->reserved_names["on_submit"].'\')">&nbsp;&nbsp;';
				print '<input type="submit" name="submit" value="Skip"/>';
			}
			print '</td>';
			print '</tr>';
			print '</table>';
			print '</td>';
			print '</tr>';
			print '</table>';
			print '</td>';
			print '</tr>';
			print '</table>';
		}else{
			print '<table class="wizard" cellspacing="0" cellpadding="0">';
			print '<tr>';
			print '<td class="fillall" colspan="2">';
			print '<table class="fillall" cellspacing="0" cellpadding="0">';
			print '<tr>';
			print '<td class="logo_wizard">';
			if(isset($this->logo))
				print '<img src="'.$this->logo.'">';
			print '</td>';
			print '<td class="title_wizard">';
			if(isset($this->title))
				print '<div class="title_wizard">'.$this->title.'</div>';
			print '</td>';
			print '</tr>';
			print '</table>';
			print '</td>';
			print '</tr>';
			if($fin[0]) {
				print '<tr>';
				print '<td class="fillall" colspan="2">';
				print '<br/><br/>The wizard has finished configuring your system.<br/><br/>';
				print $fin[1];
				print '<br/><br/>';
				print '</td>';
				print '</tr>';
				print '<tr>';
				print '<td class="fillall wizard_submit" colspan="2">';
				print '<input type="button" name="submit" value="Close" onClick="location.href=\'main.php?module=HOME\'">';
				print '</td>';
				print '</tr>';
				unset($_SESSION["fields"]);
				unset($_SESSION["wizard_step_nr"]);
			}else{
				print '<tr>';
				print '<td class="fillall" colspan="2">';
				print '<br/><br/>Couldn\'t finish configuring this system.<br/><br/>';
				errormess($fin[1], "no");
				print '<br/><br/>';
				print '</td>';
				print '</tr>';
				print '<tr>';
				print '<td class="fillall wizard_submit" colspan="2">';
				print '<input type="submit" name="submit" value="Retry" />&nbsp;&nbsp;';
				print '<input type="button" name="submit" value="Close" onClick="location.href=\'main.php?module=HOME\'">';
				print '</td>';
				print '</tr>';
			}	
			print '</table>';
		}
		end_form();
	}
}


?>