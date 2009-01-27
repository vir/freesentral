<?

require_once("framework.php");

class Extension extends Model
{
	public static function variables()
	{
		return array(
					"extension_id" => new Variable("serial","!null"),
					"extension" => new Variable("text","!null"),
					"password" => new Variable("text"),
					"firstname" => new Variable("text"),
					"lastname" => new Variable("text"),
					"address" => new Variable("text"),
					"inuse" => new Variable("int4"),
					"location" => new Variable("text"),
					"expires" => new Variable("timestamp"),
					"max_minutes" => new Variable("interval"),
					"used_minutes" => new Variable("interval","00:00:00"),
					"inuse_count" => new Variable("int2"),
					"inuse_last" => new Variable("timestamp"),
			/*		"mac_address" => new Variable("text"),
					"equipment_id" => new Variable("serial",NULL,"equipments")*/
				);
	}

	function __construct()
	{
		parent::__construct();
	}

	public function login()
	{
		if (!$this->extension || !$this->password)
			return NULL;
		$thiss = Model::selection("extension", array("extension"=>$this->extension, "password"=>$this->password));
		if(count($thiss) == 1) 
		{
			foreach($thiss[0] as $var_name=>$var)
			{
				$this->{$var_name} = $thiss[0]->{$var_name};
			}
			self::writeLog("extension ".$this->extension." logged in");
			return true;
		} else {
			self::writeLog("failed attempt to log in as extension:".$this->extension);
			return false;
		}
	}

	public function setObj($params)
	{
		$this->extension = field_value("extension", $params);
		if(Numerify($this->extension) == "NULL")
			return array(false, "Field extension must be numeric");
		if(strlen($this->extension) < 3)
			return array(false,"Field extension must be minimum 3 digits");

		if(($msg = $this->objectExists()))
			return array(false, (is_numeric($msg)) ? "This extension already exists ".$this->extension : $msg);
		$this->select();
		$this->setParams($params);
		if($this->max_minutes)
			$this->max_minutes = minutes_to_interval($this->max_minutes);
	/*	$this->mac_address = field_value("mac_address",$params);
		if($this->mac_address)
		{
			if(field_value("equipment",$params) != "Not selected")
				$this->equipment_id = field_value("equipment",$params);
			else
				return array(false, "Please select equipment you wish to provision.");
		}*/
		if(!$this->password)
			return array(false,"Field password is compulsory.");
		if(strlen($this->password) < 6)
			return array(false,"Password must be at least 6 digits long");
		if(Numerify($this->password) == "NULL")
			return array(false,"Field password must be numeric");
		return parent::setObj($params);
	}
}