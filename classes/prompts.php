<?
require_once("framework.php");

class Prompt extends Model
{
	public static function variables()
	{
		return array(
					"prompt_id" => new Variable("serial"),
					"prompt" => new Variable("text"),
					"description" => new Variable("text"),
					"status" => new Variable("text") //online offline
				);
	}

	function __construct()
	{
		parent::__construct();
	}
}
?>