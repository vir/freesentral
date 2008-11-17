<?

require_once("framework.php");

class Group_Member extends Model
{
	public static function variables()
	{
		return array(
					"group_member_id" => new Variable("serial"),
					"group_id" => new Variable("serial",NULL,"groups",true),
					"extension_id" => new Variable("serial",NULL,"extensions",true)
				);
	}

	function __construct()
	{
		parent::__construct();
	}
}

?>