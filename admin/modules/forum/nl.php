<?php
if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

$page->add_breadcrumb_item("NL", "index.php?module=forum-nl");

$page->output_header("NL");

echo "OK";

$page->output_footer();
?>