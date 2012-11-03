<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

//ACP Hooks
$plugins->add_hook("admin_forum_menu", "newsletter_admin_forum_menu");
$plugins->add_hook("admin_forum_action_handler", "newsletter_admin_forum_action_handler");
$plugins->add_hook("admin_forum_permissions", "newsletter_admin_forum_permissions");

//UCP Hooks
$plugins->add_hook("usercp_options_end", "newsletter_ucp");
$plugins->add_hook("datahandler_user_update", "newsletter_ucp_handler");

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


function newsletter_install()
{
	global $db;

//	newsletter_uninstall();

	$template="</tr>
<tr>
<td valign=\"top\" width=\"1\"><input type=\"checkbox\" class=\"checkbox\" name=\"receive_newsletter\" id=\"receive_newsletter\" value=\"1\" {\$receive_newsletter_check} /></td>
<td><span class=\"smalltext\"><label for=\"receive_newsletter\">{\$lang->receive_newsletter}</label></span></td>";
	$templatearray = array(
	        "title" => "usercp_options_newsletter",
	        "template" => $template,
	        "sid" => "-2",
	        );
    $db->insert_query("templates", $templatearray);

	$db->add_column('users', 'receive_newsletter', "int(1) NOT NULL default '0'");

	$col = $db->build_create_table_collation();
	$db->query("CREATE TABLE `".TABLE_PREFIX."newsletter` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`subject` varchar(100) NOT NULL default '',
				`html` text NOT NULL default '',
				`plain` text NOT NULL default '',
				`override_receive` boolean NOT NULL default '0',
				`created` bigint(30) NOT NULL default '0',
				`sent` bigint(30) NOT NULL default '0',
	PRIMARY KEY (`id`) ) ENGINE=MyISAM {$col}");
}

function newsletter_is_installed()
{
	global $db;
	return $db->table_exists("newsletter");
}

function newsletter_uninstall()
{
    global $db;
    $db->delete_query("templates", "title='usercp_options_newsletter'");
	$db->drop_column('users', 'receive_newsletter');
	$db->drop_table("newsletter");
}

function newsletter_activate()
{
	require MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("usercp_options", "#".preg_quote('{$lang->show_redirect}</label></span></td>')."#i", '{$lang->show_redirect}</label></span></td>{$receive_newsletter}');
}

function newsletter_deactivate()
{
	require MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("usercp_options", "#".preg_quote('{$receive_newsletter}')."#i", "", 0);
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

function newsletter_ucp()
{
	global $mybb, $receive_newsletter, $templates, $user, $lang;
	$lang->load("newsletter");

	if($user['receive_newsletter'] == 1)
	{
		$receive_newsletter_check = "checked=\"checked\"";
	}
	else
	{
		$receive_newsletter_check = "";
	}
	eval("\$receive_newsletter = \"".$templates->get("usercp_options_newsletter")."\";");
}

function newsletter_ucp_handler($user)
{
	global $mybb;
	$user->user_update_data['receive_newsletter'] = $mybb->input['receive_newsletter'];
	return $user;
}
?>