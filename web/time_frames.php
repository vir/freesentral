<?
require_once("framework.php");

class Time_Frame extends Model
{
	public static function variables()
	{
		return array(
					"time_frame_id" => new Variable("serial"),
					"prompt_id" => new Variable("serial",NULL,"prompts"),
					"day" => new Variable("text"), //day the week
					"start_hour" => new Variable("text"), 
					"end_hour" => new Variable("text"),
					"numeric_day" => new Variable("int2")
				);
	}

	function __construct()
	{
		parent::__construct();
	}
}
?>