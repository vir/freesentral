<script language="javascript">
function playlistClick(lin,col,role)
{
    if (copacClick(lin,col,role)) {
		if (role.substring(0,8) == 'playlist')
			window.location = "main.php?module=music_on_hold&method=playlist_items&playlist="+copacValoare(lin,col);
		if (role == 'music_on_hold')
			window.location = "main.php?module=music_on_hold&method=edit_music_on_hold&music_on_hold="+copacValoare(lin,col)+"&playlist="+copacParinte(lin,col);
    }
}
</script>
<?
global $module, $method, $path, $action, $page, $limit, $actions;

if(!$method || $method == "manage")
	$method = $module;

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

$actions = array("&method=edit_music_on_hold"=>'<img src="images/edit.gif" title="Edit" alt="Edit"/>', "&method=delete_music_on_hold"=>'<img src="images/delete.gif" title="Delete" alt="Delete"/>', "&method=upload_music_on_hold"=>'<img src="images/upload.gif" title="ReUpload file" alt="ReUpload file"/>', "&method=listen_music_on_hold"=>'<img src="images/listen.gif" title="Play" alt="Play"/>');

draw_tree();
if (getparam("playlist"))
	print "<script language=\"javascript\">copacComuta('".getparam("playlist")."');</script>\n";
print '<div class="content">';
$call();
print '</div>';

function draw_tree()
{
	$item = new Playlist_Item;
	$item->extend(array("playlist"=>array("table"=>"playlists", "join"=>"RIGHT"), "music_on_hold"=>"music_on_hold"));
	$items = $item->extendedSelect(NULL, "playlist, music_on_hold");

	$items = Model::objectsToArray($items, array("playlist"=>"", "music_on_hold"=>""));
	tree($items, "playlistClick", "copac", "Playlists");
}

function music_on_hold()
{
	global $actions, $method, $action;
	$method = "music_on_hold";
	$action = NULL;

	$music_on_hold = Model::selection("music_on_hold",NULL,"music_on_hold");
	
	tableOfObjects($music_on_hold, array("file"=>"music_on_hold", "description"), "song", $actions, array("&method=upload_music_on_hold"=>"Upload new song"));
}

function upload_music_on_hold()
{
	$music_on_hold = new Music_on_Hold;
	$music_on_hold->music_on_hold_id = getparam("music_on_hold_id");
	if($music_on_hold->music_on_hold_id)
		$music_on_hold->select();
	$fields = array("upload_file"=>array("display"=>"file", "comment"=>"File must have extension .mp3"), "description"=>array("value"=>$music_on_hold->description, "display"=>"textarea"));

	start_form(NULL,"post",true);
	addHidden("database", array("music_on_hold_id"=>getparam("music_on_hold_id"), "MAX_FILE_SIZE"=>"10000000000"));
	editObject(NULL, $fields, "Upload song for Music on Hold", "Save");
	end_form();
}

function upload_music_on_hold_database()
{
	global $path, $target_path;
	$path .= "&method=music_on_hold";

	$filename = basename($_FILES["upload_file"]["name"]);
	if(strtolower(substr($filename,-4)) != ".mp3")
	{
		//errormess("File format must be .mp3",$path);
		notice("File format must be .mp3", "music_on_hold", false);
		return;
	}	
	$music_on_hold = new Music_On_Hold;
	$music_on_hold->music_on_hold_id = getparam("music_on_hold_id");
	$music_on_hold->music_on_hold = $filename;
	if($music_on_hold->objectExists()) {
		//errormess("This file already exists in the database",$path);
		notice("This file already exists in the database", "music_on_hold", false);
		return;
	}

	$fpath = "$target_path/music_on_hold";

	if(!is_dir($fpath))
		mkdir($fpath,0777);

	$file = "$fpath/$filename";
	if (!move_uploaded_file($_FILES["upload_file"]['tmp_name'],$file)) {
		//errormess("Could not upload file.",$path);
		notice("Could not upload file.", "music_on_hold", false);
		return;
	}
	//should do the converting from .wav to .au around here
	$au = str_replace(".mp3",".au",$file);
	passthru("sox $file  -r 8000 -c 1 -b -A $au");

	$music_on_hold->select();
	$music_on_hold->music_on_hold = $filename;
	$music_on_hold->description = getparam("description");
	if($music_on_hold->music_on_hold_id)
		$res = $music_on_hold->update();
	else
		$res = $music_on_hold->insert();

	if($res[0])
		//message("Succesfully uploaded music on hold song",$path);
		notice("Succesfully uploaded music on hold song", "music_on_hold");
	else
		//errormess("Could not upload music on hold song",$path);
		notice("Could not upload music on hold song", "music_on_hold", false);
}

