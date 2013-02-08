<?php
/*
Plugin Name: Mingle Forum
Plugin URI: http://cartpauj.com/projects/mingle-forum-plugin
Description: Mingle Forum is growing rapidly in popularity because it is simple, reliable, lightweight and does just enough to keep things interesting. If you like this plugin please consider making a donation at http://cartpauj.com/donate/
Version: 1.0.34
Author: Cartpauj
Author URI: http://cartpauj.com/
Text Domain: mingleforum
Copyright: 2009-2011, cartpauj

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//Textdomain Hook
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain('mingleforum', false, $plugin_dir.'/i18n/');

//Load class file and create instance
include_once("wpf.class.php");
$mingleforum = new mingleforum();

//Activation Hook
register_activation_hook(__FILE__ ,array(&$mingleforum,'wp_forum_install'));
//Shortcode Hook
add_shortcode('mingleforum', array(&$mingleforum, "go"));
//Action Hooks
add_action('init', array(&$mingleforum, "set_cookie"));
add_action('wp', array(&$mingleforum, "before_go")); //Redirects Old URL's to SEO URL's
//Filter Hooks
add_filter("wp_title", array(&$mingleforum, "set_pagetitle"));

//Functions
function latest_activity($num = 5){
	global $mingleforum;
	return $mingleforum->latest_activity($num);
}
?>