<?
require_once("framework.php");

class Did extends Model
{
	public static function variables()
	{
		return array(
					"did_id" => new Variable("serial"),
					"did" => new Variable("text"),
					"number" => new Variable("text"),
					"destination" => new Variable("text"),
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
}
?>