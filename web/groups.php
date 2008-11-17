<?

require_once("framework.php");

class Group extends Model
{
	public static function variables()
	{
		return array(
					"group_id" => new Variable("serial"),
					"group" => new Variable("text"),
					"description" => new Variable("text"),
					"extension" => new Variable("text"),
					"mintime" => new Variable("int2"),
					"length" => new Variable("int2"),
					"maxout" => new Variable("int2"),
					"greeting" => new Variable("text"),
					"maxcall" => new Variable("int2"),
					"prompt" => new Variable("text"),
					"details" => new Variable("bool"),
					"playlist_id" => new Variable("serial",NULL,"playlosts")   //prompts to be played when user is in queue
				);
	}

	function __construct()
	{
		parent::__construct();
	}
}
/*
	This are all the parameters supported by the queues module
	The majority of them aren't implemented in the interface!!

;  mintime: int: Minimum time between queries, in milliseconds
;  length: int: Maximum queue length, will declare congestion if grows larger
;  maxout: int: Maximum number of simultaneous outgoing calls to operators
;  greeting: string: Resource to be played initially as greeting
;  onhold: string: Resource to be played while waiting in queue
;  maxcall: int: How much to call the operator, in milliseconds
;  prompt: string: Resource to play to the operator when it answers
;  notify: string: Target ID for notification messages about queue activity
;  detail: bool: Notify when details change, including call position in queue
;  single: bool: Make just a single delivery attempt for each queued call
*/

?>