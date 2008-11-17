<?

require_once("framework.php");

class Pbx_Setting extends Model
{
	public static function variables()
	{
		return array(
					"pbx_setting_id" => new Variable("serial"),
					"extension_id" => new Variable("serial", NULL, "extensions"),
					"param" => new Variable("text"),
					"value" => new Variable("text")
				);
	}

	function __construct()
	{
		parent::__construct();
	}
}

?>