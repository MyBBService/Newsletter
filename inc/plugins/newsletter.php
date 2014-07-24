<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

global $cache;
if(!isset($pluginlist))
	$pluginlist = $cache->read("plugins");

//ACP Hooks
if(is_array($pluginlist['active']) && in_array("mybbservice", $pluginlist['active'])) {
	$plugins->add_hook("mybbservice_actions", "newsletter_mybbservice_actions");
	$plugins->add_hook("mybbservice_permission", "newsletter_admin_forum_permissions");
} else {
	$plugins->add_hook("admin_forum_menu", "newsletter_admin_forum_menu");
	$plugins->add_hook("admin_forum_action_handler", "newsletter_admin_forum_action_handler");
	$plugins->add_hook("admin_forum_permissions", "newsletter_admin_forum_permissions");
}

//UCP Hooks
$plugins->add_hook("usercp_options_end", "newsletter_ucp");
$plugins->add_hook("datahandler_user_update", "newsletter_ucp_handler");

//Global Hook
$plugins->add_hook("global_start", "newsletter_add_shutdown");

function newsletter_info()
{
	return array(
		"name"			=> "Newsletter",
		"description"	=> "Fügt ein einfaches Newsletter System hinzu",
		"website"		=> "http://mybbservice.de/",
		"author"		=> "MyBBService",
		"authorsite"	=> "http://mybbservice.de/",
		"version"		=> "1.1.1",
		"guid" 			=> "",
		"compatibility" => "*",
		"dlcid"			=> "21"
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
				`html` text NOT NULL,
				`plain` text NOT NULL,
				`override_receive` boolean NOT NULL default '0',
				`created` bigint(30) NOT NULL default '0',
				`sent` bigint(30) NOT NULL default '0',
	PRIMARY KEY (`id`) ) ENGINE=MyISAM {$col}");

	$db->query("CREATE TABLE `".TABLE_PREFIX."newsletter_mailqueue` (
				`mid` int(10) NOT NULL AUTO_INCREMENT,
				`mailto` varchar(200) NOT NULL default '',
				`mailfrom` varchar(200) NOT NULL default '0',
				`subject` varchar(100) NOT NULL default '',
				`html` text NOT NULL,
				`plain` text NOT NULL,
	PRIMARY KEY (`mid`) ) ENGINE=MyISAM {$col}");
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
	$db->drop_table("newsletter_mailqueue");
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


function newsletter_mybbservice_actions($actions)
{
	global $page, $lang, $info;
	$lang->load("newsletter");

	$actions['newsletter'] = array(
		"active" => "newsletter",
		"file" => "../forum/newsletter.php"
	);

	$sub_menu = array();
	$sub_menu['10'] = array("id" => "newsletter", "title" => $lang->newsletter, "link" => "index.php?module=mybbservice-newsletter");
	$sidebar = new SidebarItem($lang->newsletter);
	$sidebar->add_menu_items($sub_menu, $actions[$info]['active']);

	$page->sidebar .= $sidebar->get_markup();

	return $actions;
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

function newsletter_prepare_send($nid)
{
	global $db;

	$query = $db->simple_select("newsletter", "subject, html, plain, override_receive", "id='{$nid}'");
	$newsletter = $db->fetch_array($query);

	$db->update_query("newsletter", array("sent" => TIME_NOW), "id='{$nid}'");

	$where = "";
	if(!$newsletter['override_receive'])
		$where = "receive_newsletter='1'";
	$uquery = $db->simple_select("users", "email", $where);

	$queue_array = array(
		"mailfrom" => "",
		"subject" => $db->escape_string($newsletter['subject']),
		"html" => $db->escape_string($newsletter['html']),
		"plain" => $db->escape_string($newsletter['plain'])
	);
	while($user = $db->fetch_array($uquery)) {
		$queue_array['mailto'] = $user['email'];
		$db->insert_query("newsletter_mailqueue", $queue_array);
	}
}

function newsletter_send()
{
	global $db;

	$query = $db->simple_select("newsletter_mailqueue", "*", "", array("limit" => "10"));
	if($db->num_rows($query) > 0) {
		while($email = $db->fetch_array($query)) {
			$db->delete_query("newsletter_mailqueue", "mid='{$email['mid']}'");

			if($email['html'] != "")
				my_mail($email['mailto'], $email['subject'], $email['html'], $email['mailfrom'], "", "", false, "html", $email['plain']);
			else
				my_mail($email['mailto'], $email['subject'], $email['plain'], $email['mailfrom']);
		}
	}
}

function newsletter_add_shutdown()
{
	add_shutdown("newsletter_send");
}
?>