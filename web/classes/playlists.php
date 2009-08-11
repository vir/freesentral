<?
/**
 * playlists.php
 * This file is part of the FreeSentral Project http://freesentral.com
 *
 * FreeSentral - is a Web Graphical User Interface for easy configuration of the Yate PBX software
 * Copyright (C) 2008-2009 Null Team
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301, USA.
 */
?>
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