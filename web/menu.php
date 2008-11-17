<?
include ("structure.php");

$errorlogin = "Login invalid!";
global $module, $method, $support, $level, $do_not_load, $iframe;

function index_title()
{
	title();
}

function get_login_form()
{
	global $login, $link;
?>
		<link type="text/css" rel="stylesheet" href="index.css"/>
		<div class="login-div">
		<form action="index.php" method="post">
			<fieldset class="login" border="1">
				<legend class="login">Login</legend>
				<? 
					if ($login) 
						print $login;
					else 
						print "<p>&nbsp;</p>";	
				?>
				<p class="wellcome_to">Welcome to FreeSentral!</p>
				<p align="right"><label id="username">Username:&nbsp;</label> <input type="text" name="username" id="username" size="19"/></p>
				<p align="right"><label id="password">Password:&nbsp;</label> <input type="password" name="password" id="password" size="19" /></p>
				<p align="right"><input type="submit" value="Send" class="submit"/></p>
				<div align="center">
		<?
			$sigur = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'];
		    $s1 = $sigur ? "Cripted SSL" : "Uncripted";
		    $s2 = $sigur ? "deactivate" : "secure";
		    $l = $sigur ? "http://" : "https://";
		    $l .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		    print "<b>$s1</b> <a  class=\"signup\" href=\"$l\">$s2</a>";
		?>
				</div>
			</fieldset>
		</form>
		</div>
<?
}

function title()
{
	global $module;
	print "Tellfon: ".ucwords(str_replace("_"," ",$module));
}

function get_content()
{
	global $module,$dir,$support,$iframe;

	if($iframe != "true") {
	/*?>	<table class="container" cellspacing="0" cellpadding="0">
			<tr>
				<th valign="top">
					<table class="upperbanner"> 
						<tr>
							<td class="upperbanner">
								<div class="upperbanner">Welcome 
								<? 
									if(isset($_SESSION["real_user"]))
										print $_SESSION["real_user"]. ". You are currently logged in as ";
									print $_SESSION["user"];
								?>!&nbsp;&nbsp;<a class="uplink" href="index.php">Logout</a>&nbsp;&nbsp;
								<?
									if(isset($_SESSION["real_user"]))
										print '<a class="uplink" href="main.php?method=stop_impersonate">Return&nbsp;to&nbsp;your&nbsp;account</a>';
								?>
								</div></td>
							<td><div class="photo"> <img src="images/logo.jpg"/></td>
						</tr>
					</table>
					<table class="status">
						<tr>
							<td class="status" colspan="2">&nbsp;&nbsp;</td>
						</tr>
					</table>
				</th>
			</tr>
		</table>*/?>
	<table class="container" cellspacing="0" cellpadding="0">
		<tr>
			<td class="holdlogo">
				 <img src="images/logo2.jpg"/>
			</td>
		</tr>
		<tr>
			<td class="upperbanner">
								<div class="upperbanner">Welcome
								<font class="bluefont">
								<? 
									if(isset($_SESSION["real_user"]))
										print $_SESSION["real_user"]. ". You are currently logged in as ";
									print $_SESSION["user"];
								?>
								</font>
								!&nbsp;&nbsp;<a class="uplink" href="index.php">Logout</a>&nbsp;&nbsp;
								<?
									if(isset($_SESSION["real_user"]))
										print '<a class="uplink" href="main.php?method=stop_impersonate">Return&nbsp;to&nbsp;your&nbsp;account</a>';
								?>
								</div>
			</td>
		</tr>
	</table>

	<div class="position"> <br/> </div>
	<table class="firstmenu" cellpadding="0" cellspacing="0">
		<tr>
			<? menu(); ?>
		</tr>
	</table>
	<? submenu();
	}
	?>
	<table class="holdcontent" cellspacing="0" cellpadding="0">
		<tr>
			<td class="holdcontent">
	<?
	if($module) {
			if(is_file("modules/$dir/$module.php"))
				include("modules/$dir/$module.php"); 
	} ?>
			</td>
		</tr>
	</table>
<?
}

function menu()
{
	global $level,$support;
	if ($support)
		files('customer');
	else
    	files($level);
}

function files($level)
{
	global $module;
	$names = array();
	if ($handle = opendir("modules/$level/"))
	{
		while (false !== ($file = readdir($handle)))
		{
			//if ((trim($file,"~") === $file) && (stripos($file,".swp") === false))
			if (substr($file,-4) != ".php")
				continue;
			if (stripos($file,".php") === false)
				continue;
			if($file == "HOME.php")
				continue;
			$names[] = ereg_replace('.php','',$file);
		}
		closedir($handle);
	}
	sort($names);
	if(is_file("modules/$level/HOME.php"))
		$names = array_merge(array("HOME"), $names);

	$i = 0;
	foreach($names as $name)
	{
		if(dont_load($name) || $name == "verify_settings")
			continue;

		if ($name == $module) {
			if($i)
				print "<td class=\"separator\">&nbsp;</td>";
			print "<td class=\"firstmenu_selected\"><a class=\"linkselected\" href=\"main.php?module=$name\">";
		} else {
			if($i)
				print "<td class=\"separator\">&nbsp;</td>";
			print "<td class=\"firstmenu\"><a class=\"link\" href=\"main.php?module=$name\">";
		}
		print '<div>'.str_replace(" ","&nbsp;",ucwords(str_replace("_"," ",$name))).'</div>';
		print "</a></td>";
		$i++;
	}
	print("<td class=\"fillspace\">&nbsp;</td>");	
}

function dont_load($name)
{
	global $do_not_load;

	if (!is_array($do_not_load))
		return false;

	for($i=0; $i<count($do_not_load); $i++) {
		if ($do_not_load[$i] == $name)
			return true;
	}

	return false;
}

function submenu()
{
	global $module,$dir,$struct,$method,$support;
	if(!isset($struct[$dir.'_'.$module]))
		return;
	$i = 0;
	$max = 9;
	print '<table class="secondmenu"> 
			<tr>';
    foreach($struct["$dir"."_".$module] as $option) {
		if($i % $max == 0 && $i){
			print("<td class=\"fillfree\">&nbsp;</td>");
			print '</tr><tr>';
		}
		if($method == $option)
			print("<td class=\"option\"><a class=\"secondlinkselected\" href=\"main.php?module=$module&method=$option\">");//.strtoupper($option)."</a></td>");
		else
			print("<td class=\"option\"><a class=\"secondlink\" href=\"main.php?module=$module&method=$option\"><div>");//.strtoupper($option)."</a></td>");
		print str_replace(" ","&nbsp;",ucwords(str_replace("_"," ",$option)));
		print("</div></a></td><td class=\"option_separator\"><div></div></td>");
		$i++; 
	}
	print("<td class=\"fillfree\" colspan=\"$max\">&nbsp;</td>");
	print "</tr></table>";
}
/*
function status()
{
    global $dir,$module,$struct,$method,$align;
    if(!(array_key_exists("$dir"."_"."$module",$struct))) {
        print(strtoupper($module));
        return;
    }
    print(ucwords($module).":");
    if(!$method || $method == ''){
        $name = explode("-",($struct["$dir"."_"."$module"][0]));
        if (!$align)
            $align = $name[0];
        $method = $name[1];
    }else{
        $name = $method;
        foreach($struct["$dir"."_"."$module"] as $option) {
            $opt = explode("-",$option);
            if($opt[1] == $method) {
                $align = $opt[0];
                break;
            }
        }
    }
    $num = explode("_",$method);
    for($i=0; $i<count($num); $i++)
        print(ucwords($num[$i])." ");
}*/
?>
