<?

function getdirection($caller, $called)
{
	if($caller == $_SESSION["user"])
		print '<img src="images/outgoing.png">';
	else
		print '<img src="images/incoming.png">';
}

function show_status($location)
{
	return ($location) ? 'online' : 'offline';
}

function detect_busy($inuse_count, $location)
{
	if(!$location)
		//return 'nonavailable';
		return '<img src="images/offline.gif"/ title="Offline" alt="Offline">';

	return ($inuse_count) ? '<img src="images/busy.gif"/ title="Busy" alt="Busy">' : '<img src="images/online.gif" title="Online" alt="Online"/>';
}

function get_formats($form_identifier = '')
{
	$cods = array("alaw"=>getparam($form_identifier."alaw"), "mulaw"=>getparam($form_identifier."mulaw"), "gsm"=>getparam($form_identifier."gsm"), "ilbc"=>getparam($form_identifier."ilbc"), "g729"=>getparam($form_identifier."g729"), "g723"=>getparam($form_identifier."g723"));
	$formats = NULL;
	$first = true;
	foreach($cods as $key => $value) {
		if($value == "on") {
			if($first) {
				$formats .= $key;
				$first = false;
			}else
				$formats .= ','.$key;
		}
	}
	return $formats;
}

function include_formats($formats,$form_identifier)
{
	$formats = explode(',',$formats);
	?>
	<input type="checkbox" name="<?print $form_identifier;?>alaw" <? if (in_array("alaw",$formats)) print "CHECKED";?>>alaw
	<input type="checkbox" name="<?print $form_identifier;?>mulaw" <? if (in_array("mulaw",$formats)) print "CHECKED";?>>mulaw
	<input type="checkbox" name="<?print $form_identifier;?>gsm" <? if (in_array("gsm",$formats)) print "CHECKED";?>>gsm
	<input type="checkbox" name="<?print $form_identifier;?>g729"<? if (in_array("g729",$formats)) print "CHECKED";?>>g729
	<input type="checkbox" name="<?print $form_identifier;?>g723"<? if (in_array("g723",$formats)) print "CHECKED";?>>g723
	<input type="checkbox" name="<?print $form_identifier;?>ilbc"<? if (in_array("ilbc",$formats)) print "CHECKED";?>>ilbc
	<?
}

function get_default_destination($extension, $group)
{
	if($extension)
		return $extension;
	if($group)
		return $group;
	return NULL;
}

function vmGetMessageStats($dir,&$total,&$unread)
{
	$o = 0;
	$n = 0;
	if (is_dir($dir) && ($d = @opendir($dir))) 
	{
		while (($f = readdir($d)) !== false) 
		{
	    	if (substr($f,0,4) == "nvm-")
				$n++;
			elseif (substr($f,0,3) == "vm-")
				$o++;
		}
		closedir($d);
	}
	$total = $n + $o;
	$unread = $n;
}

function vmGetMessageFiles($dir,&$files)
{
	if (is_dir($dir) && ($d = @opendir($dir))) 
	{
		$nf = array();
		$of = array();
		while (($f = readdir($d)) !== false) 
		{
			if (substr($f,0,4) == "nvm-")
				$nf[] = $f;
			elseif (substr($f,0,3) == "vm-")
				$of[] = $f;
		}
		closedir($d);
		$files = array_merge($nf,$of);
		return true;
	}
	return false;
}

function vmSetMessageRead($dir,&$file)
{
	if (is_dir($dir) && is_file("$dir/$file")) 
	{
		if (substr($file,0,4) != "nvm-")
			return false;
		$newname = substr($file,1);
		if (rename("$dir/$file","$dir/$newname")) 
		{
			$file = $newname;
			return true;
		}
	}
	return false;
}

function getCustomVoicemailDir($called)
{
    global $target_path;

    $last = $called[strlen($called)-1];

    $dir = "$target_path/$last";
    if (!is_dir($dir))
        mkdir($dir,0777);

	if(strlen($called) >=2) {
    	$alast = $called[strlen($called)-2];

    	$dir = "$target_path/$last/$alast/";
    	if (!is_dir($dir))
        	mkdir($dir,0777);
		$dir = "$target_path/$last/$alast/$called";
	}else
		$dir = "$target_path/$last/$called";

	if (!is_dir($dir))
		mkdir($dir,0777);
    return $dir;
}

function vmInitMessageDir($mailbox)
{
	return getCustomVoicemailDir($mailbox);
}

function format_files($files,$limit =20)
{
	$array = array();
	$index = 0;

	foreach($files as $file) 
	{
		$file_ = explode('.',$file);
		$file_[count($file_)-1] = NULL;
		$file_ =  implode('.',$file_);
		$file_ = rtrim($file_,'.');
		$file_info = explode("-",$file_);
		if($file_info[0] == "vm")	
			$heard = 'yes';
		else
			$heard = 'no';
		$array[$index] = array("heard"=>$heard, "date"=>$file_info[1], "time"=>$file_info[2], "from"=>$file_info[3], "file"=>$file, "id"=>str_replace(".","_",$file));
		$index++;
		if($index == $limit)
			break;
	}
	$ordered = false;
	$aux = array();
	while(!$ordered) 
	{
		$ordered = true;
		for($i=1;$i<count($array);$i++) 
		{
			if($array[$i]["date"]>$array[$i-1]["date"] || ($array[$i]["date"]==$array[$i-1]["date"] && $array[$i]["time"]>$array[$i-1]["time"])) 
			{
				$aux = $array[$i];
				$array[$i] = $array[$i-1];
				$array[$i-1] = $aux;
				$ordered = false;
			}
		}
	}
	return $array;
}

