<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

//ACP Hooks
$plugins->add_hook("admin_forum_menu", "nl_admin_forum_menu");
$plugins->add_hook("admin_forum_action_handler", "nl_admin_forum_action_handler");
$plugins->add_hook("admin_forum_permissions", "nl_admin_forum_permissions");

function nl_info()
{
	return array(
		"name"			=> "Newsletter",
		"description"	=> "Fügt ein einfaches Newsletter System hinzu",
		"website"		=> "http://mybbservice.de/",
		"author"		=> "MyBBService",
		"authorsite"	=> "http://mybbservice.de/",
		"version"		=> "1.0 Beta",
		"guid" 			=> "",
		"compatibility" => "16*"
	);
}

/*
function nl_install()
{
	global $db;

	$col = $db->build_create_table_collation();
	$db->query("CREATE TABLE `".TABLE_PREFIX."newsletter` (
				*FOLGT*
	) ENGINE=MyISAM {$col}");
}

function nl_is_installed()
{
	global $db;
	return $db->table_exists("newsletter");
}

function nl_uninstall()
{
    global $db;
	$db->drop_table("newsletter");
} */

function nl_activate()
{
	require MYBB_ROOT."inc/adminfunctions_templates.php";
}

function nl_deactivate()
{
	require MYBB_ROOT."inc/adminfunctions_templates.php";
}


function nl_admin_forum_menu($sub_menu)
{
	global $lang;

//	$lang->load("newsletter");

	$sub_menu[] = array("id" => "nl", "title" => "NL", "link" => "index.php?module=forum-nl");

	return $sub_menu;
}

function nl_admin_forum_action_handler($actions)
{
	$actions['nl'] = array(
		"active" => "nl",
		"file" => "nl.php"
	);

	return $actions;
}

function nl_admin_forum_permissions($admin_permissions)
{
	global $lang;

//	$lang->load("newsletter");

	$admin_permissions['nl'] = "NL";

	return $admin_permissions;
}
?>