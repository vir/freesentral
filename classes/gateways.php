<?

require_once("framework.php");

class Gateway extends Model
{
	public static function variables()
	{
		return array(
					// compulsory fields
					"gateway_id" => new Variable("serial"),
					"gateway" => new Variable("text"),
					"protocol" => new Variable("text"),
					"server" => new Variable("text"),

					// for gateways with registration
					"username" => new Variable("text"),
					"password" => new Variable("text"),
					"enabled" => new Variable("bool"),
					// various params that are not compulsory
					"description" => new Variable("text"),
					"interval" => new Variable("text"),
					"authname" => new Variable("text"),
					"number" => new Variable("text"),
					"domain" => new Variable("text"),
					"outbound" => new Variable("text"),
					"localaddress" => new Variable("text"),
					"formats" => new Variable("text"),

					// for gateways without registrations
					//"ip" => new Variable("text"), -> was replaced with server
					"port" => new Variable("text"),
					"iaxuser" => new Variable("text"),
					"iaxcontext" => new Variable("text"),
					"chans_group" => new Variable("text"),
					"formats" => new Variable("text"),

					"rtp_forward" => new Variable("bool"),
					"status" => new Variable("text"), // yate will set this field after trying to autenticate
					"modified" => new Variable("bool") //field necesary for yate, autenticate again if modified is true
				);
	}

	function __construct()
	{
		parent::__construct();
	}

	function getTableName()
	{
		return "gateways";
	}
}

?>