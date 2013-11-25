<?php
if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

if(function_exists("mybbservice_info"))
    define(MODULE, "mybbservice-newsletter");
else
    define(MODULE, "forum-newsletter");

$page->add_breadcrumb_item($lang->newsletter, "index.php?module=".MODULE);

if($mybb->input['action'] == "abos") {
	$page->output_header($lang->abos);
	generate_tabs("abos");

	$table = new Table;

	$query = $db->simple_select("users", "*", "receive_newsletter='1'", array("order_by"=>"username", "order_dir"=>"DESC"));
	if($db->num_rows($query) > 0)
	{
		$ucount = 0;
		while($user = $db->fetch_array($query)) {
			$ucount++;

			$table->construct_cell($user['username']);

			if($ucount == 5) {
				$ucount = 0;
				$table->construct_row();
			}
		}
		if($ucount != 0) {
			$table->construct_row();
		}
	} else {
		$table->construct_cell($lang->no_abos, array('class' => 'align_center', 'colspan' => 5));
		$table->construct_row();
	}
	$table->output($lang->sprintf($lang->abos_header, $db->num_rows($query)));	
}
if($mybb->input['action'] == "do_add") {
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		$errors[] = $lang->invalid_post_verify_key2;
	}

    if(!strlen(trim($mybb->input['override_receive'])))
	{
		$errors[] = $lang->override_reveive_not;
	}
	if(!strlen(trim($mybb->input['subject'])))
	{
		$errors[] = $lang->subject_not;
	}
	if(!strlen(trim($mybb->input['plain'])))
	{
		$errors[] = $lang->plain_not;
	}
	if(!strlen(trim($mybb->input['html'])))
	{
		$mybb->input['html'] = "";
	}
	if(!strlen(trim($mybb->input['send'])))
	{
		$errors[] = $lang->send_not;
	}

	if(!isset($errors)) {
		$insert = array(
			"subject" => $db->escape_string($mybb->input['subject']),
			"html" => $db->escape_string($mybb->input['html']),
			"plain" => $db->escape_string($mybb->input['plain']),
			"override_receive" => $db->escape_string($mybb->input['override_receive']),
			"created" => TIME_NOW
		);
		$id = $db->insert_query("newsletter", $insert);

		if($mybb->input['send'])
			newsletter_prepare_send($id);

		flash_message($lang->newsletter_added, 'success');
		admin_redirect("index.php?module=".MODULE);
	} else
		$mybb->input['action'] = "add";
}
if($mybb->input['action'] == "add") {
	$page->add_breadcrumb_item($lang->add, "index.php?module=".MODULE."&action=add");
	$page->output_header($lang->add);
	generate_tabs("add");
	
	if(isset($errors)) {
		$page->output_inline_error($errors);
		$override_receive = $mybb->input['override_receive'];
		$subject = $mybb->input['subject'];
		$html = $mybb->input['html'];
		$plain = $mybb->input['plain'];
		$send = $mybb->input['send'];
	} else {
		$override_receive = 0;
		$subject = "";
		$html = "";
		$plain = "";
		$send = 0;
	}


	$form = new Form("index.php?module=".MODULE."&amp;action=do_add", "post");
	$form_container = new FormContainer($lang->add);

	$fid = "override_receive";
	$override_receive = $form->generate_yes_no_radio($fid, $override_receive, true, array("id" => $fid."_yes", "class" => $fid), array("id" => $fid."_no", "class" => $fid));
	$form_container->output_row($lang->override_receive." <em>*</em>", $lang->override_receive_desc, $override_receive);

	$subject = $form->generate_text_box("subject", $subject);
	$form_container->output_row($lang->subject." <em>*</em>", $lang->subject_desc, $subject);

	$plain = $form->generate_text_area("plain", $plain);
	$form_container->output_row($lang->plain." <em>*</em>", $lang->plain_desc, $plain);

	$html = $form->generate_text_area("html", $html);
	$form_container->output_row($lang->html, $lang->html_desc, $html);

	$fid = "send";
	$send = $form->generate_yes_no_radio($fid, $send, true, array("id" => $fid."_yes", "class" => $fid), array("id" => $fid."_no", "class" => $fid));
	$form_container->output_row($lang->send." <em>*</em>", $lang->send_desc, $send);

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->submit);
	$buttons[] = $form->generate_reset_button($lang->reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
if($mybb->input['action'] == "send") {
	if(!strlen(trim($mybb->input['id'])))
	{
		flash_message($lang->newsletter_no_id, 'error');
		admin_redirect("index.php?module=".MODULE);
	}
	$id=(int)$mybb->input['id'];
	newsletter_prepare_send($id);	
	flash_message($lang->newsletter_sent, 'success');
	admin_redirect("index.php?module=".MODULE);
}
if($mybb->input['action'] == "do_edit") {
	if(!strlen(trim($mybb->input['id'])))
	{
		flash_message($lang->newsletter_no_id, 'error');
		admin_redirect("index.php?module=".MODULE);
	}
	$id=(int)$mybb->input['id'];
	$query = $db->simple_select("newsletter", "sent", "id='{$id}'");
	if($db->num_rows($query) != 1)
	{
		flash_message($lang->newsletter_wrong_id, 'error');
		admin_redirect("index.php?module=".MODULE);
	}
	$nl = $db->fetch_array($query);

	if($nl['sent'] != 0)
	{
		flash_message($lang->newsletter_already_sent, 'error');
		admin_redirect("index.php?module=".MODULE);
	}

   	if(!verify_post_check($mybb->input['my_post_key']))
	{
		$errors[] = $lang->invalid_post_verify_key2;
	}

    if(!strlen(trim($mybb->input['override_receive'])))
	{
		$errors[] = $lang->override_reveive_not;
	}
	if(!strlen(trim($mybb->input['subject'])))
	{
		$errors[] = $lang->subject_not;
	}
	if(!strlen(trim($mybb->input['plain'])))
	{
		$errors[] = $lang->plain_not;
	}
	if(!strlen(trim($mybb->input['html'])))
	{
		$mybb->input['html'] = "";
	}
	if(!strlen(trim($mybb->input['send'])))
	{
		$errors[] = $lang->send_not;
	}

	if(!isset($errors)) {
		$update = array(
			"subject" => $db->escape_string($mybb->input['subject']),
			"html" => $db->escape_string($mybb->input['html']),
			"plain" => $db->escape_string($mybb->input['plain']),
			"override_receive" => $db->escape_string($mybb->input['override_receive'])
		);
		$db->update_query("newsletter", $update, "id='{$id}'");

		if($mybb->input['send'])
			newsletter_prepare_send($id);

		flash_message($lang->newsletter_edited, 'success');
		admin_redirect("index.php?module=".MODULE);
	} else
		$mybb->input['action'] = "edit";
}
if($mybb->input['action'] == "edit") {
	if(!strlen(trim($mybb->input['id'])))
	{
		flash_message($lang->newsletter_no_id, 'error');
		admin_redirect("index.php?module=".MODULE);
	}
	$id=(int)$mybb->input['id'];
	$query = $db->simple_select("newsletter", "*", "id='{$id}'");
	if($db->num_rows($query) != 1)
	{
		flash_message($lang->newsletter_wrong_id, 'error');
		admin_redirect("index.php?module=".MODULE);
	}
	$nl = $db->fetch_array($query);

	if($nl['sent'] != 0)
	{
		flash_message($lang->newsletter_already_sent, 'error');
		admin_redirect("index.php?module=".MODULE);
	}

	$page->add_breadcrumb_item($lang->edit, "index.php?module=".MODULE."&action=edit&id={$id}");
	$page->output_header($lang->edit);
	generate_tabs("edit");

	if(isset($errors)) {
		$page->output_inline_error($errors);
		$override_receive = $mybb->input['override_receive'];
		$subject = $mybb->input['subject'];
		$html = $mybb->input['html'];
		$plain = $mybb->input['plain'];
		$send = $mybb->input['send'];
	} else {
		$override_receive = $nl['override_receive'];
		$subject = $nl['subject'];
		$html = $nl['html'];
		$plain = $nl['plain'];
		$send = 0;
	}


	$form = new Form("index.php?module=".MODULE."&amp;action=do_edit", "post");
	$form_container = new FormContainer($lang->edit);

	$fid = "override_receive";
	$override_receive = $form->generate_yes_no_radio($fid, $override_receive, true, array("id" => $fid."_yes", "class" => $fid), array("id" => $fid."_no", "class" => $fid));
	$form_container->output_row($lang->override_receive." <em>*</em>", $lang->override_receive_desc, $override_receive);

	$subject = $form->generate_text_box("subject", $subject);
	$form_container->output_row($lang->subject." <em>*</em>", $lang->subject_desc, $subject);

	$plain = $form->generate_text_area("plain", $plain);
	$form_container->output_row($lang->plain." <em>*</em>", $lang->plain_desc, $plain);

	$html = $form->generate_text_area("html", $html);
	$form_container->output_row($lang->html, $lang->html_desc, $html);

	$fid = "send";
	$send = $form->generate_yes_no_radio($fid, $send, true, array("id" => $fid."_yes", "class" => $fid), array("id" => $fid."_no", "class" => $fid));
	$form_container->output_row($lang->send." <em>*</em>", $lang->send_desc, $send);

	echo $form->generate_hidden_field("id", $id);
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->submit);
	$buttons[] = $form->generate_reset_button($lang->reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
if($mybb->input['action'] == "delete") {
	if(!strlen(trim($mybb->input['id'])))
	{
		flash_message($lang->newsletter_no_id, 'error');
		admin_redirect("index.php?module=".MODULE);
	}
	$id=(int)$mybb->input['id'];
	$db->delete_query("newsletter", "id='{$id}'");
	flash_message($lang->newsletter_deleted, 'success');
	admin_redirect("index.php?module=".MODULE);
}
if(!isset($mybb->input['action']) || $mybb->input['action'] == ""){
	$page->output_header($lang->newsletter);
	generate_tabs("list");

	$table = new Table;

	$table->construct_header($lang->subject);
	$table->construct_header($lang->html);
	$table->construct_header($lang->override_receive);
	$table->construct_header($lang->sent);
	$table->construct_header($lang->controls, array("class" => "align_center"));

	$query = $db->simple_select("newsletter", "*", "", array("order_by"=>"created", "order_dir"=>"DESC"));
	if($db->num_rows($query) > 0)
	{
		while($nl = $db->fetch_array($query))
		{
			if(strlen($nl['plain']) > 100)
			    $nl['plain'] = substr($nl['plain'], 0, 100)."...";
			    
			if($nl['html'] != "")
			    $html = $lang->yes;
			else
				$html = $lang->no;
				
			if($nl['override_receive'])
			    $override_receive = $lang->yes;
			else
				$override_receive = $lang->no;

			if($nl['sent'] != 0)
			    $sent = $lang->yes;
			else
				$sent = $lang->no;

			$table->construct_cell("<strong>{$nl['subject']}</strong><br /><i>{$nl['plain']}</i>");
			$table->construct_cell($html);
			$table->construct_cell($override_receive);
			$table->construct_cell($sent);

			$popup = new PopupMenu("nl_".$nl['id'], $lang->options);
			if($nl['sent'] == 0) {
				$popup->add_item($lang->send, "index.php?module=".MODULE."&amp;action=send&amp;id={$nl['id']}");
				$popup->add_item($lang->edit, "index.php?module=".MODULE."&amp;action=edit&amp;id={$nl['id']}");
			}
			$popup->add_item($lang->delete, "index.php?module=".MODULE."&amp;action=delete&amp;id={$nl['id']}");
			$table->construct_cell($popup->fetch(), array("class"=>"align_center"));

			$table->construct_row();
		}
	} else {
		$table->construct_cell($lang->no_newsletter, array('class' => 'align_center', 'colspan' => 5));
		$table->construct_row();
	}
	$table->output($lang->newsletter);
}

$page->output_footer();

function generate_tabs($selected)
{
	global $lang, $page;

	$sub_tabs = array();
	$sub_tabs['list'] = array(
		'title' => $lang->list,
		'link' => "index.php?module=".MODULE,
		'description' => $lang->list_desc
	);
	$sub_tabs['add'] = array(
		'title' => $lang->add,
		'link' => "index.php?module=".MODULE."&amp;action=add",
		'description' => $lang->add_desc
	);
	$sub_tabs['abos'] = array(
		'title' => $lang->abos,
		'link' => "index.php?module=".MODULE."&amp;action=abos",
		'description' => $lang->abos_desc
	);

	$page->output_nav_tabs($sub_tabs, $selected);
}
?>