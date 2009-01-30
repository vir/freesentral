<?

require_once("framework.php");

class Group extends Model
{
	public static function variables()
	{
		return array(
					"group_id" => new Variable("serial","!null"),
					"group" => new Variable("text","!null"),
					"description" => new Variable("text"),
					"extension" => new Variable("text","!null"),
					"mintime" => new Variable("int2"),
					"length" => new Variable("int2"),
					"maxout" => new Variable("int2"),
					"greeting" => new Variable("text"),
					"maxcall" => new Variable("int2"),
					"prompt" => new Variable("text"),
					"detail" => new Variable("bool"),
					"playlist_id" => new Variable("serial",NULL,"playlists")   //prompts to be played when user is in queue
				);
	}

	function __construct()
	{
		parent::__construct();
	}

	public function setObj($params)
	{
		$this->group = field_value("group",$params);
		if(($msg = $this->objectExists()))
			return array(false,(is_numeric($msg)) ? "There is already a group with this name ".$group->group : $msg);

		$this->select();
		$this->setParams($params);
		if(strlen($this->extension) != 2)
			return array(false,"Field 'Extension' must be at least 2 digits long");

		$this2 = new Group;
		$this2->group_id = $this->group_id;
		$this2->extension = $this->extension;

		if($this2->objectExists())
			return array(false,"A group with this extension already exists: ".$this2->extension);
		$this->playlist_id = field_value("playlist",$params);
		if($this->playlist_id == "Not selected")
			$this->playlist_id = NULL;
	//	if(!$this->playlist_id || $this->playlist_id == "Not selected")
	//		return array(false, "Please select a file for music on hold. If you don't have any file uploaded go to Settings >> Music on Hold in order to upload the songs and create playlists.");

		return parent::setObj($params);
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