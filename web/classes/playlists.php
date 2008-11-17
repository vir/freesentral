<?
require_once("framework.php");

class Playlist extends Model
{
	public static function variables()
	{
		return array(
					"playlist_id" => new Variable("serial"),
					"playlist" => new Variable("text"),
					"in_use" => new Variable("bool",false) //only one row will be set to true 
				);
	}

	function __construct()
	{
		parent::__construct();
	}
}

?>