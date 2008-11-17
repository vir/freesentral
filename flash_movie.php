<?
session_start();

require_once("config.php");
require_once("lib.php");
require_once("framework.php");
require_once("lib_custom.php");
include_classes();

$mp3 = getparam("mp3");

if($_SESSION["level"] != "admin") {
	$dir = vmInitMessageDir($_SESSION["username"]);
	$filepath = "$dir/$mp3";
}else
	$filepath = $mp3;

////////////until here
//session_start();
ming_useswfversion(8);
//creating the new flash movie
$m = new SWFMovie();
//$mp3 = $_SESSION["mp3"];
$mp3 = $filepath;

$len = $m->streamMp3(fopen($mp3, "r"));
$m->setFrames($len);
$m->setRate(12.0);
$m->setDimension(3650,340);
$m->setbackground(238,238,238);
//$m->setbackground(0,0,0);
if (isset($_GET['nostart'])) {
    // this will also prevent looping since the clip wraps around here
    $m->add(new SWFAction("stop();"));
}

$color = isset($_GET['color']) ? $_GET['color'] : "";
if ($color == "") $color = array("r"=>59,"g"=>126,"b"=>178);

$size = isset($_GET['size']) ? (0 + $_GET['size']) : 0;
if ($size < 4) $size = 20;


/* NOTE!!! Wherever you see -offset, it was added by Monica and works nicely only when size=110, width=500 height=78 */

//building the stop button

$offset = ceil($size/3);
$stop=new SWFShape(); 
$stop->setRightFill($color['r'],$color['g'],$color['b']); 

$stop->movePen(0,$size-$offset);
$stop->drawLine($size,0);  
$stop->drawLine(0,$size); 
$stop->drawLine(-$size,0); 
$stop->drawLine(0,-$size); 

$stopbutton = new SWFButton();
$stopbutton->addShape($stop, SWFBUTTON_HIT | SWFBUTTON_UP | SWFBUTTON_DOWN | SWFBUTTON_OVER);

$stopbutton->addAction(new SWFAction('stop();'),SWFBUTTON_MOUSEDOWN);
$button = $m->add($stopbutton);
$button->setName("stopbutton");

// building the play button
$play_location = array("width"=>($size*1.5),"height"=>$size);

$play=new SWFShape(); 
$play->setRightFill($color['r'],$color['g'],$color['b']); 
$play->movePen($play_location['width'],$play_location['height']-$offset);
$play->drawLine($size+$size/4,$size/2);  
$play->drawLine(-($size+$size/4),$size/2);  
$play->drawCurveTo(($play_location['width']+$size*0.2),($play_location['height']+$size*0.5)-$offset,($play_location['width']),($play_location['height']-$offset));

$playbutton = new SWFButton();
$playbutton->addShape($play, SWFBUTTON_HIT | SWFBUTTON_UP | SWFBUTTON_DOWN | SWFBUTTON_OVER);

$playbutton->addAction(new SWFAction('play();'),SWFBUTTON_MOUSEDOWN);
$button = $m->add($playbutton);
$button->setName("playbutton");

//building prompt
$sizeprompt = $size*19;
$prompt=new SWFShape(); 
$prompt->setRightFill($color['r'],$color['g'],$color['b']); 
$prompt->movePen(($play_location['width']+$size*2),($play_location['height']+$size/2)-$offset);
$prompt->drawLine($sizeprompt,0);  
$prompt->drawLine(0,20);  
$prompt->drawLine(-$sizeprompt,0);  
$prompt->drawLine(0,-20);
$m->add($prompt);

//build cursor
$cursor=new SWFShape(); 
$cursor->setRightFill($color['r'],$color['g'],$color['b']); 
$cursor->movePen($play_location['width']+$size*2,$play_location['height']-$offset);
$cursor->drawLine(0,$size);  
$cursor->drawLine(50,0);
$cursor->drawLine(0,-$size);  
$cursor->drawLine(-50,0);

$mc = new SWFSprite();
$cursors = $mc->add($cursor);
$cursors->setName("cursors");
$mc->nextFrame();
$slider = $m->add($mc);

$slider->addAction(new SWFAction("
	// don't follow the mouse at start
	dragging = false;
"),SWFACTION_ONLOAD);
$slider->addAction(new SWFAction("
	if (dragging) {
		x = _root._xmouse + xOffset;
		if (x < 0) x = 0;
		if (x > $sizeprompt) x = $sizeprompt;
		this._x = x;
	} else {
		this._x = Math.floor($sizeprompt * (_root._currentframe - 1) / $len);
	}
	
"),SWFACTION_ENTERFRAME);

$a = new SWFAction("
	if (this.hitTest(_root._xmouse,_root._ymouse)) {
		// follow the mouse from now on
		dragging = true;
		// get the object to mouse offset
    		xOffset = this._x - _root._xmouse;
	}
");
$slider->addAction($a, SWFACTION_MOUSEDOWN);
        
$a = new SWFAction("
	if (dragging) {
		// don't follow the mouse any longer
		dragging = false;
		// and jump audio to the latest cursor position
                seekTo = this._x * $len / $sizeprompt;
                _root.gotoAndPlay(1 + Math.floor(seekTo));
	}
");
$slider->addAction($a, SWFACTION_MOUSEUP);


header('Content-type: application/x-shockwave-flash');
header('Pragma: no-cache');
header('Cache-control: max-age=0');
$m->output();
?>
