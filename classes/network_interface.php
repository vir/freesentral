<?
require_once("framework.php");

class Network_Interface extends Model
{
	public static function variables()
	{
		return array(
					"network_interface_id" => new Variable("serial"),
					"network_interface" => new Variable("text"),
					"protocol" => new Variable("text"),
					"ip_address" => new Variable("text"),
					"netmask" => new Variable("text"),
					"gateway" => new Variable("text")
				);
	}

	function __construct()
	{
		parent::__construct();
	}
}
?>