function edit_music_on_hold()
{
	$music_on_hold = new Music_on_Hold;
	$music_on_hold->music_on_hold_id = getparam("music_on_hold_id");
	$music_on_hold->select();

	start_form();
	addHidden("database", array("music_on_hold_id"=>$music_on_hold->music_on_hold_id));
	editObject($music_on_hold, array("song"=>array("value"=>$music_on_hold->music_on_hold, "display"=>"fixed"), "description"=>array("display"=>"textarea")), "Edit Music on Hold song", "Save");
	end_form();
}

function edit_music_on_hold_database()
{
	global $path;
	$path .= "&method=music_on_hold";

	$music_on_hold = new Music_on_Hold;
	$music_on_hold->music_on_hold_id = getparam("music_on_hold_id");
	$params = form_params(array("description"));
	$res = $music_on_hold->edit($params);
	notice($res[1], "music_on_hold", $res[0]);
}

function delete_music_on_hold()
{
	ack_delete("music_on_hold",getparam("music_on_hold"), NULL, "music_on_hold_id", getparam("music_on_hold_id"));
}

function delete_music_on_hold_database()
{
	global $path, $target_path;
	$path .= "&method=music_on_hold";

	$music_on_hold = new Music_on_Hold;	
	$music_on_hold->music_on_hold_id = getparam("music_on_hold_id");
	if(!$music_on_hold->music_on_hold_id)
	{
		//errormess("Don't have the id to delete the file");
		notice("Don't have the id to delete the file", NULL, false);
		return;
	}
	$fpath = "$target_path/music_on_hold";
	$music_on_hold->select();
	$wavfile = $music_on_hold->music_on_hold;
	$aufile = str_replace(".wav", ".au", $wavfile);
	$wavres = true;
	$aures = true;
	if(is_file("$fpath/$wavfile"))
		$wavres = unlink("$fpath/$wavfile");
	if(is_file("$fpath/$aufile"))
		$aufile = unlink("$fpath/$aufile");

	if(!$wavres || !$aures)
		notice("Could not delete all related files from disk. Please look in $fpath and delete all files related to this song", NULL, false);
	//notify($music_on_hold->objDelete());
	$res = $music_on_hold->objDelete();
	notice($res[1], NULL, $res[0]);
}

function listen_music_on_hold()
{
	global $path;
	$path .= "&method=music_on_hold";
	//message("Not yet implemented",$path);

	$setting = Model::selection("setting", array("param"=>"path"));
	if(!count($setting)) {
		errormess("Path to music on hold is not specified. It must be defined in the Settings section.",$path);
		return;
	}

	$filepath = $setting[0]->value . "/music_on_hold/";
	$music_on_hold_id = getparam("music_on_hold_id");
	$music_on_hold = new Music_On_Hold;
	$music_on_hold->music_on_hold_id = getparam("music_on_hold_id");
	$music_on_hold->select();

	$array = array(
					"file" => array("value"=>$music_on_hold->music_on_hold, "display"=>"fixed"),
					"description" => array("value"=>$music_on_hold->description, "display"=>"fixed")
			);
	editObject($music_on_hold, $array, "Playing music on hold file", 'no');
	$filepath .= $music_on_hold->music_on_hold;

	?>
	<center>
	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="450" height="40" id="home" align="center">
		<param name="movie" value='flash_movie.php?size=160&nostart=true&mp3=<?print $filepath;?>' />
		<param name="quality" value="high" />
		<embed src='flash_movie.php?size=160&nostart=true&mp3=<?print $filepath;?>' quality="high" width="450" height="40"  name="home" align="" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
	</object>
	</center>
	<?
}

function playlists()
{
	global $method, $action;
	$method = "playlists";
	$action = NULL;
	
	$playlists = Model::selection("playlist", NULL, "playlist");
	$fields = array("playlist", "in_use");

	tableOfObjects($playlists, $fields, "playlist", array("&method=edit_playlist"=>'<img src="images/edit.gif" title="Edit" alt="Edit"/>', "&method=delete_playlist"=>'<img src="images/delete.gif" title="Delete" alt="Delete"/>'), array("&method=add_playlist"=>"Create playlist"));
}

function edit_playlist($error = NULL)
{
	if($error)
		errormess($error,'no');
	
	$playlist = new Playlist;
	$playlist->playlist_id = getparam("playlist_id");
	$playlist->select();

	$fields = array(
					"playlist" => array("compulsory"=>true, "comment"=>"Name of this playlist. Must be unique."),
					"in_use" => array("comment"=>"Check if you wish this to be the playlist for music on hold. Selected playlist will be used for a groups music on hold if no playlist was associated to this group.")
				);

	$title = ($playlist->playlist_id) ? "Edit playlist" : "Create playlist";

	start_form();
	addHidden("database", array("playlist_id"=>$playlist->playlist_id));
	editObject($playlist,$fields,$title,"Save",true);
	end_form();
}

