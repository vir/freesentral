<?
require_once("framework.php");

class Time_Frame extends Model
{
	public static function variables()
	{
		return array(
					"time_frame_id" => new Variable("serial"),
					"prompt_id" => new Variable("serial", "!null","prompts"),
					"day" => new Variable("text", "!null"), //day the week
					"start_hour" => new Variable("text"), 
					"end_hour" => new Variable("text"),
					"numeric_day" => new Variable("int2", "!null")
				);
	}

	function __construct()
	{
		parent::__construct();
	}
}
?>