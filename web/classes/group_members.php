<?

require_once("framework.php");

class Group_Member extends Model
{
	public static function variables()
	{
		return array(
					"group_member_id" => new Variable("serial", "!null"),
					"group_id" => new Variable("serial","!null","groups",true),
					"extension_id" => new Variable("serial","!null","extensions",true)
				);
	}

	function __construct()
	{
		parent::__construct();
	}
}

?>