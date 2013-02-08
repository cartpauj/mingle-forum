<?php
global $mingleforum;

$root = dirname(dirname(dirname(dirname(__FILE__))));
if (file_exists($root.'/wp-load.php')) {
	// WP 2.6
	require_once($root.'/wp-load.php');
	} else {
	// before WP 2.6
	require_once($root.'/wp-config.php');
	}
	$mingleforum->setup_links();
	$mingleforum->get_forum_admin_ops();
	header('Content-type: application/xml; charset="utf-8"',true);
	$mingleforum->do_sitemap();
?>