<?

require_once("framework.php");

class Dial_Plan extends Model
{
	public static function variables()
	{
		return array(
					"dial_plan_id" => new Variable("serial"),
					"dial_plan" => new Variable("text"),
					"priority" => new Variable("int2"),
					"prefix" => new Variable("text"),
					// if this dial_plan points to a gateway the next group of fields will be empty
					"gateway_id" => new Variable("serial",null,"gateways",true),
					// if this is not a dial_plan that points to a gateway then some of the fields from below are compulsory
/*					"protocol" => new Variable("text"),
					"ip" => new Variable("text"),
					"port" => new Variable("text"),
					"iaxuser" => new Variable("text"),
					"iaxcontext" => new Variable("text"),
					"chans_group" => new Variable("text"),
					"formats" => new Variable("text"),
					"rtp_forward" => new Variable("bool"),*/
					// optional fields for rewriting digits
					"nr_of_digits_to_cut" => new Variable("int2"),
					"position_to_start_cutting" => new Variable("int2"),
					"nr_of_digits_to_replace" => new Variable("int2"),
					"digits_to_replace_with" => new Variable("text"),
					"position_to_start_replacing" => new Variable("int2"),
					"position_to_start_adding" => new Variable("int2"),
					"digits_to_add" => new Variable("text")
				);
	}

	function __construct()
	{
		parent::__construct();
	}
}

?>