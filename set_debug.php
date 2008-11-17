<?

session_start();

if(isset($_SESSION["error_reporting"]))
	ini_set("error_reporting",$_SESSION["error_reporting"]);

if(isset($_SESSION["display_errors"]))
	ini_set("display_errors",true);
else
	ini_set("display_errors",false);

if(isset($_SESSION["log_errors"]))
	ini_set("log_errors",$_SESSION["log_errors"]);
else
	ini_set("log_errors",false);

if(isset($_SESSION["error_log"]))
	ini_set("error_log", $_SESSION["error_log"]);

?>