<?
require_once("framework.php");

class Short_Name extends Model
{
	public function variables()
	{ 
		return array(
				"short_name_id" => new Variable("serial"),
				"short_name" => new Variable("text"),
				"name" => new Variable("text"),
				"number" => new Variable("text")
			);
	}

	function __construct()
	{
		parent::__construct();
	}	
}
?>