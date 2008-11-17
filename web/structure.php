<?

$struct = array();

$struct["admin_routes"] = array("manage","new_route");
$struct["admin_internal_routes"] = array("manage", "add_internal_route");
$struct["admin_registrations"] = array("manage", "add_registration");
$struct["admin_admins"] = array("manage", "add_admin");
$struct["admin_extensions"] = array("manage","groups", "add_extension", "add_range","add_group", "search", "import", "export");
$struct["admin_outbound"] = array("gateways", "dial_plan", "add_gateway", "add_dial_plan");
$struct["admin_auto_attendant"] = array("prompts", "keys", "scheduling", "wizard");
$struct["admin_settings"] = array("general"/*, "equipments"*/, "network");
$struct["admin_HOME"] = array("manage", "logs", "ongoing_calls");
$struct["admin_music_on_hold"] = array("music_on_hold", "playlists", "add_playlist");

?>