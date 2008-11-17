<?

require_once("framework.php");

class Call_Log extends Model
{
	public static function variables()
	{
		return array(
					"time" => new Variable("timestamp"),
					"chan" => new Variable("text"),
					"address" => new Variable("text"),
					"direction" => new Variable("text"),
					"billid" => new Variable("text"),
					"caller" => new Variable("text"),
					"called" => new Variable("text"),
					"duration" => new Variable("interval"),
					"billtime" => new Variable("interval"),
					"ringtime" => new Variable("interval"),
					"status" => new Variable("text"),
					"reason" => new Variable("text"),
					"ended" => new Variable("bool")
				);
	}

	function __construct()
	{
		parent::__construct();
	}

 	public static function index()
	{
		return array(
					"time",
					"comb_time_ended"=>"time,ended"
				);
	}
}

?>