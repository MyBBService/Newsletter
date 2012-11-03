<?php
if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

$page->add_breadcrumb_item($lang->newsletter, "index.php?module=forum-newsletter");

$page->output_header($lang->newsletter);

echo "OK";

$page->output_footer();
?>