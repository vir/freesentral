<?

require_once("framework.php");

class Extension extends Model
{
	public static function variables()
	{
		return array(
					"extension_id" => new Variable("serial"),
					"extension" => new Variable("text"),
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
		$extensions = Model::selection("extension", array("extension"=>$this->extension, "password"=>$this->password));
		if(count($extensions) == 1) 
		{
			foreach($extensions[0] as $var_name=>$var)
			{
				$this->{$var_name} = $extensions[0]->{$var_name};
			}
			self::writeLog("extension ".$this->extension." logged in");
			return true;
		} else {
			self::writeLog("failed attempt to log in as extension:".$this->extension);
			return false;
		}
	}
}