<?
require_once("framework.php");

// Default class used for logging 
class ActionLog extends Model
{
	public static function variables()
	{
		return array(
					"date" => new Variable("timestamp"),
					"log" => new Variable("text"), // log in human readable form meant to be displayed
					"performer_id" => new Variable("text"), // id of the one performing the action (taken from $_SESSION)
					"performer" => new Variable("text"), // name of the one performing the action (taken from $_SESSION)
					"real_performer_id" => new Variable("text"),
					"object" => new Variable("text"),  // name of class that was marked as performer for actions
					"query" => new Variable("text") //query that was performed
				);
	}

	function __construct()
	{
		parent::__construct();
	}

	public static function index()
	{
		return array(
					"date"
				);
	}
}
?>