function edit_playlist_database()
{
	global $path;
	$path .= "&method=playlists";

	$playlist = new Playlist;
	$playlist->playlist_id = getparam("playlist_id");
	$params = form_params(array("playlist", "in_use"));

	$res = ($playlist->playlist_id) ? $playlist->edit($params) : $playlist->add($params);
	notice($res[1], "playlists", $res[0]);
}

function delete_playlist()
{
	ack_delete("playlist", getparam("playlist"), NULL, "playlist_id", getparam("playlist_id"));
}

function delete_playlist_database()
{
	global $path;
	$path .= "&method=playlists";

	$playlist = new Playlist;
	$playlist->playlist_id = getparam("playlist_id");
	if(!$playlist->playlist_id) {
		errormess("Don't have id. Can't delete this object.", $path);
		return;
	}
	//notify($playlist->objDelete(),$path);
	$res = $playlist->objDelete();
	notice($res[1], "playlists", $res[0]);
}

function playlist_items()
{
	global $actions;

	$playlist_name = getparam("playlist");
	$playlist = new PlayList;
	$playlist->playlist = $playlist_name;
	$playlist->select("playlist");
	if(!$playlist->playlist_id) {
		errormess("Invalid playlist name");
		return;
	}
	$actions["&method=remove_from_playlist"] = '<img src="images/remove_from_playlist.gif" alt="Remove from playlist" title="Remove from playlist"/>';	
	$music_on_hold = Model::selection("music_on_hold", NULL, "music_on_hold", NULL, NULL, NULL, array("other_table"=>"playlist_items", "column"=>"music_on_hold_id", "relation"=>"IN", "conditions"=>array("playlist_id"=>$playlist->playlist_id)));
	$fields = array("file"=>"music_on_hold", "description");
	tableOfObjects($music_on_hold, $fields, "playlist item", $actions, array("&method=insert_item&playlist_id=".$playlist->playlist_id."&playlist=".$playlist->playlist=>"Add item to playlist")); 
}

function insert_item()
{
	$playlist_id = getparam("playlist_id");
	$playlist = getparam("playlist");

	$music_on_hold = Model::selection("music_on_hold",NULL,"music_on_hold", NULL, NULL, NULL, array("other_table"=>"playlist_items", "column"=>"music_on_hold_id", "relation"=>"NOT IN", "conditions"=>array("playlist_id"=>$playlist_id)));
	$files = Model::objectsToArray($music_on_hold, array("music_on_hold_id"=>"file_id", "music_on_hold"=>"file"),true);

	start_form();
	addHidden("database", array("playlist_id"=>$playlist_id, "playlist"=>$playlist));
	editObject(NULL, array("file"=>array($files, "display"=>"select")), "Add item to playlist ".$playlist, "Add");
	end_form();
}

function insert_item_database()
{
	$playlist_id = getparam("playlist_id");
	if(!$playlist_id) {
		notice("Please select the playlist.", "playlists", false);
		return;
	}
	$playlist = getparam("playlist");
	$file = getparam("file");
	if(!$file || $file == "Not selected") {
		notice("Please select a file before pressing the 'Add' button.", "playlists", false);
		return;
	}

	$playlist_item = new Playlist_Item;
	$playlist_item->playlist_id = $playlist_id;
	$playlist_item->music_on_hold_id = getparam("file");
	if(!$playlist_item->playlist_id || !$playlist_item->music_on_hold_id) {
		//errormess("Could not add item to playlist: incomplete information");
		notice("Could not add item to playlist: incomplete information", "playlists", false);
		return;
	}
	//notify($playlist_item->insert(false));
	$res = $playlist_item->insert(false);
	notice($res[1], NULL, $res[0]);
}

function remove_from_playlist()
{
	$file = getparam("file");
	$music_on_hold_id = getparam("music_on_hold_id");
	$playlist_id = getparam("playlist_id");
	$playlist_item = Model::selection("playlist_item", array("music_on_hold_id"=>$music_on_hold_id, "playlist_id"=>$playlist_id));
	if(!count($playlist_item)) {
		errormess("Don't have item id");
		return;
	}
	ack_delete("playlist_item", $file, NULL, "playlist_item_id", $playlist_item[0]->playlist_item_id);
}

function remove_from_playlist_database()
{
	$playlist_item = new Playlist_Item;
	$playlist_item->playlist_item_id = getparam("playlist_item_id");
	if(!$playlist_item->playlist_item_id) {
		//errormess("Don't have item id in order to remove it.");
		notice("Don't have item id in order to remove it.", NULL, false);
		return;
	}
	//notify($playlist_item->objDelete());
	$res = $playlist_item->objDelete();
	notice($res[1],NULL,$res[0]);
}

?>