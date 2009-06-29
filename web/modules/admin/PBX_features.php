<div class="content wide">
<?
global $module, $method, $path, $action, $page, $target_path, $iframe;

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

$call();

function PBX_features()
{
	all();
}

function digits()
{
?>
*0 - put your party on hold
<br/><br/>
*1, then the number you want to transfer to, and then * - Unassisted transfer
<br/><br/>
*2, then phone number, then * - This operation will put your party and hold and you will be connected to the inserted number(third party)
<br/><br/>
*3 - during a call transfers you and your pear in to a conference. 
<br/><br/>
*4 - This operation will connect the party you put on hold to your current party.
<br/><br/>
*6 - transfer yourself and your new pear to an existing conference. 
<br/><br/>
*7 - To return a party on hold.
<br/><br/>
*9 - return your dial tone
<br/><br/>
** and then insert the extension that is receiving a call - pickup that call(works only if your and that extension are in the same group)
<br/><br/>
# - after a certain number of digits will delete the entered digits.
<br/><br/>
*** - disable the pbx features. You need to use this feature when you wish to use an IVR(interactive voice response). Ex: Auto Attendant for a certain company your called to.
<br/><br/>
### - enable pbx features
<?
}

function all()
{
	?>
<div class="features_notice">
PBX features:
<ul>
<li class="list_features"><a class="list_features" href="#call_tranfer">Call Transfer</a></li>
<li class="list_features"><a class="list_features" href="#call_hold">Call Hold</a></li>
<li class="list_features"><a class="list_features" href="#conference">Conference</a></li>
<li class="list_features"><a class="list_features" href="#call_hunt">Call Hunt</a></li>
<li class="list_features"><a class="list_features" href="#call_pick_up">Call Pick Up</a></li>
<li class="flush"><a class="list_features" href="#call_hunt">Flush</a></li>
<li class="passthrough"><a class="list_features" href="#call_hunt">Pass Throught</a></li>
<li class="retake"><a class="list_features" href="#call_hunt">Retake PBX features</a></li>
</ul>
</div>
	<?
	call_transfer();
	call_hold();
	conference();
	call_hunt();
	call_pick_up();
?>
<a name="flush" class="features">Flush</a>
<div class="features_notice">
Press # after a certain number of digits in order to delete the digits yor entered. 
</div>
<a name="passthrough" class="features">Pass Throught</a>
<div class="features_notice">
Press *** do disable the pbx features. You need to use this feature when you wish to use an IVR(interactive voice response). Ex: Auto Attendant for a certain company your called to. 
</div>
<a name="retake" class="features">Retake PBX features</a>
<div class="features_notice">
Press ### after pressing ***(do disable pbx features) to enable your pbx features during a call.
</div>
<?
}

function call_transfer()
{
	global $module;
?>
<a name="call_transfer" class="features">Call Transfer</a>
<div class="features_notice">
There are two types of transfers:
<ul class="features">
<li class="features">Unassisted transfer</li>

You transfer your party to another phone number by pressing *1, then the number you want to transfer to, and then *. After performing transfer your call was hanged up.

<li class="features">Assisted transfer</li>

You transfer your party to another number after checking with the other party. 
<br/><br/>
This type of transfer is done in two steps: First press *2, then phone number, then *. This operation will put your party and hold and you will be connected to the inserted number(third party). After third party agrees to receive the call you press *4. This operation will connect the party you put on hold and the third party. 
</ul>
<br/><br/>
<center><a class="llink" href="main.php?module=<?print $module;?>&method=call_hold">Next >></a></center>
</div>
<?
}

function call_hold()
{
	global $module;
?>
<a name="call_hold" class="features">Call Hold</a>
<div class="features_notice">
In order to put your party on hold you need to press *0. 
<br/><br/>
To return a party on hold you need to press *7. 
<br/><br/>
After putting your party on hold you receive dial tone in order to perform another operation. 
<br/><br/>
<center><a class="llink" href="main.php?module=<?print $module;?>&method=call_transfer"><< Previous</a> | <a class="llink" href="main.php?module=<?print $module;?>&method=conference">Next >></a></center>
</div>
<?
}

function conference()
{
	global $module;
?>
<a name="conference" class="features">Conference</a>
<div class="features_notice">
There are 2 types of conferences.
<ul class="features">
<li class="features">Use the active conference rooms</li>
Just dial the number associated to the conference room. Look in the 'Conferences' tab to see active conferences, their number and current number of participants.
<li class="features">Transform current call to conference </li>
During a call press *3 in order to transfer you and your pear in to a conference. If you want to add another person to the conference press *9(this will return your dial tone) and you will be able to make a new call. Then press *6 to transfer yourself and your new pear to the existing conference. 
</ul>
<br/><br/>
<center><a class="llink" href="main.php?module=<?print $module;?>&method=call_hold"><< Previous</a> | <a class="llink" href="main.php?module=<?print $module;?>&method=call_hunt">Next >></a></center>
</div>
<?
}

function call_hunt()
{
	global $module;
?>
<a name="call_hunt" class="features">Call Hunt</a>
<div class="features_notice">
This feature is enabled by the use of groups. When a call comes for a certain group all the users in that group are called. 
<br/><br/>
<center><a class="llink" href="main.php?module=<?print $module;?>&method=conference"><< Previous</a> | <a class="llink" href="main.php?module=<?print $module;?>&method=call_pick_up">Next >></a></center>
</div>
<?
}

function call_pick_up()
{
	global $module;
?>
<a name="call_pick_up" class="features">Call Pick Up</a>
<div class="features_notice">
Call pick up is allowed between members of the same group.
<br/><br/>
If an extension in your group is called you can pickup that call pressing ** and then insert the extension that received the call. 
<br/><br/>
<center><a class="llink" href="main.php?module=<?print $module;?>&method=call_hunt"><< Previous</a> | <a class="llink" href="main.php?module=<?print $module;?>&method=flush_digits">Next >></a></center>
</div>
<?
}

function flush_digits()
{
	global $module;
?>
<a name="flush" class="features">Flush</a>
<div class="features_notice">
Press # after a certain number of digits in order to delete the digits yor entered.
<br/><br/> 
<center><a class="llink" href="main.php?module=<?print $module;?>&method=call_pick_up"><< Previous</a> | <a class="llink" href="main.php?module=<?print $module;?>&method=passthrought">Next >></a></center>
</div>
<?
}

function passthrought()
{
	global $module;
?>
<a name="passthrough" class="features">Pass Throught</a>
<div class="features_notice">
Press *** do disable the pbx features. You need to use this feature when you wish to use an IVR(interactive voice response). Ex: Auto Attendant for a certain company your called to. 
<br/><br/> 
<center><a class="llink" href="main.php?module=<?print $module;?>&method=flush_digits"><< Previous</a> | <a class="llink" href="main.php?module=<?print $module;?>&method=retake">Next >></a></center>
</div>
<?
}

function retake()
{
	global $module;
?>
<a name="retake" class="features">Retake PBX features</a>
<div class="features_notice">
Press ### after pressing ***(do disable pbx features) to enable your pbx features during a call.
<br/><br/> 
<center><a class="llink" href="main.php?module=<?print $module;?>&method=passthrought"><< Previous</a></center>
</div>
<?
}

?>
</div>