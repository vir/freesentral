<?
require_once("framework.php");

class Did extends Model
{
	public static function variables()
	{
		return array(
					"did_id" => new Variable("serial", "!null"),
					"did" => new Variable("text", "!null"),
					"number" => new Variable("text", "!null"),
					"destination" => new Variable("text", "!null"),
					"description" => new Variable("text"),
					"default_destination" => new Variable("text"),
					"extension_id" => new Variable("serial",NULL,"extensions",true),
					"group_id" => new Variable("serial",NULL,"groups",true)
				);
	}

	function __construct()
	{
		parent::__construct();
	}

	function setObj($params)
	{
		$this->number = field_value("number",$params);

		if(($msg = $this->objectExists()))
			return array(false, (is_numeric($msg)) ? "A DID for this number already exists." : $msg);
		if(Numerify($this->number) == "NULL")
			return array(false,"Field 'Number' must be numeric.");

		$this->did = field_value("did",$params);
		$this->number = NULL;
		if(($msg = $this->objectExists()))
			return array(false,"A DID with this name already exists.");
		$this->select();
		$this->setParams($params);
		$this->extension_id = field_value("extension",$params);
		$this->group_id = field_value("group",$params);
		if(substr($this->destination,0,9) == "external/" && !$this->default_destination)
			return array(false, "Field 'Default destination' is compulsory if 'Destination' is a script");
		if(substr($this->destination,0,9) != "external/" && $this->default_destination!="Not selected")
		{
		//	print "Field 'Default destination' was ignored. It is taken into account only when destination is a script.";
			$this->default_destination = NULL;
			$this->extension_id = NULL;
			$this->group_id = NULL;
		}
		if($this->default_destination == "Not selected")
			$this->default_destination= NULL;
		if($this->default_destination == "extension")
		{
			if(!$this->extension_id || $this->extension_id == "Not selected")
				return array(false,"Please select an extension");
			$this->group_id = NULL;
		}
		if($this->default_destination == "group")
		{
			if(!$this->group_id || $this->group_id == "Not selected")
				return array(false,"Please select a group");
			$this->extension_id = NULL;
		}
		return parent::setObj($params);
	}
}
?>