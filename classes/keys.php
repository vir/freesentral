<?
require_once("framework.php");

class Key extends Model
{
	public static function variables()
	{
		return array(
					"key_id" => new Variable("serial"),
					"key" => new Variable("text"),
					"prompt_id" => new Variable("serial",NULL,"prompts"),
					"destination" => new Variable("text"),
					"description" => new Variable("text")
				);
	}

	function __construct()
	{
		parent::__construct();
	}

	// use this function so that name of the table is not automatically "keies"
	public function getTableName()
	{
		return "keys";
	}
}
?>