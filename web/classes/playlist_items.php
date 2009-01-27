<?
require_once("framework.php");

class Playlist_Item extends Model
{
	public static function variables()
	{
		return array(
					"playlist_item_id" => new Variable("serial","!null"),
					"playlist_id" => new Variable("serial","!null","playlists",true),
					"music_on_hold_id" => new Variable("serial","!null","music_on_hold",true)
				);
	}

	function __construct()
	{
		parent::__construct();
	}
}
?>