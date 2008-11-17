#! /usr/bin/php -q
<?

require_once ('lib.php');
require_once ('framework.php');
require_once ('lib_custom.php');

include_classes();
require_once ('set_debug.php');

Database::query("select * from unexisting_table");

?>