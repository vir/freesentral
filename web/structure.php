<?

$struct = array();

$struct["admin_routes"] = array("manage","new_route");
$struct["admin_internal_routes"] = array("manage", "add_internal_route");
$struct["admin_registrations"] = array("manage", "add_registration");
$struct["admin_admins"] = array("manage", "add_admin");
$struct["admin_extensions"] = array("manage","groups", "add_extension", "add_range","add_group", "search", "import", "export");
$struct["admin_outbound"] = array("gateways", "dial_plan", "add_gateway", "add_dial_plan");
$struct["admin_auto_attendant"] = array("prompts", "keys", "scheduling", "wizard");
$struct["admin_settings"] = array("general"/*, "equipments"*/, "network", "address_book", "admins");
$struct["admin_HOME"] = array("manage", "logs", "active_calls", "call_logs");
$struct["admin_music_on_hold"] = array("music_on_hold", "playlists", "add_playlist");
$struct["admin_dids"] = array("manage", "conferences", "add_did", "add_conference");
$struct["admin_PBX_features"] = array("digits", "call_transfer", "call_hold", "conference", "call_hunt", "call_pick_up", "flush_digits", "passthrought", "retake");
$struct["extension_PBX_features"] = array("digits", "call_transfer", "call_hold", "conference", "call_hunt", "call_pick_up", "flush_digits", "passthrought", "retake");

// options to be disabled
$block["admin_settings"] = array("network"); 

?>