function voicemail($limit = NULL)
{
	$extension = $_SESSION["user"];
	$dir = vmInitMessageDir($extension);
	$total = NULL;
	$unread = NULL;

	vmGetMessageStats($dir,$total,$unread);
	print "<font class=\"voicemail_notice\">You have $unread unheard voicemails out of $total messages.</font><br/><br/>";
	$files = NULL;
	vmGetMessageFiles($dir,$files);

	$messages = format_files($files,$limit);
	$formats = array("date","time","from");

	start_form();
	addHidden("database");
	table($messages, $formats, "voicemail message", "id", array("&method=listen_voicemail"=>'<img src="images/listen.gif" alt="listen" title="Listen"/>', "&method=delete_voicemail_message"=>'<img src="images/delete.gif" alt="delete" title="Delete"/>'), NULL, NULL, true, NULL, array("unread"=>array("heard"=>"no")));
	if(count($messages))
	print '<table class="content"><tr><td class="content allleft"><input type="submit" value="DELETE"/></td></tr></table>';
	end_form();
}

function delete_voicemail_message()
{
	$id = getparam("id");
	if(!$id)
	{
		errormess("Don't know which file to delete.");
		return;
	}
	$extension = $_SESSION["user"];
	$dir = vmInitMessageDir($extension);
	$files = NULL;
	vmGetMessageFiles($dir,$files);
	$messages = format_files($files);

	for($i=0 ;$i<count($messages);$i++)
	{
		if($messages[$i]["id"] == $id){
			unlink("$dir/".$messages[$i]["file"]);
			voicemail();
			return;
		}
	}
	errornote("No file was selected for deletion.");
	voicemail();
}

function listen_voicemail()
{
	$file = getparam("file");
	$id = getparam("id");

	$extension = $_SESSION["user"];
	$dir = vmInitMessageDir($extension);

	if(substr($file,0,1) == "n") {
		rename("$dir/$file", "$dir/".substr($file,1,strlen($file)));
		$filepath = "$dir/".substr($file,1,strlen($file));
		$file = substr($file,1,strlen($file));
	}else
		$filepath = "$dir/$file";

	$message = format_files(array($file));
	$message = $message[0];

	$fields = array(
					"from"=>array("value"=>$message["from"], "display"=>"fixed"),
					"date"=>array("value"=>$message["date"], "display"=>"fixed"),
					"time"=>array("value"=>$message["time"], "display"=>"fixed")
				);

	$mp3_file = str_replace(".slin", ".mp3", $filepath);

	if(!is_file($mp3_file))
	{
		errormess("Missing file.");
		return;
	}

	editObject(NULL,$fields, "Playing voicemail message","no");
?>
	<center>
	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="450" height="40" id="home" align="center">
		<param name="movie" value='flash_movie.php?size=160&nostart=true&mp3=<?print $mp3_file;?>' />
		<param name="quality" value="high" />
		<embed src='flash_movie.php?size=160&nostart=true&mp3=<?print $mp3_file;?>' quality="high" width="450" height="40"  name="home" align="" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
	</object>
	</center>
<?
}

function voicemail_database()
{
	$extension = $_SESSION["user"];
	$dir = vmInitMessageDir($extension);
	$files = NULL;
	vmGetMessageFiles($dir,$files);

	$messages = format_files($files);

	for($i=0; $i<count($messages); $i++)
		if(getparam("check_".$messages[$i]["id"]) == "on")
			unlink("$dir/".$messages[$i]["file"]);

	voicemail();
}

function wiz_period($value, $name)
{
	$start = 0;
	$end = 0;

	print 'From <select name="start_'.$name.'">';
	print '<option>Not selected</option>';
	for($i=1; $i<25; $i++) {
		print '<option';
		if($start == $i)
			print " SELECTED";
		print '>'.$i.'</option>';
	}
	print '</select>';
	print '&nbsp;&nbsp;To <select name="end_'.$name.'">';
	print '<option>Not selected</option>';
	for($i=1; $i<25; $i++) {
		print '<option';
		if($end == $i)
			print " SELECTED";
		print '>'.$i.'</option>';
	}
	print '</select>';	
}


function days_of_week($name, $value=NULL)
{
	$days = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
	for($i=0; $i<count($days); $i++) {
		print '<input type="checkbox" name="'.$name.strtolower($days[$i]).'">'.$days[$i] . "&nbsp;&nbsp;";
		if(($i+1)%3 == 0)
			print '<br/>';
	}
}
?>