<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

//ACP Hooks
$plugins->add_hook("admin_forum_menu", "newsletter_admin_forum_menu");
$plugins->add_hook("admin_forum_action_handler", "newsletter_admin_forum_action_handler");
$plugins->add_hook("admin_forum_permissions", "newsletter_admin_forum_permissions");

function newsletter_info()
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
function newsletter_install()
{
	global $db;

	$col = $db->build_create_table_collation();
	$db->query("CREATE TABLE `".TABLE_PREFIX."newsletter` (
				*FOLGT*
	) ENGINE=MyISAM {$col}");
}

function newsletter_is_installed()
{
	global $db;
	return $db->table_exists("newsletter");
}

function newsletter_uninstall()
{
    global $db;
	$db->drop_table("newsletter");
} */

function newsletter_activate()
{
	require MYBB_ROOT."inc/adminfunctions_templates.php";
}

function newsletter_deactivate()
{
	require MYBB_ROOT."inc/adminfunctions_templates.php";
}


function newsletter_admin_forum_menu($sub_menu)
{
	global $lang;

	$lang->load("newsletter");

	$sub_menu[] = array("id" => "newsletter", "title" => $lang->newsletter, "link" => "index.php?module=forum-newsletter");

	return $sub_menu;
}

function newsletter_admin_forum_action_handler($actions)
{
	$actions['newsletter'] = array(
		"active" => "newsletter",
		"file" => "newsletter.php"
	);

	return $actions;
}

function newsletter_admin_forum_permissions($admin_permissions)
{
	global $lang;

	$lang->load("newsletter");

	$admin_permissions['newsletter'] = $lang->newsletter_permissions;

	return $admin_permissions;
}
?>