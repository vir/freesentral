<?
require_once("framework.php");

class Playlist extends Model
{
	public static function variables()
	{
		return array(
					"playlist_id" => new Variable("serial","!null"),
					"playlist" => new Variable("text","!null"),
					"in_use" => new Variable("bool",false) //only one row will be set to true 
				);
	}

	function __construct()
	{
		parent::__construct();
	}

	public static function defaultObject()
	{
		$playlist = Model::selection("playlist", array("in_use"=>"t"));
		if(!count($playlist)) {
			$playlist = new PlayList;
			$playlist->playlist = "Default playlist";
			$playlist->in_use = "t";
			$playlist->insert();
			return true;
		}
		return false;
	}

	public function setObj($params)
	{
		$this->playlist = field_value("playlist",$params);
		if(($msg = $this->objectExists()))
			return array(false,(is_numeric($msg)) ? "There is another playlist with this name." : $msg);
		$this->select();
		$this->setParams($params);
		$this->in_use = ($this->in_use == "on") ? "t" : "f";
		if($this->in_use == "t") {
			$pl = new Playlist;
			$pl->in_use = 'f';
			$conditions = array("in_use"=>true);
			if($this->playlist_id)
				$conditions["playlist_id"] = "!=".$this->playlist_id;
			$pl->fieldUpdate($conditions, array("in_use"));
		}
		return parent::setObj($params);
	}
}

?>