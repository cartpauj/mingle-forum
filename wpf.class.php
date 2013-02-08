<?php
include("wpf_define.php");
include_once('bbcode.php');

if(!class_exists('mingleforum')) {
class mingleforum{

	var $db_version = 1; //MANAGES DB VERSION

	function mingleforum()
	{
		$this->options = $this->get_forum_admin_ops();
		$this->get_set_ads_options();
		if($this->options['forum_use_seo_friendly_urls'])
		{
			add_filter("init", array(&$this, "flush_wp_rewrite_rules"));
			add_filter("rewrite_rules_array", array(&$this, "set_seo_friendly_rules"));
		}
		add_action("admin_menu", array(&$this,"add_admin_pages"));
		add_action("admin_head", array(&$this, "admin_header"));
		add_action("wp_head", array(&$this, "setup_header"));
		add_action("plugins_loaded", array(&$this, "wpf_load_widget"));
		add_action("wp_footer", array(&$this, "wpf_footer"));
		if($this->options['wp_posts_to_forum'])
		{
			add_action("add_meta_boxes", array(&$this, "send_wp_posts_to_forum"));
			add_action("publish_post", array(&$this, "saving_posts"));
		}
		//Ads filter hooks
		add_filter('mf_ad_above_forum', array(&$this, 'mf_ad_above_forum'));
		add_filter('mf_ad_below_forum', array(&$this, 'mf_ad_below_forum'));
		add_filter('mf_ad_above_branding', array(&$this, 'mf_ad_above_branding'));
		add_filter('mf_ad_above_info_center', array(&$this, 'mf_ad_above_info_center'));
		add_filter('mf_ad_above_quick_reply', array(&$this, 'mf_ad_above_quick_reply'));
		add_filter('mf_ad_above_breadcrumbs', array(&$this, 'mf_ad_above_breadcrumbs'));
		add_filter('mf_ad_below_first_post', array(&$this, 'mf_ad_below_first_post'));
		$this->init();
	}

	// !Member variables
	var $showing		= false;

	var $page_id		= "";
	var $reg_link 		= "";
	var $profile_link 	= "";
	var $logout_link 	= "";
	var $home_url 		= "";
	var $forum_link		= "";
	var $group_link		= "";
	var $thread_link	= "";
	var $add_topic_link = "";

	// DB tables
	var $t_groups 	= "";
	var $t_forums 	= "";
	var $t_threads 	= "";
	var $t_posts 	= "";
	var $_usergroups = "";
	var $t_usergroup2user = "";
	var $o = "";

	var $current_group = "";
	var $current_forum = "";
	var $current_thread = "";
	var $notify_msg = "";
	var $current_view = "";
	var $opt = array();
	var $base_url = "";
	var $skin_url = "";
	var $curr_page = "";
	var $last_visit = "";
	var $user_options = array();
	var $options = array();
	var $ads_options = array();

	// Initialize varables
	function init(){
		global $table_prefix, $user_ID;

		$this->page_id			= $this->get_pageid();
		$this->profile_link 	= site_url()."/wp-admin/profile.php";

		$this->t_groups 		= $table_prefix."forum_groups";
		$this->t_forums 		= $table_prefix."forum_forums";
		$this->t_threads 		= $table_prefix."forum_threads";
		$this->t_posts 			= $table_prefix."forum_posts";
		$this->t_usergroups 	= $table_prefix."forum_usergroups";//! check this later
		$this->t_usergroup2user = $table_prefix."forum_usergroup2user"; //x testing

		$this->current_forum 	= false;
		$this->current_group 	= false;
		$this->current_thread 	= false;

		$this->curr_page 		= 0;

		$this->user_options = array('allow_profile' => true,
									'signature'		=> ""
									);
		// Get the options
		$this->opt = get_option('mingleforum_options');
		if ($this->opt['forum_skin'] == "Default")
			$this->skin_url = OLDSKINURL.$this->opt['forum_skin'];
		else
			$this->skin_url = SKINURL.$this->opt['forum_skin'];
	}

	function get_set_ads_options() {
		$this->ads_options = array( 'mf_ad_above_forum_on'			=> false,
									'mf_ad_above_forum'				=> '',
									'mf_ad_below_forum_on'			=> false,
									'mf_ad_below_forum'				=> '',
									'mf_ad_above_branding_on'		=> false,
									'mf_ad_above_branding'			=> '',
									'mf_ad_above_info_center_on'	=> false,
									'mf_ad_avove_info_center'		=> '',
									'mf_ad_above_quick_reply_on'	=> false,
									'mf_ad_above_quick_reply'		=> '',
									'mf_ad_above_breadcrumbs_on'	=> false,
									'mf_ad_above_breadcrumbs'		=> '',
									'mf_ad_below_first_post_on'		=> false,
									'mf_ad_below_first_post'		=> '',
									'mf_ad_custom_css'				=> ''
									);
		$initOps = get_option('mingleforum_ads_options');
		if (!empty($initOps)){
			foreach ($initOps as $key => $option)
				$this->ads_options[$key] = $option;
		}
		update_option('mingleforum_ads_options', $this->ads_options);
	}

	function get_forum_admin_ops() {
		$this->options = array( 'wp_posts_to_forum'				=> false,
								'forum_posts_per_page' 			=> 10,
								'forum_threads_per_page' 		=> 20,
								'forum_require_registration' 	=> true,
								'forum_show_login_form'			=> true,
								'forum_date_format' 			=> "F j, Y, H:i",
								'forum_use_gravatar' 			=> true,
								'forum_show_bio'				=> true,
								'forum_skin'					=> "Default",
								'forum_use_rss' 				=> true,
								'forum_use_seo_friendly_urls'	=> false,
								'forum_allow_image_uploads'		=> false,
								'notify_admin_on_new_posts'		=> false,
								'set_sort' 						=> "DESC",
								'forum_use_spam' 				=> false,
								'forum_use_bbcode' 				=> true,
								'forum_captcha' 				=> true,
								'hot_topic'						=> 15,
								'veryhot_topic'					=> 25,
								'forum_display_name'			=> 'user_login',
								'level_one'						=> 25,
								'level_two'						=> 50,
								'level_three'					=> 100,
								'level_newb_name'				=> __("Newbie", "mingleforum"),
								'level_one_name'				=> __("Beginner", "mingleforum"),
								'level_two_name'				=> __("Advanced", "mingleforum"),
								'level_three_name'				=> __("Pro", "mingleforum"),
								'forum_db_version'				=> 0,
								'forum_disabled_cats'			=> array(),
                'allow_user_replies_locked_cats' => false,
                'forum_posting_time_limit' => 300,
                'forum_hide_branding' => false
								);
		$initOps = get_option('mingleforum_options');
		//Don't overwrite current opitions but allow the flexibility to add more options
		if (!empty($initOps)){
			foreach ($initOps as $key => $option)
				$this->options[$key] = $option;
		}
		update_option('mingleforum_options', $this->options);
		return $this->options;
	}

	// Add admin pages
	function add_admin_pages(){
		include_once("fs-admin/fs-admin.php");
		$wpfa = new mingleforumadmin();

		add_menu_page(__("Mingle Forum - Options", "mingleforum"), "Mingle Forum", "administrator", "mingle-forum", array(&$wpfa, "options"), WPFURL."images/logo.png");
		add_submenu_page("mingle-forum", __("Mingle Forum - Options", "mingleforum"), __("Options", "mingleforum"), "administrator", 'mingle-forum', array(&$wpfa, "options"));
		add_submenu_page('mingle-forum', __('Ads', 'mingleforum'), __('Ads', 'mingleforum'), "administrator", 'mfads', array(&$wpfa, "ads"));
		add_submenu_page("mingle-forum", __("Skins", "mingleforum"), __("Skins", "mingleforum"), "administrator", 'mfskins', array(&$wpfa, "skins"));
		add_submenu_page("mingle-forum", __("Forum Structure - Categories & Forums", "mingleforum"), __("Forum Structure", "mingleforum"), "administrator", 'mfstructure', array(&$wpfa, "structure"));
		add_submenu_page("mingle-forum", __("Moderators", "mingleforum"), __("Moderators", "mingleforum"), "administrator", 'mfmods', array(&$wpfa, "moderators"));
		add_submenu_page("mingle-forum", __("User Groups", "mingleforum"), __("User Groups", "mingleforum"), "administrator", 'mfgroups', array(&$wpfa, "usergroups"));
		add_submenu_page("mingle-forum", __("About", "mingleforum"), __("About", "mingleforum"), "administrator", 'mfabout', array(&$wpfa, "about"));
	}

	function admin_header(){
		echo "<link rel='stylesheet' href='".get_bloginfo('wpurl')."/wp-content/plugins/".WPFPLUGIN."/wpf_admin.css' type='text/css' media='screen'  />"; 
		?>
			<script language="JavaScript" type="text/javascript" src="<?php echo WPFURL."js/script.js"?>"></script>
		<?php
	}

	function wpf_load_widget() {
		wp_register_sidebar_widget("MFWidget", __("Forums Latest Activity", "mingleforum"), array(&$this, "widget"));
		wp_register_widget_control("MFWidget", __("Forums Latest Activity", "mingleforum"), array(&$this, "widget_wpf_control"));
	}

	function widget($args) {
		global $wpdb;
		$toShow = 0;
		$unique = array();
		$this->setup_links();
		$this->get_forum_admin_ops();
		$widget_option = get_option("wpf_widget");
		$posts = $wpdb->get_results("SELECT * FROM $this->t_posts ORDER BY `date` DESC LIMIT 50");
		echo $args['before_widget'];
		echo $args['before_title'] . $widget_option["wpf_title"] . $args['after_title'];
		echo "<ul>";
		foreach($posts as $post) {
			if(!in_array($post->parent_id, $unique) && $toShow < $widget_option["wpf_num"])
			{
				//$user = get_userdata($post->author_id);
				if($this->have_access($this->forum_get_group_from_post($post->parent_id)))
					echo "<li><a href='".$this->get_paged_threadlink($post->parent_id, '#postid-'.$post->id)."'>".$this->output_filter($post->subject)."</a><br/>".__("by:", "mingleforum")." ".$this->profile_link($post->author_id)."<br /><small>".$this->format_date($post->date)."</small></li>";
				$unique[] = $post->parent_id;
				$toShow += 1;
			}
		}
		echo "</ul>";
		echo $args['after_widget'];
	}

	function latest_activity($num = 5, $ul = true){
		global $wpdb;
		$toShow = 0;
		$unique = array();
		$posts = $wpdb->get_results("SELECT * FROM $this->t_posts ORDER BY `date` DESC LIMIT 50");
		if($ul) echo "<ul class='forumtwo'>";
		foreach($posts as $post){
			if(!in_array($post->parent_id, $unique) && $toShow < $num)
			{
				//$user = get_userdata($post->author_id);
				if($this->have_access($this->forum_get_group_from_post($post->parent_id)))
					echo "<li class='forum'><a href='".$this->get_paged_threadlink($post->parent_id, '#postid-'.$post->id)."'>".$this->output_filter($post->subject)."</a><br />".__("by:", "mingleforum")." ".$this->profile_link($post->author_id)."<br/><small>".$this->format_date($post->date)."</small></li>";
				$unique[] = $post->parent_id;
				$toShow += 1;
			}
		}
		if($ul)echo "</ul>";
	}

	function widget_wpf_control(){
		if (isset($_POST["wpf_submit"])) {
    		$name = strip_tags(stripslashes($_POST["wpf_title"]));
    		$num = strip_tags(stripslashes($_POST["wpf_num"]));
    		$widget_option["wpf_title"] = $name;
			$widget_option["wpf_num"] = $num;
    		update_option("wpf_widget", $widget_option);
 		}
 			$widget_option = get_option("wpf_widget");
		echo "<p><label for='wpf_title'>".__("Title to display in the sidebar:", "mingleforum")."
			<input style='width: 250px;' id='wpf_title' name='wpf_title' type='text' class='wpf-input' value='{$widget_option['wpf_title']}' /></label></p>";
		echo "<p><label for='wpf_num'>".__("How many items would you like to display?", "mingleforum");
		echo "<select name='wpf_num'>";
		for($i = 1; $i < 21; ++$i){
			if($widget_option["wpf_num"] == $i)
				$selected = "selected = 'selected'";
			else
				$selected = "";
			echo "<option value='$i' $selected>$i</option>";
		}
		echo "</select>";
			echo "</label></p>
				<input type='hidden' id='wpf_submit' name='wpf_submit' value='1' />";
	}

	function wpf_footer(){
		if(is_page($this->get_pageid()))
		{
			?>
			<script type="text/javascript" >
				<?php echo "var skinurl = '$this->skin_url';";?>
				fold();
			function notify(){
					
				var answer = confirm ('<?php echo $this->notify_msg;?>');
				if (!answer)
					return false;
				else
					return true;
			}
			</script>
			<?php
		}
	}

	function setup_links(){
	global $wp_rewrite;
		if($wp_rewrite->using_permalinks())
			$delim = "?";
		else
			$delim = "&";
		$perm = get_permalink($this->page_id);
		$this->forum_link 		= $perm.$delim."mingleforumaction=viewforum&f=";
		$this->group_link 		= $perm.$delim."mingleforumaction=vforum&g=";
		$this->thread_link 		= $perm.$delim."mingleforumaction=viewtopic&t=";
		$this->add_topic_link 	= $perm.$delim."mingleforumaction=addtopic&forum=$this->current_forum";
		$this->post_reply_link 	= $perm.$delim."mingleforumaction=postreply&thread=$this->current_thread";
		$this->base_url			= $perm.$delim."mingleforumaction=";
		//START MINGLE REG LINK
		if(!function_exists('is_plugin_active'))
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active('mingle/mingle.php'))
		{
			global $mngl_options;
			if(!empty($mngl_options->signup_page_id) and $mngl_options->signup_page_id > 0)
				$this->reg_link = get_permalink($mngl_options->signup_page_id);
			else
				$this->reg_link = $mngl_blogurl . '/wp-login.php?action=register';
		}
		else
		{
			$this->reg_link = site_url()."/wp-login.php?action=register";
		}
		//END MINGLE REG LINK
		$this->topic_feed_url = WPFURL."feed.php?topic=";
		$this->global_feed_url = WPFURL."feed.php?topic=all";
		$this->home_url = $perm;
		$this->logout_link = site_url()."/wp-login.php?action=logout&redirect_to=".get_permalink($this->get_pageid());
	}

	function setup_linksdk($perm){
		global $wp_rewrite;
		if($wp_rewrite->using_permalinks())
			$delim = "?";
		else
			$delim = "&";
		$this->forum_link 		= $perm.$delim."mingleforumaction=viewforum&f=";
		$this->group_link 		= $perm.$delim."mingleforumaction=vforum&g=";
		$this->thread_link 		= $perm.$delim."mingleforumaction=viewtopic&t=";
		$this->add_topic_link 	= $perm.$delim."mingleforumaction=addtopic&forum=$this->current_forum";
		$this->post_reply_link 	= $perm.$delim."mingleforumaction=postreply&thread=$this->current_thread";
		$this->base_url			= $perm.$delim."mingleforumaction=";
		//START MINGLE REG LINK
		if(!function_exists('is_plugin_active'))
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active('mingle/mingle.php'))
		{
			global $mngl_options;
			if(!empty($mngl_options->signup_page_id) and $mngl_options->signup_page_id > 0)
				$this->reg_link = get_permalink($mngl_options->signup_page_id);
			else
				$this->reg_link = $mngl_blogurl . '/wp-login.php?action=register';
		}
		else
		{
			$this->reg_link 		= site_url()."/wp-login.php?action=register";
		}
		//END MINGLE REG LINK
		$this->topic_feed_url	= WPFURL."feed.php?topic=";
		$this->global_feed_url	= WPFURL."feed.php?topic=all";
		$this->home_url 		= $perm;
		$this->logout_link 		= site_url()."/wp-login.php?action=logout&redirect_to=".get_permalink($this->get_pageid());
	}

	function get_addtopic_link(){
		return $this->add_topic_link.".$this->curr_page";
	}

	function get_post_reply_link(){
		return $this->post_reply_link.".$this->curr_page";
	}

	function get_forumlink($id, $page = ''){
		if($this->options['forum_use_seo_friendly_urls'])
		{
			$group = $this->get_seo_friendly_title($this->get_groupname($this->get_parent_id(FORUM, $id))."-group".$this->get_parent_id(FORUM, $id));
			$forum = $this->get_seo_friendly_title($this->get_forumname($id)."-forum".$id).$page;
			return rtrim($this->home_url, '/').'/'.$group.'/'.$forum;
		}
		else
			if($page == '')
				return $this->forum_link.$id.".$this->curr_page";
			else
				return $this->forum_link.$id.$page;
	}

	function get_grouplink($id){
		if($this->options['forum_use_seo_friendly_urls'])
		{
			$group = $this->get_seo_friendly_title($this->get_groupname($id)."-group".$id);
			return rtrim($this->home_url, '/').'/'.$group;
		}
		else
			return $this->group_link.$id.".$this->curr_page";
	}

	function get_threadlink($id, $page = ''){
		if($this->options['forum_use_seo_friendly_urls'])
		{
			$group = $this->get_seo_friendly_title($this->get_groupname($this->get_parent_id(FORUM, $this->get_parent_id(THREAD, $id)))."-group".$this->get_parent_id(FORUM, $this->get_parent_id(THREAD, $id)));
			$forum = $this->get_seo_friendly_title($this->get_forumname($this->get_parent_id(THREAD, $id))."-forum".$this->get_parent_id(THREAD, $id));
			$thread = $this->get_seo_friendly_title($this->get_subject($id)."-thread".$id);
			return rtrim($this->home_url, '/').'/'.$group.'/'.$forum.'/'.$thread.$page;
		}
		else
			return $this->thread_link.$id.$page;
	}

	function get_paged_threadlink($id, $postid = ''){
		global $wpdb;
		$wpdb->query("SELECT * FROM {$this->t_posts} WHERE parent_id = {$id}");
		$num = ceil($wpdb->num_rows / $this->options['forum_posts_per_page']) - 1;
		if($num < 0)
			$num = 0;
		if($this->options['forum_use_seo_friendly_urls'])
		{
			$group = $this->get_seo_friendly_title($this->get_groupname($this->get_parent_id(FORUM, $this->get_parent_id(THREAD, $id)))."-group".$this->get_parent_id(FORUM, $this->get_parent_id(THREAD, $id)));
			$forum = $this->get_seo_friendly_title($this->get_forumname($this->get_parent_id(THREAD, $id))."-forum".$this->get_parent_id(THREAD, $id));
			$thread = $this->get_seo_friendly_title($this->get_subject($id)."-thread".$id);
			return rtrim($this->home_url, '/').'/'.$group.'/'.$forum.'/'.$thread.".".$num.$postid;
		}
		else
			return $this->thread_link.$id.".".$num.$postid;
	}

	function get_pageid(){
		global $wpdb;
		return $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_content LIKE '%[mingleforum]%' AND post_status = 'publish' AND post_type = 'page'");
	}

	function get_groups($id = ''){
		global $wpdb;
		$cond = "";
		if($id)
			$cond = "WHERE id = $id";
		return $wpdb->get_results("SELECT * FROM $this->t_groups $cond ORDER BY sort ".SORT_ORDER); 
	}

	function get_forums($id = ''){
		global $wpdb;
		if($id){
			$forums = $wpdb->get_results("SELECT * FROM $this->t_forums WHERE parent_id = $id ORDER BY SORT ".SORT_ORDER);
			return $forums;
		}
		else 
			return $wpdb->get_results("SELECT * FROM $this->t_forums ORDER BY sort ".SORT_ORDER);
	}

	function get_threads($id = ''){
		global $wpdb;
		$start = $this->curr_page*$this->opt['forum_threads_per_page'];
		$end = $this->opt['forum_threads_per_page'];
		$limit = "$start, $end";
		if($id){
			$threads = $wpdb->get_results("SELECT * FROM $this->t_threads WHERE parent_id = $id AND status='open' ORDER BY last_post ".SORT_ORDER." LIMIT $limit");
			return $threads;
		}
		else
			return $wpdb->get_results("SELECT * FROM $this->t_threads ORDER BY `date` ".SORT_ORDER);
	}

	function get_sticky_threads($id){
		global $wpdb;
		if($id){
			$threads = $wpdb->get_results("SELECT * FROM $this->t_threads WHERE parent_id = $id AND status='sticky' ORDER BY last_post ".SORT_ORDER);
			return $threads;
		}
	}

	function get_posts($thread_id){
		global $wpdb;
		$start = $this->curr_page*$this->opt['forum_posts_per_page'];
		$end = $this->opt['forum_posts_per_page'];
		$limit = "$start, $end";
		if($thread_id){
			$posts = $wpdb->get_results("SELECT * FROM $this->t_posts WHERE parent_id = $thread_id ORDER BY `date` ASC LIMIT $limit");
			return $posts;
		}else{
			return false;
		}
	}

	function get_groupname($id){
		global $wpdb;
		return $this->output_filter($wpdb->get_var("SELECT name FROM $this->t_groups WHERE id = $id"));
	}

	function get_forumname($id){
		global $wpdb;
		return $this->output_filter($wpdb->get_var("SELECT name FROM $this->t_forums WHERE id = $id"));
	}

	function get_threadname($id){
		global $wpdb;
		return $this->output_filter($wpdb->get_var("SELECT subject FROM $this->t_threads WHERE id = $id"));
	}

	function get_postname($id){
		global $wpdb;
		return $this->output_filter($wpdb->get_var("SELECT subject FROM $this->t_posts WHERE id = $id"));
	}

	function get_group_description($id){
		global $wpdb;
		return $wpdb->get_var("SELECT description FROM $this->t_groups WHERE id = $id");
	}

	function get_forum_description($id){
		global $wpdb;
		return $wpdb->get_var("SELECT description FROM $this->t_forums WHERE id = $id");
	}

	function current_group(){
		return $this->current_group;
	}

	function current_forum(){
		return $this->current_forum;
	}

	function current_thread(){
		return $this->current_thread;
	}

	function check_parms($parm){
		$regexp = "/^([+-]?((([0-9]+(\.)?)|([0-9]*\.[0-9]+))([eE][+-]?[0-9]+)?))$/";
		if (!preg_match($regexp, $parm)){
			wp_die("Bad request, please re-enter.");
		}
		$p = explode(".", $parm);
		if(count($p) > 1)
			$this->curr_page = $p[1];
		else
			$this->curr_page = 0;
		return $p[0];
	}

	function before_go()
	{
		$this->setup_links();

		if(isset($_GET['markallread']) && $_GET['markallread'] == "true")
			$this->markallread();

		if(isset($_GET['mingleforumaction']))
			$action = $_GET['mingleforumaction'];
		else
			$action = false;
		if (!isset($_GET['getNewForumID']) && !isset($_GET['delete_topic']) && !isset($_GET['remove_post']) && !isset($_GET['forumsubs']) && !isset($_GET['threadsubs']) && !isset($_GET['sticky']) && !isset($_GET['closed']))
		{
			if ($action != false)
			{
				if ($this->options['forum_use_seo_friendly_urls'])
				{
					switch($action)
					{
						case 'vforum':
							$whereto = $this->get_grouplink($this->check_parms($_GET['g']));
							break;
						case 'viewforum':
							$whereto = $this->get_forumlink($this->check_parms($_GET['f']));
							break;
						case 'viewtopic':
							$whereto = $this->get_threadlink($this->check_parms($_GET['t']));
							break;
					}
					if (!empty($whereto))
					{
						header("HTTP/1.1 301 Moved Permanently");
						if($this->curr_page > 0)
							header("Location: ".$whereto.".".$this->curr_page);
						else
							header("Location: ".$whereto);
					}
				}
			}
		}
	}

	function go() {
		global $user_ID;
		$start_time = microtime(true);
		get_currentuserinfo();
    
    $this->o = "";

		if($user_ID){
			if(get_user_meta($user_ID, 'wpf_useroptions', true) == ''){
				update_user_meta($user_ID, 'wpf_useroptions', $this->user_options);
			}
		}

		if(isset($_GET['mingleforumaction']))
			$action = $_GET['mingleforumaction'];
		else
			$action = false;
		if ($action == false)
		{
			if ($this->options['forum_use_seo_friendly_urls'])
			{
				$uri = $this->get_seo_friendly_query();
				if (!empty($uri) && $uri['action'] && $uri['id'])
				{
					switch($uri['action'])
					{
						case 'group':
							$action = 'vforum';
							$_GET['g'] = $uri['id'];
							break;
						case 'forum':
							$action = 'viewforum';
							$_GET['f'] = $uri['id'];
							break;
						case 'thread':
							$action = 'viewtopic';
							$_GET['t'] = $uri['id'];
							break;
					}
				}
			}
		}

		if($action){
			switch($action){
				case 'viewforum': 
						$this->current_view = FORUM;
						$this->showforum($this->check_parms($_GET['f']));
						break;
				case 'viewtopic': 
						$this->current_view = THREAD;
						$this->showthread($this->check_parms($_GET['t']));
						break;
				case 'addtopic':
						include(WPFPATH.'wpf-thread.php');
						break;
				case 'postreply':
						if($this->is_closed($_GET['thread'])){
							wp_die(__("An unknown error has occured. Please try again.", "mingleforum"));
						}else{
              $this->current_thread = $this->check_parms($_GET['thread']);
							include(WPFPATH.'wpf-post.php');
						}
						break;
				case 'shownew':
						$this->show_new();
						break;
				case 'editpost':
						include(WPFPATH.'wpf-post.php');
						break;
				case 'profile':
						$this->view_profile();
						break;
				case 'search':
						$this->search_results();
						break;
				case 'editprofile':
						include(WPFPATH.'wpf-edit-profile.php');
						break;
				case 'vforum':
						$this->vforum($this->check_parms($_GET['g']));
						break;
			}
		}
		else{
			$this->current_view = MAIN;
			$this->mydefault();
		}
		$end_time = microtime(true);
		$load = __("Page loaded in:", "mingleforum")." ".round($end_time-$start_time, 3)." ".__("seconds.", "mingleforum")."";
    
    if(!$this->options['forum_hide_branding'])
    {
      $this->o .= apply_filters('mf_ad_above_branding', ''); //Adsense Area -- Above Branding
      $this->o .= "<div id='wpf-info'><small>
        ".__("Mingle Forum ", "mingleforum")." by <a href='http://cartpauj.com'>cartpauj</a><br /> 
        ".__("Version:", "mingleforum").$this->get_version()."; 
        $load</small>
      </div>";
    }
    
		$above_forum_ad = apply_filters('mf_ad_above_forum', ''); //Adsense Area -- Above Forum
		$below_forum_ad = apply_filters('mf_ad_below_forum', ''); //Adsense Area -- Below Forum
		return $above_forum_ad."<div id='wpf-wrapper'>".$this->o."</div>".$below_forum_ad;
	}

	function get_version(){
		$plugin_data = implode('', file(ABSPATH."wp-content/plugins/".WPFPLUGIN."/wpf-main.php"));
		if (preg_match("|Version:(.*)|i", $plugin_data, $version))
			$version = $version[1];
		return $version;
	}

	function get_userdata($user_id, $data){
		global $wpdb;
		$user = get_userdata($user_id);
		if(!$user)
			return __("Guest", "mingleforum");
		return $user->$data;
	}

	function get_lastpost($thread_id){
		global $wpdb;
		$post = $wpdb->get_row("select `date`, author_id, id from $this->t_posts where parent_id = $thread_id order by `date` DESC limit 1");
		if (!empty($post)) {
			$link = $this->get_paged_threadlink($thread_id);
			return __("by", "mingleforum")." ".$this->profile_link($post->author_id)."<br />".__("on", "mingleforum")." <a href='".$link."'>".date($this->opt['forum_date_format'], strtotime($post->date))."</a>";
		}
		else
			return false;
	}

	function get_lastpost_all(){
		global $wpdb;
		$post = $wpdb->get_row("select `date`, author_id, id from $this->t_posts order by `date` DESC limit 1");
		return __("Latest Post by", "mingleforum")." ".$this->profile_link($post->author_id)."<br />".__("on", "mingleforum")." ".date($this->opt['forum_date_format'], strtotime($post->date));
	}

	function showforum($forum_id){
		global $user_ID, $wpdb;
		if(isset($_GET['delete_topic']))
			$this->remove_topic();
		if(isset($_GET['move_topic']))
			$this->move_topic();
		if(!empty($forum_id)){
		$out = "";
		$del = "";
			$threads = $this->get_threads($forum_id);
			$sticky_threads = $this->get_sticky_threads($forum_id);
			$t = $sticky_threads + $threads;
			$this->current_group = $this->get_parent_id(FORUM, $forum_id);
			$this->current_forum = $forum_id;
			$this->forum_subscribe();
			if($this->is_forum_subscribed())
				$this->notify_msg = __("Remove this Forum from your email notifications?", "mingleforum");
			else
				$this->notify_msg = __("This will notify you of all new Topics created in this Forum. Are you sure that is what you want to do?", "mingleforum");
			$this->header();
			if(isset($_GET['getNewForumID'])){
				$out .= $this->getNewForumID();
			}else{
				if(!$this->have_access($this->current_group)){
					wp_die(__("Sorry, but you don't have access to this forum", "mingleforum"));
				}
				$out .= "<table cellpadding='0' cellspacing='0'>
							<tr class='pop_menus'>
								<td width='100%'>".$this->thread_pageing($forum_id)."</td>
								<td>".$this->forum_menu($this->current_group)."</td>
							</tr>
						</table>";
				$out .= "<div class='wpf'><table class='wpf-table' id='topicTable'>
								<tr>
									<th width='7%' class='forumIcon'>".__("Status", "mingleforum")."</th>
									<th>".__("Topic Title", "mingleforum")."</th>
									<th width='11%' nowrap='nowrap'>".__("Started by", "mingleforum")."</th>
									<th width='4%'>".__("Replies", "mingleforum")."</th>
									<th width='4%'>".__("Views", "mingleforum")."</th>
									<th width='22%'>".__("Last post", "mingleforum")."</th>
								</tr>";
		/***************************************************************************************/
			if($sticky_threads){
				$out .= "<tr><th class='wpf-bright' colspan='6'>".__("Sticky Topics", "mingleforum")."</th></tr>";
				foreach($sticky_threads as $thread){
					if($this->is_moderator($user_ID, $this->current_forum)){
						if($this->options['forum_use_seo_friendly_urls'])
							$strCommands = "<a href='".$this->forum_link.$this->current_forum."&getNewForumID&topic=$thread->id'>".__("Move Topic", "mingleforum")."</a> | <a href='".$this->forum_link.$this->current_forum."&delete_topic&topic=$thread->id' onclick='return wpf_confirm();'>".__("Delete Topic", "mingleforum")."</a>";
						else
							$strCommands = "<a href='".$this->get_forumlink($this->current_forum)."&getNewForumID&topic=$thread->id'>".__("Move Topic", "mingleforum")."</a> | <a href='".$this->get_forumlink($this->current_forum)."&delete_topic&topic=$thread->id' onclick='return wpf_confirm();'>".__("Delete Topic", "mingleforum")."</a>";
						$del = "<small><br />($strCommands)</small>";
					}
					$image = "";
					if($user_ID){
						$poster_id = $this->last_posterid_thread($thread->id); // date and author_id
						if($user_ID != $poster_id){
							$lp = strtotime($this->last_poster_in_thread($thread->id)); // date
							$lv = strtotime($this->last_visit());
							if($lp > $lv)
								$image = "<img src='$this->skin_url/images/new.gif' alt='".__("New posts since last visit", "mingleforum")."'>";
						}
					}
					$sticky_img = "<img alt='' src='$this->skin_url/images/topic/normal_post_sticky.gif'/>";
					$out .= "<tr>
									<td class='forumIcon' align='center'>$sticky_img</td>
									<td class='wpf-alt sticky'><span class='topicTitle'><a href='"
										.$this->get_threadlink($thread->id)."'>"
										.$this->output_filter($thread->subject)."</a>&nbsp;&nbsp;$image</span> $del
									</td>
									<td>".$this->profile_link($thread->starter)."</td>
									<td class='wpf-alt' align='center'>".( $this->num_posts($thread->id) - 1 )."</td>
									<td class='wpf-alt' align='center'>".$thread->views."</td>
									<td><small>".$this->get_lastpost($thread->id)."</small></td>
								</tr>";
					}
		/********************************************************************************************************/
				$out .= "<tr><th class='wpf-bright forumTopics' colspan='6'>".__("Forum Topics", "mingleforum")."</th></tr>";
				}
				$alt = "alt even";
				foreach($threads as $thread){
					$alt = ($alt=="alt even")?"odd":"alt even";
					if($user_ID){
					$image = "";
						$poster_id = $this->last_posterid_thread($thread->id); // date and author_id
						if($user_ID != $poster_id){
							$lp = strtotime($this->last_poster_in_thread($thread->id)); // date
							$lv = strtotime($this->last_visit());
							if($lp > $lv)
								$image = "<img src='$this->skin_url/images/new.gif' alt='".__("New posts since last visit", "mingleforum")."'>";
						}
					}
					if($this->is_moderator($user_ID, $this->current_forum)){
						if($this->options['forum_use_seo_friendly_urls'])
							$strCommands = "<a href='".$this->forum_link.$this->current_forum."&getNewForumID&topic=$thread->id'>".__("Move Topic", "mingleforum")."</a> | <a href='".$this->forum_link.$this->current_forum."&delete_topic&topic=$thread->id' onclick='return wpf_confirm();'>".__("Delete Topic", "mingleforum")."</a>";
						else
							$strCommands = "<a href='".$this->get_forumlink($this->current_forum)."&getNewForumID&topic=$thread->id'>".__("Move Topic", "mingleforum")."</a> | <a href='".$this->get_forumlink($this->current_forum)."&delete_topic&topic=$thread->id' onclick='return wpf_confirm();'>".__("Delete Topic", "mingleforum")."</a>";
						$del			= "<small class='adminActions'><br />($strCommands)</small>";
					}
					$out .= "<tr class='$alt'>
									<td class='forumIcon' align='center'>".$this->get_topic_image($thread->id)."</td>
									<td class='wpf-alt'><span class='topicTitle'><a href='"
										.$this->get_threadlink($thread->id)."'>"
										.$this->output_filter($thread->subject)."</a>&nbsp;&nbsp;$image</span> $del
									</td>
									<td>".$this->profile_link($thread->starter)."</td>
									<td class='wpf-alt' align='center'>".( $this->num_posts($thread->id) - 1 )."</td>
									<td class='wpf-alt' align='center'>".$thread->views."</td>
									<td><small>".$this->get_lastpost($thread->id)."</small></td>
								</tr>";
					}
					$out .= "</table></div>";
				$out .= "<table cellpadding='0' cellspacing='0'>
							<tr class='pop_menus'>
								<td width='100%'>".$this->thread_pageing($forum_id)."</td>
								<td>".$this->forum_menu($this->current_group, "bottom")."</td>
							</tr>
						</table>";
			}
			$this->o .= $out;
			$this->footer();
		}
	}

	function get_subject($id){
		global $wpdb;
		return stripslashes($wpdb->get_var("SELECT subject FROM $this->t_threads WHERE id = $id"));
	}

	function showthread($thread_id){
		global $wpdb, $user_ID;
		$this->current_group = $this->forum_get_group_from_post($thread_id);
		$this->current_forum = $this->get_parent_id(THREAD, $thread_id);
		$this->current_thread = $thread_id;
		if(isset($_GET['remove_post']))
			$this->remove_post();
		if(isset($_GET['sticky']))
			$this->sticky_post();
		$this->thread_subscribe();
		if($posts = $this->get_posts($thread_id)){
			if($this->is_thread_subscribed())
				$this->notify_msg = __("Remove this Topic from your email notifications?", "mingleforum");
			else
				$this->notify_msg = __(" This will notify you of all responses to this Topic. Are you sure that is what you want to do?", "mingleforum");
		if(!current_user_can('administrator') && !is_super_admin($user_ID) && !$this->is_moderator($user_ID, $this->current_forum))
			$wpdb->query("UPDATE {$this->t_threads} SET views = views+1 WHERE id = {$thread_id}");
			if($this->is_sticky($thread_id))
				$image = "normal_post_sticky.gif";
			else
				$image = $this->get_topic_image_two($thread_id);
			if(!$this->have_access($this->current_group)){
				wp_die(__("Sorry, but you don't have access to this forum", "mingleforum"));
			}
			$this->header();
			$out = "<table cellpadding='0' cellspacing='0'>
						<tr class='pop_menus'>
							<td width='100%'>".$this->post_pageing($thread_id)."</td>
							<td>".$this->topic_menu($thread_id)."
							</td>
						</tr>
					</table>";
			if ($this->is_closed())
				$meClosed = " ".__("TOPIC CLOSED", "mingleforum")." ";
			else
				$meClosed = "";
			$out .= "<div class='wpf'>
						<table class='wpf-table' width='100%'>
						<tr>
							<th width='100'><img src='{$this->skin_url}/images/topic/{$image}' align='left'/> ".__("Author", "mingleforum")."</th>
							<th>".__("Topic: ", "mingleforum").$this->get_subject($thread_id).$meClosed."</th>
						</tr>
					</table>";
			$out .= "</div>";
			$class = "";
			$c = 0;
			foreach($posts as $post){
				$class = ($class == "wpf-alt")?"":"wpf-alt";
				$user = get_user_meta($post->author_id, "wpf_useroptions", true);
				$out .= "<table class='wpf-post-table' width='100%' id='postid-{$post->id}'>
						<tr class='{$class}'>
							<td valign='top' width='100'>".
							$this->profile_link($post->author_id, true)
								."<div class='wpf-small'>";
                  $out .= $this->get_userrole($post->author_id)."<br />";
                  $out .=__("Posts:", "mingleforum")." ".$this->get_userposts_num($post->author_id)."<br />";
                  $out .= '<a href="#postid-'.$post->id.'">Permalink</a><br />';
				  if (($post->author_id !=  $user_ID) && ($post->author_id))
				    $out .= $this->get_send_message_link($post->author_id);
                  if($this->opt["forum_use_gravatar"])
                    $out .= $this->get_avatar($post->author_id);
							$out .= "</div>".apply_filters('mf_below_post_avatar', '', $post->author_id, $post->id)."</td>
							<td valign='top'>
								<table width='100%' cellspacing='0' cellpadding='0' class='wpf-meta-table'>
									<tr>
										<td class='wpf-meta' valign='top'>".$this->get_postmeta($post->id, $post->author_id)."</td>
									</tr>
									<tr>
										<td valign='top' colspan='2' class='topic_text'>";
										if(!$c)
											$out .= apply_filters('mf_thread_start', '', $this->current_thread, $this->get_threadlink($post->parent_id));
										$out .= apply_filters('mf_before_reply', '', $post->id).make_clickable(convert_smilies(wpautop($this->autoembed($this->output_filter($post->text))))).apply_filters('mf_after_reply', '', $post->id)."</td>
									</tr>";
									if($user['signature'] && $this->options['forum_show_bio']){
										$out .= "<tr><td class='user_desc'><small>".$this->output_filter(make_clickable(convert_smilies(wpautop($user['signature'], true))))."</small></td></tr>";
									}
								$out .= "</table>
							</td>
						</tr>";
						if(!$c)
							$out .= apply_filters('mf_ad_below_first_post', ''); //Adsense Area -- Below First Post
				$out .= "</table>";
				$c += 1;
			}
			$quick_thread = $this->check_parms($_GET['t']);
		//QUICK REPLY AREA
    if(!in_array($this->current_group, $this->options['forum_disabled_cats']) || is_super_admin() || $this->is_moderator($user_ID, $this->current_forum) || $this->options['allow_user_replies_locked_cats'])
    {
      if(!$this->is_closed() && ($user_ID || !$this->options['forum_require_registration'])) {
        $out .= "<table class='wpf-post-table' width='100%' id='wpf-quick-reply'>
          <form action='".WPFURL."wpf-insert.php' name='addform' method='post'>
            <tr>
              <td>";
              $out .= apply_filters('mf_ad_above_quick_reply', ''); //Adsense Area -- Above Quick Reply Form
              $out .= "<strong>".__("Quick Reply", "mingleforum").": </strong><br/>".
                $this->form_buttons()."<br/>
                  <input type='hidden' name='add_post_subject' value='".__('Re:', 'mingleforum')." ".$this->get_subject(floor($quick_thread))."'/>
                  <textarea rows='6' style='width:99% !important;' name='message' class='wpf-textarea' ></textarea>
              </td>
            </tr>";
            $out .= $this->get_quick_reply_captcha();
            $out .= "<tr>
              <td>
                <input type='submit' id='quick-reply-submit' name='add_post_submit' value='".__("Submit Quick Reply", "mingleforum")."' />
                <input type='hidden' name='add_post_forumid' value='".floor($quick_thread)."'/>
                <input type='hidden' name='add_topic_plink' value='".get_permalink($this->page_id)."'/>
              </td>
            </tr>				
          </form>
        </table>";
      }
    }
			$out .= "<table cellpadding='0' cellspacing='0'>
						<tr class='pop_menus'>
							<td width='100%'>".$this->post_pageing($thread_id)."</td>
							<td style='height:30px;'>".$this->topic_menu($thread_id, "bottom")."
							</td>
						</tr>
					</table>";
			$this->o .= $out;
			$this->footer();
		}
	}

	function get_postmeta($post_id, $author_id){
		global $user_ID;
		$image = "<img align='left' src='$this->skin_url/images/post/xx.gif' alt='".__("Post", "mingleforum")."' style='padding-right:10px;'/>";
		$o = "<table width='100%' cellspacing='0' cellpadding='0' style='margin:0; padding:0; border-collapse:collapse;' border='0'>
				<tr>
					<td colspan='3'>$image <strong>".$this->get_postname($post_id)."</strong><br /><small><strong>".__("on:", "mingleforum")."&nbsp;</strong>".$this->get_postdate($post_id)."</small></td></tr><tr>";
          if(!in_array($this->current_group, $this->options['forum_disabled_cats']) || is_super_admin() || $this->is_moderator($user_ID, $this->current_forum) || $this->options['allow_user_replies_locked_cats'])
          {
            if($this->options['forum_use_seo_friendly_urls'])
            {
              if(!$this->is_closed())
                 $o .= "<td nowrap='nowrap' width='10%'><img src='$this->skin_url/images/buttons/quote.gif' alt='' align='left'><a href='$this->post_reply_link&quote=$post_id.$this->curr_page'> ".__("Quote", "mingleforum")."</a></td>";
              if($this->is_moderator($user_ID, $this->current_forum))
                 $o .= "<td nowrap='nowrap' width='10%'><img src='$this->skin_url/images/buttons/delete.gif' alt='' align='left'><a onclick=\"return wpf_confirm();\" href='".$this->thread_link.$this->current_thread."&remove_post&id=$post_id'> ".__("Remove", "mingleforum")."</a></td>";
              if(($this->is_moderator($user_ID, $this->current_forum)) || ($user_ID == $author_id && $user_ID))
                 $o .= "<td nowrap='nowrap' width='10%'><img src='$this->skin_url/images/buttons/modify.gif' alt='' align='left'><a href='".$this->base_url."editpost&id=$post_id&t=$this->current_thread.0'>" .__("Edit", "mingleforum")."</a></td>";
            }
            else
            {
              if(!$this->is_closed())
                 $o .= "<td nowrap='nowrap' width='10%'><img src='$this->skin_url/images/buttons/quote.gif' alt='' align='left'><a href='$this->post_reply_link&quote=$post_id.$this->curr_page'> ".__("Quote", "mingleforum")."</a></td>";
              if($this->is_moderator($user_ID, $this->current_forum))
                 $o .= "<td nowrap='nowrap' width='10%'><img src='$this->skin_url/images/buttons/delete.gif' alt='' align='left'><a onclick=\"return wpf_confirm();\" href='".$this->get_threadlink($this->current_thread)."&remove_post&id=$post_id'> ".__("Remove", "mingleforum")."</a></td>";
              if(($this->is_moderator($user_ID, $this->current_forum)) || ($user_ID == $author_id && $user_ID))
                 $o .= "<td nowrap='nowrap' width='10%'><img src='$this->skin_url/images/buttons/modify.gif' alt='' align='left'><a href='".$this->base_url."editpost&id=$post_id&t=$this->current_thread.0'>" .__("Edit", "mingleforum")."</a></td>";
            }
          }
				$o .= "</tr>
			</table>";
		return $o;
	}

	function get_postdate($post){
		global $wpdb;
		return $this->format_date($wpdb->get_var("select `date` from $this->t_posts where id = $post"));
	}

	function format_date($date){
		if($date)
			return date($this->opt['forum_date_format'], strtotime($date));
		else
			return false;
	}

	function wpf_current_time_fixed( $type, $gmt = 0 ) {
		$t =  ( $gmt ) ? gmdate( 'Y-m-d H:i:s' ) : gmdate( 'Y-m-d H:i:s', ( time() + ( get_option( 'gmt_offset' ) * 3600 ) ) );
		switch ( $type ) {
			case 'mysql':
				return $t;
				break;
			case 'timestamp':
				return strtotime($t);
				break;
		}
	}

	function get_userposts_num($id){
		global $wpdb;
		return $wpdb->get_var("select count(*) from $this->t_posts where author_id = $id");
	}

	function get_post_owner($id) {
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare("SELECT `author_id` FROM {$this->t_posts} WHERE `id` = %d", $id));
	}

	function mydefault(){
		global $user_ID, $wp_rewrite;
		$alt = "";
		if($wp_rewrite->using_permalinks())
			$delim = "?";
		else
			$delim = "&";
		$grs = $this->get_groups();
		$this->header();
		foreach($grs as $g){
			if($this->have_access($g->id)){
				$this->o .= "<div class='wpf'><table width='100%' class='wpf-table forumsList'>";
				$this->o .= "<tr><th colspan='4'><a href='".$this->get_grouplink($g->id)."'>".$this->output_filter($g->name)."</a></th></tr>";
				$frs = $this->get_forums($g->id);
				foreach($frs as $f){
				$alt = ($alt=="alt even")?"odd":"alt even";
					$this->o .= "<tr class='$alt'>";
					$image = "off.gif";
					if($user_ID){
					$lpif = $this->last_poster_in_forum($f->id, true);
						$last_posterid = $this->last_posterid($f->id);
						if($last_posterid != $user_ID){
							$lp = strtotime($lpif); // date
							$lv = strtotime($this->last_visit());
						if($lv < $lp)
							$image = "on.gif";
						else
							$image = "off.gif";
						}
					}
					$this->o .= "
							<td class='wpf-alt forumIcon' width='6%' align='center'><img alt='' src='$this->skin_url/images/$image' /></td>
							<td valign='top'><strong><a href='".$this->get_forumlink($f->id)."'>"
								.$this->output_filter($f->name)."</a></strong><br />"
								.$this->output_filter($f->description);
								if($f->description != "")$this->o .= "<br />";
								$this->o .= $this->get_forum_moderators($f->id)
							."</td>";
					$this->o .= "<td nowrap='nowrap' width='11%' align='left' class='wpf-alt'><small>".__("Topics: ", "mingleforum")."".$this->num_threads($f->id)."<br />".__("Posts: ", "mingleforum").$this->num_posts_forum($f->id)."</small></td>";
					$this->o .= "<td  width='28%' ><small>".$this->last_poster_in_forum($f->id)."</small></td>";
					$this->o .= "</tr>";
				}
			$this->o .= "</table>
			</div><br class='clear'/>";
			}	
		}
		$this->o .= apply_filters('wpwf_new_posts',"<table>
					<tr>
						<td><small><img alt='' align='top' src='$this->skin_url/images/new_some.gif' /> ".__("New posts", "mingleforum")." <img alt='' align='top' src='$this->skin_url/images/new_none.gif' /> ".__("No new posts", "mingleforum")." - <a href='".get_permalink($this->get_pageid()).$delim."markallread=true'>".__("Mark All Read", "mingleforum")."</a></small></td>
					</tr>
				</table><br class='clear'/>");
		$this->footer();
	}

	function vforum($groupid){
		global $user_ID;
		$alt = "";
		$grs = $this->get_groups($groupid);
		$this->current_group = $groupid;
		$this->header();
		foreach($grs as $g){
			if($this->have_access($g->id)){
				$this->o .= "<div class='wpf'><table width='100%' class='wpf-table'>";
				$this->o .= "<tr><th colspan='4'><a href='".$this->get_grouplink($g->id)."'>".$this->output_filter($g->name)."</a></th></tr>";
				$frs = $this->get_forums($g->id);
				foreach($frs as $f){
				$alt = ($alt=="alt even")?"odd":"alt even";
					$this->o .= "<tr class='$alt'>";
					$image = "off.gif";
					if($user_ID){
					$lpif = $this->last_poster_in_forum($f->id, true);
						$last_posterid = $this->last_posterid($f->id);
						if($last_posterid != $user_ID){
							$lp = strtotime($lpif); // date
							$lv = strtotime($this->last_visit());
						if($lv < $lp)
							$image = "on.gif";
						else
							$image = "off.gif";
						}
					}
					$this->o .= "
							<td class='wpf-alt forumIcon' width='6%' align='center'><img alt='' src='$this->skin_url/images/$image' /></td>
							<td valign='top'><strong><a href='".$this->get_forumlink($f->id)."'>"
								.$this->output_filter($f->name)."</a></strong><br />"
								.$this->output_filter($f->description);
								if($f->description != "")$this->o .= "<br />";
								$this->o .= $this->get_forum_moderators($f->id)
							."</td>";
					$this->o .= "<td nowrap='nowrap' width='11%' align='left' class='wpf-alt'><small>".__("Topics: ", "mingleforum")."".$this->num_threads($f->id)."<br />".__("Posts: ", "mingleforum").$this->num_posts_forum($f->id)."</small></td>";
					$this->o .= "<td  width='28%' ><small>".$this->last_poster_in_forum($f->id)."</small></td>";
					$this->o .= "</tr>";
				}
			$this->o .= "</table>
			</div><br class='clear'/>";
			}
		}
		$this->o .= apply_filters('wpwf_new_posts',"<table>
					<tr>
						<td><small><img alt='' align='top' src='$this->skin_url/images/new_some.gif' /> ".__("New posts", "mingleforum")." <img alt='' align='top' src='$this->skin_url/images/new_none.gif' /> ".__("No new posts", "mingleforum")."</small></td>
					</tr>
				</table><br class='clear'/>");
		$this->footer();
	}

	function output_filter($string){
		$parser = new cartpaujBBCodeParser();
		return stripslashes($parser->bbc2html($string));
	}

	function input_filter($string){
		global $wpdb;
		$Find = array("<", "%", "$");
		$Replace = array("&#60;", "&#37;", "&#36;");
		$newStr = str_replace($Find, $Replace, $string);
		return $newStr;
	}

	function sig_input_filter($string){
		global $wpdb;
		$Find = array("<", "%", "$");
		$Replace = array("&#60;", "&#37;", "&#36;");
		$newStr = str_replace($Find, $Replace, $string);
		return $newStr;
	}

	function last_posterid($forum){
		global $wpdb;
		return $wpdb->get_var("SELECT $this->t_posts.author_id FROM $this->t_posts INNER JOIN $this->t_threads ON $this->t_posts.parent_id=$this->t_threads.id WHERE $this->t_threads.parent_id = $forum ORDER BY $this->t_posts.date DESC");
	}

	function last_posterid_thread($thread_id){
		global $wpdb;
		return $wpdb->get_var("SELECT $this->t_posts.author_id FROM $this->t_posts INNER JOIN $this->t_threads ON $this->t_posts.parent_id=$this->t_threads.id WHERE $this->t_posts.parent_id = $thread_id ORDER BY $this->t_posts.date DESC");
	}

	function num_threads($forum){
		global $wpdb;
		return $wpdb->get_var("select count(id) from $this->t_threads where parent_id = $forum ");
	}

	function num_posts_forum($forum){
		global $wpdb;
		return $wpdb->get_var("SELECT count($this->t_posts.id) FROM $this->t_posts INNER JOIN $this->t_threads ON $this->t_posts.parent_id=$this->t_threads.id WHERE $this->t_threads.parent_id = $forum  ORDER BY $this->t_posts.date DESC");
	}

	function num_posts_total(){
		global $wpdb;
		return $wpdb->get_var("select count(id) from $this->t_posts");
	}

	function num_posts($thread_id){
		global $wpdb;
		return $wpdb->get_var("select count(id) from $this->t_posts where parent_id = $thread_id ");
	}

	function num_threads_total(){
		global $wpdb;
		return $wpdb->get_var("select count(id) from $this->t_threads");
	}

	function last_poster_in_forum($forum, $post_date = false){
		global  $wpdb, $table_posts, $profile, $table_threads;
		$date = $wpdb->get_row("SELECT $this->t_posts.date, $this->t_posts.id, $this->t_posts.parent_id, $this->t_posts.author_id FROM $this->t_posts INNER JOIN $this->t_threads ON $this->t_posts.parent_id=$this->t_threads.id WHERE $this->t_threads.parent_id = $forum ORDER BY $this->t_posts.date DESC");
		if($post_date && is_object($date))
			return $date->date;
		if(!$date)
			return __("No topics yet", "mingleforum");
		$d =  date($this->opt['forum_date_format'], strtotime($date->date));
		return "<strong>".__("Last post", "mingleforum")."</strong> ".__("by", "mingleforum")." ".$this->profile_link($date->author_id)
		."<br />".__("in", "mingleforum")." <a href='".$this->get_paged_threadlink($date->parent_id)."#postid-$date->id'>".$this->get_postname($date->id)."</a><br />".__("on", "mingleforum")." $d";
	}

	function last_poster_in_thread($thread_id) {
		global $wpdb;
		return $wpdb->get_var("select `date` from $this->t_posts where parent_id = $thread_id order by `date` DESC");
	}

	function have_access($groupid) {
		global $wpdb, $user_ID;

		if(current_user_can("administrator") || is_super_admin($user_ID))
			return true;

		$user_groups = $wpdb->get_var("select usergroups from {$this->t_groups} where id = {$groupid}");
		$user_groups = maybe_unserialize($user_groups);
		if(!$user_groups)
			return true;

			foreach($user_groups as $user_group) {
	 			if($this->is_user_ingroup($user_ID, $user_group))
	 				return true;
			}
		return false;
	}

	function get_usergroups(){
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM $this->t_usergroups");
	}

	function get_members($usergroup){
		global $wpdb, $table_prefix;
		return $wpdb->get_results("SELECT user_id FROM $this->t_usergroup2user WHERE `group` = $usergroup");
	}

	function is_user_ingroup($user_id = "0", $user_group_id){
		global $wpdb;
		if(!$user_id)
			return false;
		$id = $wpdb->get_var("select user_id from $this->t_usergroup2user where user_id = $user_id and `group` = $user_group_id");
		if($id != "")
			return true;
		return false;
	}

	// TODO
	function setup_header(){
		$this->setup_links();
		global $user_ID;
		if($this->options['forum_use_rss']) { ?>
			<link rel='alternate' type='application/rss+xml' title="<?php echo __("Forums RSS", "mingleforum"); ?>" href="<?php echo $this->global_feed_url;?>" /> <?php }
		if(is_page($this->get_pageid()))
		{
			if($this->ads_options['mf_ad_custom_css'] != "") {
			?>
			<style type="text/css"><?php echo stripslashes($this->ads_options['mf_ad_custom_css']); ?></style>
			<?php } //ENDIF FOR CUSTOM ADS CSS ?>
			<link rel='stylesheet' type='text/css' href="<?php echo "$this->skin_url/style.css";?>"  />
			<script language="JavaScript" type="text/javascript" src="<?php echo WPFURL."js/script.js"?>"></script>
			<script language="JavaScript" type="text/javascript">
			function wpf_confirm(){
				var answer = confirm ('<?php echo __("Are you sure you want to remove this?", "mingleforum");?>');
				if (!answer)
					return false;
				else
					return true;
			}
			</script>
		<?php
		}
	}

	// Some SEO friendly stuff
	function get_pagetitle($bef_title){
		global $wpdb;
		$default_title = " &raquo; ";
		$action = "";
		$title = "";
		if(isset($_GET['mingleforumaction']) && !empty($_GET['mingleforumaction']))
			$action = $_GET['mingleforumaction'];
		elseif($this->options['forum_use_seo_friendly_urls'])
		{
			$uri = $this->get_seo_friendly_query();
			if (!empty($uri) && $uri['action'] && $uri['id'])
			{
				switch($uri['action'])
				{
					case 'group':
						$action = 'vforum';
						$_GET['g'] = $uri['id'];
						break;
					case 'forum':
						$action = 'viewforum';
						$_GET['f'] = $uri['id'];
						break;
					case 'thread':
						$action = 'viewtopic';
						$_GET['t'] = $uri['id'];
						break;
				}
			}
		}
		switch($action){
			case "vforum":
				$title = $default_title.$this->get_groupname($this->check_parms($_GET['g']));
				break;
			case "viewforum":
				$title = $default_title.$this->get_groupname($this->get_parent_id(FORUM, $this->check_parms($_GET['f'])))." &raquo; ".$this->get_forumname($this->check_parms($_GET['f']));
				break;
			case "viewtopic":
				$group = $this->get_groupname($this->get_parent_id(FORUM, $this->get_parent_id(THREAD, $this->check_parms($_GET['t']))));
				$title = $default_title.$group." &raquo; ".$this->get_forumname($this->get_parent_id(THREAD, $this->check_parms($_GET['t'])))." &raquo; ".$this->get_threadname($this->check_parms($_GET['t']));
				break;
			case "search":
				$terms = htmlentities($wpdb->escape($_POST['search_words']), ENT_QUOTES);
				$title = $default_title.__("Search Results", "mingleforum"). " &raquo; {$terms} | ";
				break;
			case "profile":
				$title = $default_title.__("Profile", "mingleforum")."";
				break;
			case "editpost":
				$title = $default_title.__("Edit Post", "mingleforum")."";
				break;
			case "postreply":
				$title = $default_title.__("Post Reply", "mingleforum")."";
				break;
			case "addtopic":
				$title = $default_title.__("New Topic", "mingleforum")."";
				break;
		}
		return $bef_title.$title;
	}

	function set_pagetitle($title){
		return $this->get_pagetitle($title);
	}
	function array_search( $needle, $haystack, $strict = FALSE ){
       	if( !is_array($haystack) )return false;
       		foreach($haystack as $key => $val){
           		if(   (  ( $strict ) && ( $needle === $val )  ) || (  ( !$strict ) && ( $needle == $val )  )   )return $val;
        		}
        return false;
	}

	function get_usergroup_name($usergroup_id){
		global $wpdb, $table_prefix;
		return $wpdb->get_var("SELECT name FROM $this->t_usergroups WHERE id = $usergroup_id");
	}

	function get_usergroup_description($usergroup_id){
		global $wpdb, $table_prefix;
		return $wpdb->get_var("SELECT description FROM $this->t_usergroups WHERE id = $usergroup_id");
	}

	function is_moderator($user_id, $forum_id = ''){
		$user = get_userdata($user_id);
		if($user->user_level >= 9)
			return true;
		$forums = get_user_meta($user_id, 'wpf_moderator', true);
		if(!$forum_id)
			return $forums;
		if($forums == "mod_global")
			return true;
		return $this->array_search( $forum_id, $forums );
	}

	function get_users(){
		global $wpdb, $table_prefix;
		return $wpdb->get_results("SELECT user_login, ID FROM {$wpdb->users} ORDER BY user_login ASC");	
	}

	function get_moderators(){
		global $wpdb, $table_prefix;

		return $wpdb->get_results("
			select $wpdb->usermeta.user_id, $wpdb->users.user_login 
			from 
			$wpdb->usermeta 
			inner join 
			$wpdb->users on $wpdb->usermeta.user_id = $wpdb->users.ID 
			where 
			$wpdb->usermeta.meta_key = 'wpf_moderator' ORDER BY $wpdb->users.user_login ASC");
	}

	function get_forum_moderators($forum_id){
		global $wpdb;
		$out = "";
		$mods = $wpdb->get_results("SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = 'wpf_moderator'");
		foreach($mods as $mod){
			if($this->is_moderator($mod->user_id, $forum_id)){
				$out .= $this->profile_link($mod->user_id).", ";
			}
		}
		$out = substr($out, 0, strlen($out)-2);
		return "<small><i>".__("Moderators:", "mingleforum")." $out</i></small>";
	}

	function wp_forum_install()
	{
		global $table_prefix, $wpdb;
		$table_threads = $table_prefix."forum_threads";
		$table_posts = $table_prefix."forum_posts";
		$table_forums = $table_prefix."forum_forums";
		$table_groups = $table_prefix."forum_groups";
		$table_usergroup2user = $table_prefix."forum_usergroup2user"; 
		$table_usergroups = $table_prefix."forum_usergroups";
		$oldops = get_option('mingleforum_options');

		if($oldops['forum_db_version'] < $this->db_version) //Don't run all the friggin queries if db is already at latest version
		{
			$charset_collate = '';
			if( $wpdb->has_cap('collation'))
			{
				if(!empty($wpdb->charset))
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
				if(!empty($wpdb->collate))
					$charset_collate .= " COLLATE $wpdb->collate";
			}

			$sql1 = "
			CREATE TABLE ". $table_forums." (
			  id int(11) NOT NULL auto_increment,
			  `name` varchar(255) NOT NULL default '',
			  parent_id int(11) NOT NULL default '0',
			  `description` varchar(255) NOT NULL default '',
			  views int(11) NOT NULL default '0',
			  `sort` int( 11 ) NOT NULL default '0',
			  PRIMARY KEY  (id)
			){$charset_collate};";

			$sql2 = "
			CREATE TABLE ". $table_groups." (
			  id int(11) NOT NULL auto_increment,
			  `name` varchar(255) NOT NULL default '',
			  `description` varchar(255) default '',
			  `usergroups` varchar(255) default '',
			  `sort` int( 11 ) NOT NULL default '0',
			  PRIMARY KEY  (id)
			){$charset_collate};";

			$sql3 = "
			CREATE TABLE ". $table_posts." (
			  id int(11) NOT NULL auto_increment,
			  `text` longtext,
			  parent_id int(11) NOT NULL default '0',
			  `date` datetime NOT NULL default '0000-00-00 00:00:00',
			  author_id int(11) NOT NULL default '0',
			  `subject` varchar(255) NOT NULL default '',
			  views int(11) NOT NULL default '0',
			  PRIMARY KEY  (id)
			){$charset_collate};";

			$sql4 = "
			CREATE TABLE ". $table_threads." (
			  id int(11) NOT NULL auto_increment,
			  parent_id int(11) NOT NULL default '0',
			  views int(11) NOT NULL default '0',
			  `subject` varchar(255) NOT NULL default '',
			  `date` datetime NOT NULL default '0000-00-00 00:00:00',
			  `status` varchar(20) NOT NULL default 'open',
			  closed int(11) NOT NULL default '0',
			  mngl_id int(11) NOT NULL default '-1',
			  starter int(11) NOT NULL,
			  `last_post` datetime NOT NULL default '0000-00-00 00:00:00',
			  PRIMARY KEY  (id)
			){$charset_collate};";

			$sql5 = "
				CREATE TABLE ". $table_usergroup2user." (
			  `id` int(11) NOT NULL auto_increment,
			  `user_id` int(11) NOT NULL,
			  `group` varchar(255) NOT NULL,
			  PRIMARY KEY  (`id`)
			){$charset_collate};";

			$sql6 = 
				"CREATE TABLE ". $table_usergroups." (
				  `id` int(11) NOT NULL auto_increment,
				  `name` varchar(255) NOT NULL,
				  `description` varchar(255) default NULL,
				  `leaders` varchar(255) default NULL,
				  PRIMARY KEY  (`id`)
				){$charset_collate};";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			dbDelta($sql1);
			dbDelta($sql2);
			dbDelta($sql3);
			dbDelta($sql4);
			dbDelta($sql5);
			dbDelta($sql6);
			
			$xyquery1="ALTER TABLE ".$table_groups." ADD sort int( 11 ) NOT NULL;";
			$xyquery2="ALTER TABLE ".$table_forums." ADD sort int( 11 ) NOT NULL;";
			$xyquery3="ALTER TABLE ".$table_threads." ADD last_post datetime NOT NULL;";
			$xyquery4="ALTER TABLE ".$table_groups." ADD description varchar(255);";

			$xyquery5="ALTER TABLE ".$table_groups." ADD usergroups varchar(255);";
			$xyquery6="ALTER TABLE ".$table_threads." CHANGE forum_id parent_id int(11);";
			$xyquery7="ALTER TABLE ".$table_posts." CHANGE thread_id parent_id int(11);";
			$xyquery8="ALTER TABLE `".$table_posts."` ADD FULLTEXT ( `text` );";
			$xyquery9="ALTER TABLE ".$table_threads." ADD closed int(11) NOT NULL default '0';";
			$xyquery10="ALTER TABLE ".$table_threads." ADD mngl_id int(11) NOT NULL default '-1';";

			// 1.7.3
			maybe_add_column($table_groups, sort, $xyquery1);
			maybe_add_column($table_forums, sort, $xyquery2);

			// 1.7.5
			maybe_add_column($table_threads, last_post, $xyquery3);

			// 2.0
			maybe_add_column($table_groups, description, $xyquery4);
			maybe_add_column($table_groups, usergroups, $xyquery5);
			maybe_add_column($table_groups, parent_id, $xyquery6);
			maybe_add_column($table_posts,  parent_id, $xyquery7);
			$wpdb->query($xyquery8);

			// Mingle Forum 1.0
			maybe_add_column($table_threads, closed, $xyquery9);
			maybe_add_column($table_threads, mngl_id, $xyquery10);
			
			//Setup the Skin Folder outside of the plugin
			$target_path = ABSPATH.'wp-content/mingle-forum-skins';
			if(!file_exists($target_path))
				@mkdir($target_path."/");

			$oldops['forum_db_version'] = 1;
			update_option('mingleforum_options', $oldops);
		}
		$this->convert_moderators();
	}

	function forum_menu($group, $pos = "top"){
		global $user_ID;
		$menu = "";
		if($user_ID || $this->allow_unreg()){	
			if($pos == "top")
				$class = "mirrortab";
			else
				$class= "maintab";

			$menu = "<table cellpadding='0' cellspacing='0' style='margin-right:10px;' id='forummenu'>";
			$menu .= "<tr>
							<td class='".$class."_first'>&nbsp;</td>
							<td valign='top' class='".$class."_back' nowrap='nowrap'><a href='".$this->get_addtopic_link()."'>".__("New Topic", "mingleforum")."</a></td>";
			if($user_ID)
			{
				if($this->is_forum_subscribed()) //Check if user has already subscribed to topic
					$menu .= "<td class='".$class."_back' nowrap='nowrap'><a onclick='return notify();' href='".$this->forum_link.$this->current_forum."&forumsubs'>".__("Unsubscribe", "mingleforum")."</a></td>";
				else
					$menu .= "<td class='".$class."_back' nowrap='nowrap'><a onclick='return notify();' href='".$this->forum_link.$this->current_forum."&forumsubs'>".__("Subscribe", "mingleforum")."</a></td>";
			}
			$menu .= "<td valign='top' class='".$class."_last'>&nbsp;&nbsp;</td>
				</tr>
				</table>";
		}
		return $menu;
	}

	function topic_menu($thread, $pos = "top"){
		global $user_ID;
		$menu = "";
		$stick = "";
		$closed = "";
		if($user_ID || $this->allow_unreg()){
			if($pos == "top"){
				$class = "mirrortab";
			}else{
				$class = "maintab";
			}
			if($this->is_moderator($user_ID, $this->current_forum)){
				if($this->options['forum_use_seo_friendly_urls'])
				{
					if($this->is_sticky()){
						$stick = "<td class='".$class."_back' nowrap='nowrap'><a href='".$this->thread_link.$this->current_thread.".".$this->curr_page."&sticky&id=$this->current_thread'>".__("Undo Sticky", "mingleforum")."</a></td>";
					}else{
						$stick = "<td class='".$class."_back' nowrap='nowrap'><a href='".$this->thread_link.$this->current_thread.".".$this->curr_page."&sticky&id=$this->current_thread'>".__("Sticky", "mingleforum")."</a></td>";
					}
					if($this->is_closed()){
						$closed = "<td class='".$class."_back' nowrap='nowrap'><a href='".$this->thread_link.$this->current_thread.".".$this->curr_page."&closed=0&id=$this->current_thread'>".__("Re-open", "mingleforum")."</a></td>";
					}else{
						$closed = "<td class='".$class."_back' nowrap='nowrap'><a href='".$this->thread_link.$this->current_thread.".".$this->curr_page."&closed=1&id=$this->current_thread'>".__("Close", "mingleforum")."</a></td>";
					}
				}
				else
				{
					if($this->is_sticky()){
						$stick = "<td class='".$class."_back' nowrap='nowrap'><a href='".$this->get_threadlink($this->current_thread)."&sticky&id=$this->current_thread'>".__("Undo Sticky", "mingleforum")."</a></td>";
					}else{
						$stick = "<td class='".$class."_back' nowrap='nowrap'><a href='".$this->get_threadlink($this->current_thread)."&sticky&id=$this->current_thread'>".__("Sticky", "mingleforum")."</a></td>";
					}
					if($this->is_closed()){
						$closed = "<td class='".$class."_back' nowrap='nowrap'><a href='".$this->get_threadlink($this->current_thread)."&closed=0&id=$this->current_thread'>".__("Re-open", "mingleforum")."</a></td>";
					}else{
						$closed = "<td class='".$class."_back' nowrap='nowrap'><a href='".$this->get_threadlink($this->current_thread)."&closed=1&id=$this->current_thread'>".__("Close", "mingleforum")."</a></td>";
					}
				}
			}
			$menu .= "<table cellpadding='0' cellspacing='0' style='margin-right:10px;' id='topicmenu'>";
			$menu .= "<tr><td class='".$class."_first'>&nbsp;</td>";
      if(!in_array($this->current_group, $this->options['forum_disabled_cats']) || is_super_admin() || $this->is_moderator($user_ID, $this->current_forum) || $this->options['allow_user_replies_locked_cats'])
      {
        if(!$this->is_closed())
          $menu .= "<td valign='top' class='".$class."_back' nowrap='nowrap'><a href='".$this->get_post_reply_link()."'>".__("Reply", "mingleforum")."</a></td>";
      }
			if($user_ID)
			{
				if($this->is_thread_subscribed()) //Check if user has already subscribed to topic
					$menu .= "<td class='".$class."_back' nowrap='nowrap'><a onclick='return notify();' href='".$this->thread_link.$this->current_thread.".".$this->curr_page."&threadsubs'>".__("Unsubscribe", "mingleforum")."</a></td>";
				else
					$menu .= "<td class='".$class."_back' nowrap='nowrap'><a onclick='return notify();' href='".$this->thread_link.$this->current_thread.".".$this->curr_page."&threadsubs'>".__("Subscribe", "mingleforum")."</a></td>";
			}
			if($this->options['forum_use_rss'])
				$menu .= "<td class='".$class."_back' nowrap='nowrap'><a href='$this->topic_feed_url"."$this->current_thread'>".__("RSS feed", "mingleforum")."</a></td>";
			$menu .= $stick.$closed."
			<td valign='top' class='".$class."_last'>&nbsp;&nbsp;</td>
			</tr></table>";
		}
		return $menu;
	}

	function setup_menu(){
		global $user_ID;
		$this->setup_links();

		if(isset($_GET['closed']))
			$this->closed_post();
		//START MINGLE MY PROFILE LINK
		if(!function_exists('is_plugin_active'))
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active('mingle/mingle.php'))
		{
			$MnglUser = get_userdata($user_ID);
			global $mngl_options;
			$myProfURL2 = '';
			if(isset($mngl_options->profile_page_id) and $mngl_options->profile_page_id != 0)
			{
				if( MnglUtils::rewriting_on() and $mngl_options->pretty_profile_urls )
				{
					global $mngl_blogurl;
					$struct = MnglUtils::get_permalink_pre_slug_uri();
					$myProfURL2 = "{$mngl_blogurl}{$struct}{$MnglUser->user_login}";
				}
				else
				{
					$permalink = get_permalink($mngl_options->profile_page_id);
					$param_char = ((preg_match("#\?#",$permalink))?'&':'?');
					$myProfURL2 = "{$permalink}{$param_char}u={$MnglUser->user_login}";
				}
			}
			$link = "<a id='user_button' href='" . $myProfURL2 . "' title='".__("My profile", "mingleforum")."'>".__("My Profile", "mingleforum")."</a>";
		}
		else
		{
			$link = "<a id='user_button' href='".$this->base_url."profile&id=$user_ID' title='".__("My profile", "mingleforum")."'>".__("My Profile", "mingleforum")."</a>";
		}
		//END MINGLE MY PROFILE LINK

		if($this->options['forum_use_seo_friendly_urls'])
		{
			$menuitems = array(
								"home" 	    => "<a id='home_button' href='".$this->home_url."'>".__("Forum Home", "mingleforum")."</a>",
								"profile" 	=> $link,
								"move" 		=> "<a href='".$this->forum_link.$this->current_forum.".".$this->curr_page."&getNewForumID&topic=$this->current_thread'>".__("Move Topic", "mingleforum")."</a>"
				);
		}
		else
		{
			$menuitems = array(
							"home" 	    => "<a id='home_button' href='".$this->home_url."'>".__("Forum Home", "mingleforum")."</a>",
							"profile" 	=> $link,
							"move" 		=> "<a href='".$this->get_forumlink($this->current_forum)."&getNewForumID&topic=$this->current_thread'>".__("Move Topic", "mingleforum")."</a>");
		}

		$menu = "";
		if($user_ID || $this->allow_unreg()){
			$menu .= "<table cellpadding='0' cellspacing='5' id='wp-mainmenu'><tr>";
			$menu .= "<td valign='top' class='menu_sub'>{$menuitems['home']}</td>";
			if($user_ID)
				$menu .= "<td valign='top' class='menu_sub'>{$menuitems['profile']}</td>";

			switch($this->current_view){
				case THREAD:
					if($this->is_moderator($user_ID, $this->current_forum)){
						$menu .= "<td valign='top' class='menu_sub'>{$menuitems['move']}</td>";
					}
			}
			$menu .= "</tr></table>";
		}
		return $menu;			
	}

	function convert_moderators(){
		global $wpdb, $table_prefix;
		if(!get_option('wpf_mod_option_vers')){
			$mods = $wpdb->get_results("SELECT user_id, user_login, meta_value FROM $wpdb->usermeta 
				INNER JOIN $wpdb->users ON $wpdb->usermeta.user_id=$wpdb->users.ID WHERE meta_key = 'moderator' AND meta_value <> ''");

			foreach($mods as $mod){
				$string = explode(",", substr_replace($mod->meta_value, "", 0, 1));

				update_user_meta($mod->user_id, 'wpf_moderator', maybe_serialize($string));
			}
			update_option('wpf_mod_option_vers', '2');	
		}		
	}

	function login_form(){
		global $user_ID;
		if($user_ID)
			$user = get_userdata($user_ID);
		$login_msg = "";
		if ($user_ID)
			$login_msg = "<p>".__("You are logged in as:", "mingleforum")." ".$user->user_login."</p>";
		else
			$login_msg = "";

		if(!is_user_logged_in() && $this->options['forum_show_login_form']){
			return "<form action='".site_url()."/wp-login.php' method='post'>
				<label style='font-size:100%;' for='log'>".__("Username: ", "mingleforum")."</label><br /><input type='text' name='log' id='log' value='' size='20' class='wpf-input'/><br />
				<label style='font-size:100%;' for='pwd'>".__("Password: ", "mingleforum")."</label><br /><input type='password' name='pwd' id='pwd' size='20' class='wpf-input'/> 
				<input type='submit' name='submit' value='Login' id='wpf-login-button' class='button' /><br />
				<label style='font-size:100%;' for='rememberme'><input name='rememberme' id='rememberme' type='checkbox' checked='checked' value='forever' /> ".__("Remember Me", "mingleforum")."</label>
				
				<input type='hidden' name='redirect_to' value='".$_SERVER['REQUEST_URI']."'/>
			</form>";
		}
		else
			return $login_msg;
	}

	// function pre($array){
		// echo "<pre>";
		// print_r($array);
		// echo "</pre>";
	// }

	function print_curr(){
		$this->o .= "<p>Group: $this->current_group<br>
				Forum: $this->current_forum<br>
				Thread: $this->current_thread</p>";
	}

	function get_parent_id($type, $id){
		global $wpdb;
		switch($type){
			case FORUM:
				return $wpdb->get_var("select parent_id from $this->t_forums where id = $id"); 
				break;
			case THREAD:
				return $wpdb->get_var("select parent_id from $this->t_threads where id = $id"); 
				break;
		}
	}
	// TODO
	function get_userrole($user_id){
		$user = get_userdata($user_id);
		if($user->user_level >= 9)
			return __("Administrator", "mingleforum");
		if(!$user_id)
			return ""; //User is a guest
		if($this->is_moderator($user_id, $this->current_forum))
			return __("Moderator", "mingleforum");
		else
		{
			$mePosts = $this->get_userposts_num($user_id);
			if ($mePosts < $this->opt['level_one'])
				return __($this->opt['level_newb_name'], "mingleforum");
			if ($mePosts < $this->opt['level_two'])
				return __($this->opt['level_one_name'], "mingleforum");
			if ($mePosts < $this->opt['level_three'])
				return __($this->opt['level_two_name'], "mingleforum");
			else
				return __($this->opt['level_three_name'], "mingleforum");
		}
	}

/**************************************************/
	function forum_get_group_id($group){
		global $wpdb, $table_groups;
		return $wpdb->get_var("SELECT id FROM $this->t_groups WHERE id = $group");
	}
	function forum_get_parent($forum){
		global $wpdb, $table_forums;
		return $wpdb->get_var("SELECT parent_id FROM $this->t_forums WHERE id = $forum");
	}
	function forum_get_forum_from_post($thread){
		global $wpdb, $table_threads;
		return $wpdb->get_var("SELECT parent_id FROM $this->t_threads WHERE id = $thread");
	}
	function forum_get_group_from_post($thread_id){
		return $this->forum_get_group_id($this->forum_get_parent($this->forum_get_forum_from_post($thread_id)));
	}
/****************************************************/

	function trail(){
	global $wpdb;
		$this->setup_links();

		$trail = "<a href='".get_permalink($this->page_id)."'>".__("Forum", "mingleforum")."</a>";

		if($this->current_group)
			if($this->options['forum_use_seo_friendly_urls'])
			{
				$group = $this->get_seo_friendly_title($this->get_groupname($this->current_group))."-group".$this->current_group;
				$trail .= " <strong>&raquo;</strong> <a href='".rtrim($this->home_url, '/').'/'.$group.".0'>".$this->get_groupname($this->current_group)."</a>";
			}
			else
				$trail .= " <strong>&raquo;</strong> <a href='$this->base_url"."vforum&g=$this->current_group.0'>".$this->get_groupname($this->current_group)."</a>";

		if($this->current_forum)
			if ($this->options['forum_use_seo_friendly_urls'])
			{
				$group = $this->get_seo_friendly_title($this->get_groupname($this->get_parent_id(FORUM, $this->current_forum))."-group".$this->get_parent_id(FORUM, $this->current_forum));
				$forum = $this->get_seo_friendly_title($this->get_forumname($this->current_forum)."-forum".$this->current_forum);
				$trail .= " <strong>&raquo;</strong> <a href='".rtrim($this->home_url, '/').'/'.$group.'/'.$forum.".0'>".$this->get_forumname($this->current_forum)."</a>";
			}
			else
				$trail .= " <strong>&raquo;</strong> <a href='$this->base_url"."viewforum&f=$this->current_forum.0'>".$this->get_forumname($this->current_forum)."</a>";

		if($this->current_thread)
			if ($this->options['forum_use_seo_friendly_urls'])
			{
				$group = $this->get_seo_friendly_title($this->get_groupname($this->get_parent_id(FORUM, $this->get_parent_id(THREAD, $this->current_thread)))."-group".$this->get_parent_id(FORUM, $this->get_parent_id(THREAD, $this->current_thread)));
				$forum = $this->get_seo_friendly_title($this->get_forumname($this->get_parent_id(THREAD, $this->current_thread))."-forum".$this->get_parent_id(THREAD, $this->current_thread));
				$thread = $this->get_seo_friendly_title($this->get_threadname($this->current_thread)."-thread".$this->current_thread);
				$trail .= " <strong>&raquo;</strong> <a href='".rtrim($this->home_url, '/').'/'.$group.'/'.$forum.'/'.$thread.".0'>".$this->get_threadname($this->current_thread)."</a>";
			}
			else
				$trail .= " <strong>&raquo;</strong> <a href='$this->base_url"."viewtopic&t=$this->current_thread.0'>".$this->get_threadname($this->current_thread)."</a>";

		if($this->current_view == NEWTOPICS)
			$trail .= " <strong>&raquo;</strong> ".__("New Topics since last visit", "mingleforum");

		if($this->current_view == SEARCH){
			$terms = "";
			if(isset($_POST['search_words']))
				$terms = htmlentities($wpdb->escape($_POST['search_words']), ENT_QUOTES);
			$trail .= " <strong>&raquo;</strong> ".__("Search Results", "mingleforum")." &raquo; $terms";
		}

		if($this->current_view == PROFILE)
			$trail .= " <strong>&raquo;</strong> ".__("Profile Info", "mingleforum");

		if($this->current_view == POSTREPLY)
			$trail .= " <strong>&raquo;</strong> ".__("Post Reply", "mingleforum");

		if($this->current_view == EDITPOST)
			$trail .= " <strong>&raquo;</strong> ".__("Edit Post", "mingleforum");

		if($this->current_view == NEWTOPIC)
			$trail .= " <strong>&raquo;</strong> ".__("New Topic", "mingleforum");

		return apply_filters('mf_ad_above_breadcrumbs', '')."<p id='trail' class='breadcrumbs'>$trail</p>"; //Adsense Area -- Above Breadcrumbs
	}

	function last_visit(){
		global $user_ID;
		if($user_ID)
			return $_COOKIE['wpmfcookie'];
		else
			return "0000-00-00 00:00:00";
	}

	function set_cookie()
	{
		global $user_ID;
		if($user_ID && !isset($_COOKIE['wpmfcookie']))
		{
			$last = get_user_meta($user_ID, 'lastvisit', true);
			setcookie("wpmfcookie", $last, 0, "/");
			update_user_meta($user_ID, 'lastvisit', $this->wpf_current_time_fixed('mysql', 0));
		}
	}

	function markallread()
	{
		global $user_ID;
		if($user_ID)
		{
			update_user_meta($user_ID, 'lastvisit', $this->wpf_current_time_fixed('mysql', 0));
			$last = get_user_meta($user_ID, 'lastvisit', true);
			setcookie("wpmfcookie", $last, 0, "/");
		}
	}

	function get_avatar($user_id, $size = 60){

		if($this->opt['forum_use_gravatar'] == 'true')
			return get_avatar($user_id, $size);
		else
			return "";
	}

	function header(){
		global $user_ID, $wpdb, $mingleforum;
		$avatar = "";
		$this->setup_links();
		if(!function_exists('is_plugin_active'))
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active('mingle/mingle.php'))
		{
			$ProfLinky = "";
		}
		else
		{
			$ProfLinky = "<a href='".site_url("wp-admin/profile.php") . "'>".__("Edit Profile", "mingleforum")."<br />";
		}

		if($user_ID){
			$welcome = __("Welcome", "mingleforum")." ".$this->get_userdata($user_ID, $this->options['forum_display_name']);
			$meta = "<div style='float:left'>".__("Your last visit was:", "mingleforum")." ".$this->format_date($this->last_visit())."<br />";
			$meta .= $this->get_inbox_link();
			$meta .= "<a href='".$this->base_url."shownew'>".__("Show new topics since your last visit", "mingleforum")."</a><br />";
			$meta .= "<a href='".$this->base_url."editprofile&user_id=$user_ID'>".__("Edit your forum options", "mingleforum")."</a><br />";
			$meta .= $ProfLinky;
			$meta .= "<a href='".wp_nonce_url( site_url("wp-login.php?action=logout&redirect_to=".get_permalink($this->get_pageid()), 'login'), 'log-out' )."'>".__("Log out", "mingleforum")."</a></div>";
			$avatar = "<td class='wpf-alt' width='6%'>".$this->get_avatar($user_ID, 60)."</td>";
			$colspan = "colspan = '2'";
		}
		else{
			$meta = apply_filters('wpwf_guest_welcome_msg', "<p>".__("Welcome Guest, please login or", "mingleforum")." "."<a href='$this->reg_link'>".__("register.", "mingleforum")."</a></p>".$this->login_form()); //--weaver--
			$welcome = __("Welcome", "mingleforum"). " <strong>".$this->get_userdata($user_ID, $this->options['forum_display_name'])."</strong>";
			$colspan = "";
		}
		if(!$user_ID && !$this->allow_unreg()){
			$meta = "<p>".__("Welcome Guest, posting in this forum requires", "mingleforum")." <a href='$this->reg_link'>".__("registration.", "mingleforum")."</a></p>".$this->login_form();
			$colspan = "";
		}
		$o = "<div class='wpf'>

				<table width='100%' class='wpf-table' id='profileHeader'>
					<tr>
						<th $colspan ><h4 style='float:left;'>$welcome&nbsp;</h4>
						<a id='upshrink' style='float:right;' href='#' onclick='shrinkHeader(!current_header); return false;'>".__("Show/Hide Header", "mingleforum")."</a>
						</th>
					</tr>

					<tr id='upshrinkHeader'>
						$avatar
						<td valign='top'>$meta</td>
					</tr>

					<tr id='upshrinkHeader2'>
						<th class='wpf-bright' $colspan align='right'>
							<div>
								<form name='wpf_search_form' method='post' action='$this->base_url"."search'>
									<input type='text' name='search_words' class='wpf-input' />
									<input type='submit' id='wpf-search-submit' name='search_submit' value='".__("Search forums", "mingleforum")."' />
								</form>
							</div>
						</th>
					</tr>
				</table>
			</div>";
		$o .= $this->setup_menu();
		$o .= $this->trail();
		$this->o .= $o;
	}

/*	function get_pagelinks($thread_id){
		global $wpdb;

		$pages = $wpdb->get_results("SELECT * FROM $this->t_posts WHERE parent_id = $thread_id");

		if(count($pages) > $this->opt['forum_posts_per_page']){
			$num_pages = ceil(count($pages)/$this->opt['forum_posts_per_page']);

			for($i = 0; $i < $num_pages; ++$i){
				if($this->options['forum_use_seo_friendly_urls'])
					$out .= " <a href='".$this->get_threadlink($thread_id).".".$i."'>".($i+1)."</a>";
				else
					$out .= " <a href='".$this->thread_link.$thread_id.".".$i."'>".($i+1)."</a>";
			}
			return " &laquo; $out &raquo;";
		}
		else
			return "";
	}
DISABLED THIS IN 1.0.25*/

	function post_pageing($thread_id){
		global $wpdb;
		$out =  __("Pages:", "mingleforum");
		$count = $wpdb->get_var("SELECT count(*) FROM $this->t_posts WHERE parent_id = $thread_id");
		$num_pages = ceil($count/$this->opt['forum_posts_per_page']);
		if($num_pages <= 6) {
			for($i = 0; $i < $num_pages; ++$i){
				if($i ==  $this->curr_page)
					$out .= " [<strong>".($i+1)."</strong>]";
				else
					$out .= " <a href='".$this->get_threadlink($this->current_thread, ".".$i)."'>".($i+1)."</a>";
			}
		}
		else {
			if($this->curr_page >= 4)
				$out .= " <a href='".$this->get_threadlink($this->current_thread)."'>".__("First", "mingleforum")."</a> << ";
			for($i = 3; $i > 0; $i--) {
				if((($this->curr_page + 1) - $i) > 0)
					$out .= " <a href='".$this->get_threadlink($this->current_thread, ".".($this->curr_page - $i))."'>".(($this->curr_page + 1) - $i)."</a>";
			}
			$out .= " [<strong>".($this->curr_page + 1)."</strong>]";
			for($i = 1; $i <= 3; $i++) {
				if((($this->curr_page + 1) + $i) <= $num_pages)
					$out .= " <a href='".$this->get_threadlink($this->current_thread, ".".($this->curr_page + $i))."'>".(($this->curr_page + 1) + $i)."</a>";
			}
			if($num_pages - $this->curr_page >= 5)
				$out .= " >> <a href='".$this->get_threadlink($this->current_thread, ".".($num_pages-1))."'>".__("Last", "mingleforum")."</a>";
		}
		return "<span class='wpf-pages'>".$out."</span>";
	}

	function thread_pageing($forum_id){
		global $wpdb;
		$out = __("Pages:", "mingleforum");
		$count = $wpdb->get_var("SELECT count(*) FROM $this->t_threads WHERE parent_id = $forum_id AND `status` <> 'sticky'");
		$num_pages = ceil($count/$this->opt['forum_threads_per_page']);
		if($num_pages <= 6) {
			for($i = 0; $i < $num_pages; ++$i){
				if($i ==  $this->curr_page)
					$out .= " [<strong>".($i+1)."</strong>]";
				else
					$out .= " <a href='".$this->get_forumlink($this->current_forum, '.'.$i)."'>".($i+1)."</a>";
			}
		}
		else {
			if($this->curr_page >= 4)
				$out .= " <a href='".$this->get_forumlink($this->current_forum, ".0")."'>".__("First", "mingleforum")."</a> << ";
			for($i = 3; $i > 0; $i--) {
				if((($this->curr_page + 1) - $i) > 0)
					$out .= " <a href='".$this->get_forumlink($this->current_forum, ".".($this->curr_page - $i))."'>".(($this->curr_page + 1) - $i)."</a>";
			}
			$out .= " [<strong>".($this->curr_page + 1)."</strong>]";
			for($i = 1; $i <= 3; $i++) {
				if((($this->curr_page + 1) + $i) <= $num_pages)
					$out .= " <a href='".$this->get_forumlink($this->current_forum, ".".($this->curr_page + $i))."'>".(($this->curr_page + 1) + $i)."</a>";
			}
			if($num_pages - $this->curr_page >= 5)
				$out .= " >> <a href='".$this->get_forumlink($this->current_forum, ".".($num_pages-1))."'>".__("Last", "mingleforum")."</a>";
		}
		return "<span class='wpf-pages'>".$out."</span>";
	}

	function remove_topic(){
		global $user_ID, $wpdb;
		$topic = $_GET['topic'];
		if($this->is_moderator($user_ID, $this->current_forum)){
			//DELETE MINGLE ENTRY AS WELL
			if(!function_exists('is_plugin_active'))
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if(is_plugin_active('mingle/mingle.php') and is_user_logged_in())
			{
				$board_post =& MnglBoardPost::get_stored_object();
				$myDelID = $wpdb->get_var($wpdb->prepare("SELECT `mngl_id` FROM {$this->t_threads} WHERE id = %d", $topic));
				if ($myDelID > 0)
					$board_post->delete( $myDelID );
			}
			//END DELETE MINGLE ENTRY
			$wpdb->query($wpdb->prepare("DELETE FROM {$this->t_posts} WHERE `parent_id` = %d", $topic));
			$wpdb->query($wpdb->prepare("DELETE FROM {$this->t_threads} WHERE `id` = %d", $topic));
		}else{
			wp_die(__("An unknown error has occured. Please try again.", "mingleforum"));
		}	
	}

	function getNewForumID(){
		global $user_ID, $wpdb;
		$topic = !empty($_GET['topic']) ? (int)$_GET['topic'] : 0;
		$topic = !empty($_GET['t']) ? (int)$_GET['t'] : $topic;
		if($this->is_moderator($user_ID, $this->current_forum)){
			$currentForumID = $this->check_parms($_GET['f']);
			$strOUT = '
			<form id="" method="post" action="'.$this->base_url.'viewforum&f='.$currentForumID.'&move_topic&topic='.$topic.'">
			Move "<strong>'.$this->get_subject($topic).'</strong>" to new forum: <select id="newForumID" name="newForumID" onchange="location=\''.$this->base_url.'viewforum&f='.$currentForumID.'&move_topic&topic='.$topic.'&newForumID=\'+this.options[this.selectedIndex].value">';
			$frs = $this->get_forums();
			foreach($frs as $f){
				$strOUT .= '
				<option value="'.$f->id.'"'.($f->id==$currentForumID ? ' selected="selected"' : '').'>'.$f->name.'</option>';
			}
			$strOUT .= '
			</select>
			<noscript><input type="submit" value="Go!" /></noscript>
			</form>';

			return $strOUT;
		}else{
			wp_die(__("An unknown error has occured. Please try again.", "mingleforum"));
		}
	}
  
	function move_topic(){
		global $user_ID, $wpdb;
		$topic = $_GET['topic'];
		$currentForumID = $this->check_parms($_GET['f']);
		$newForumID = !empty($_GET['newForumID']) ? (int)$_GET['newForumID'] : 0;
		$newForumID = !empty($_POST['newForumID']) ? (int)$_POST['newForumID'] : $newForumID;
		if($this->is_moderator($user_ID, $this->current_forum)){
			$strSQL = $wpdb->prepare("UPDATE {$this->t_threads} SET `parent_id` = {$newForumID} WHERE id = %d", $topic);
			$wpdb->query($strSQL);
			@header("location: ".$this->base_url."viewforum&f=".$newForumID);
			@exit;
		}else{
			wp_die(__("An unknown error has occured. Please try again.", "mingleforum"));
		}	
	}
  
	function remove_post(){
		global $user_ID, $wpdb;
		$id = (isset($_GET['id']) && is_numeric($_GET['id']))?$_GET['id']:0;
		$author = $wpdb->get_var($wpdb->prepare("SELECT author_id from {$this->t_posts} where id = %d"), $id);
    
		$del = "fail";
		if(current_user_can("administrator") || is_super_admin($user_ID))
			$del = "ok";
		if($this->is_moderator($user_ID, $this->current_forum))
			$del = "ok";
		if($user_ID ==  $author)
			$del = "ok";
    
		if($del == "ok"){
			$wpdb->query($wpdb->prepare("DELETE FROM {$this->t_posts} WHERE id = %d", $id));
			$this->o .= "<div class='updated'>".__("Post deleted", "mingleforum")."</div>";		
		}else{
			wp_die(__("An unknown error has occured. Please try again.", "mingleforum"));
		}
	}
  
	function sticky_post(){
		global $user_ID, $wpdb;
		if(current_user_can("administrator") || is_super_admin($user_ID)){
			if(!$this->is_moderator($user_ID, $this->current_forum)){
				wp_die(__("An unknown error has occured. Please try again.", "mingleforum"));
				}
		}
		$id = (isset($_GET['id']) && is_numeric($_GET['id']))?$_GET['id']:0;
		$status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$this->t_threads} WHERE id = %d", $id));
    
		switch($status){
			case 'sticky': 
				$wpdb->query($wpdb->prepare("UPDATE {$this->t_threads} SET status = 'open' WHERE id = %d", $id));
				break;
			case 'open': 
				$wpdb->query($wpdb->prepare("UPDATE {$this->t_threads} SET status = 'sticky' WHERE id = %d", $id));
				break;
		}
	}

	function forum_subscribe()
	{
		global $user_ID;
		if(isset($_GET['forumsubs']) && $user_ID)
		{
			$useremail = $this->get_userdata($user_ID, 'user_email');
			if(!empty($useremail))
			{
				$list = get_option("mf_forum_subscribers_".$this->current_forum, array());
				if($this->is_forum_subscribed()) //remove user if already exists (user clicked unsubscribe)
				{
					$key = array_search($useremail, $list);
					unset($list[$key]);
				}
				else
					$list[] = $useremail;
				update_option("mf_forum_subscribers_".$this->current_forum, $list);
			}
		}
	}

	function is_forum_subscribed()
	{
		global $user_ID;
		if($user_ID)
		{
			$useremail = $this->get_userdata($user_ID, 'user_email');
			$list = get_option("mf_forum_subscribers_".$this->current_forum, array());
			if(in_array($useremail, $list))
				return true;
		}
		return false;
	}

	function get_subscribed_forums()
	{
		global $user_ID, $wpdb;
		$results = array();
		$email = $this->get_userdata($user_ID, 'user_email');
		$forums = $wpdb->get_results("SELECT id FROM {$this->t_forums}");
		if(!empty($forums))
			foreach($forums as $f)
			{
				$list = get_option("mf_forum_subscribers_".$f->id, array());
				if(in_array($email, $list))
					$results[] = $f->id;
			}
		return $results;
	}

	function thread_subscribe()
	{
		global $user_ID;
		if(isset($_GET['threadsubs']) && $user_ID)
		{
			$useremail = $this->get_userdata($user_ID, 'user_email');
			if(!empty($useremail))
			{
				$list = get_option("mf_thread_subscribers_".$this->current_thread, array());
				if($this->is_thread_subscribed()) //remove user if already exists (user clicked unsubscribe)
				{
					$key = array_search($useremail, $list);
					unset($list[$key]);
				}
				else
					$list[] = $useremail;
				update_option("mf_thread_subscribers_".$this->current_thread, $list);
			}
		}
	}

	function is_thread_subscribed()
	{
		global $user_ID;
		if($user_ID)
		{
			$useremail = $this->get_userdata($user_ID, 'user_email');
			$list = get_option("mf_thread_subscribers_".$this->current_thread, array());
			if(in_array($useremail, $list))
				return true;
		}
		return false;
	}

	function get_subscribed_threads()
	{
		global $user_ID, $wpdb;
		$results = array();
		$email = $this->get_userdata($user_ID, 'user_email');
		$threads = $wpdb->get_results("SELECT id FROM {$this->t_threads}");
		if(!empty($threads))
			foreach($threads as $t)
			{
				$list = get_option("mf_thread_subscribers_".$t->id, array());
				if(in_array($email, $list))
					$results[] = $t->id;
			}
		return $results;
	}

	function is_sticky($thread_id = ''){
		global $wpdb;
		if($thread_id){
			$id = $thread_id;
		}else{
			$id = $this->current_thread;
		}
		$status = $wpdb->get_var("select status from $this->t_threads where id = $id");
		if($status == "sticky")
		 	return true;
		else
			return false;
	}

	function closed_post(){
		global $user_ID, $wpdb;
		if(current_user_can("administrator") || is_super_admin($user_ID)){
			if(!$this->is_moderator($user_ID, $this->current_forum)){
				wp_die(__("An unknown error has occured. Please try again.", "mingleforum"));
			}
		}
		$strSQL = "UPDATE {$this->t_threads} SET closed = %d WHERE id = %d";
		$wpdb->query($wpdb->prepare($strSQL, (int)$_GET['closed'], (int)$_GET['id']));
	}
  
	function is_closed($thread_id = ''){
		global $wpdb;
		if($thread_id){
			$id = $thread_id;
		}else{
			$id = $this->current_thread;
		}
		$strSQL = $wpdb->prepare("SELECT closed FROM {$this->t_threads} WHERE id = %d", $id);
		$closed = $wpdb->get_var($strSQL);
		if($closed){
			return true;
		}else{
			return false;
		}
	}

	function allow_unreg(){
		if($this->opt['forum_require_registration'] == false)
			return true;
		return false;
	}

	function profile_link($user_id, $toWrap = false){
		if($toWrap)
			$user = wordwrap($this->get_userdata($user_id, $this->options['forum_display_name']), 10, "-<br/>", 1);
		else
			$user = $this->get_userdata($user_id, $this->options['forum_display_name']);
		//START MINGLE PROFILE LINKS
		if(!function_exists('is_plugin_active'))
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active('mingle/mingle.php'))
		{
			$MnglUser = get_userdata($user_id);
			global $mngl_options;
			$myProfURL3 = '';
			if(isset($mngl_options->profile_page_id) and $mngl_options->profile_page_id != 0)
			{
				if( MnglUtils::rewriting_on() and $mngl_options->pretty_profile_urls )
				{
					global $mngl_blogurl;
					$struct = MnglUtils::get_permalink_pre_slug_uri();
					$myProfURL3 = "{$mngl_blogurl}{$struct}{$MnglUser->user_login}";
				}
				else
				{
					$permalink = get_permalink($mngl_options->profile_page_id);
					$param_char = ((preg_match("#\?#",$permalink))?'&':'?');
					$myProfURL3 = "{$permalink}{$param_char}u={$MnglUser->user_login}";
				}
			}
			$link = "<a href='" . $myProfURL3 . "' title='".__("View profile", "mingleforum")."'>$user</a>";
		}
		else
		{
			$link = "<a href='".$this->base_url."profile&id=$user_id' title='".__("View profile", "mingleforum")."'>$user</a>";
		}
		//END MINGLE PROFILE LINKS
		if($user == __("Guest", "mingleforum"))
			return $user;
		$user_op = get_user_meta($user_id, "wpf_useroptions", true);
		if($user_op)
			if($user_op['allow_profile'] == false)
				return $user;
		return $link;
	}

	function form_buttons(){
		$button = '
	<a title="'.__("Bold", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[b]", "[/b]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/b.png" /></a><a title="'.__("Italic", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[i]", "[/i]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/i.png" /></a><a title="'.__("Underline", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[u]", "[/u]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/u.png" /></a><a title="'.__("Strikethrough", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[s]", "[/s]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/s.png" /></a><a title="'.__("Code", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[code]", "[/code]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/code.png" /></a><a title="'.__("Quote", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[quote]", "[/quote]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/quote.png" /></a><a title="'.__("List", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[list]", "[/list]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/list.png" /></a><a title="'.__("List item", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[*]", "", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/li.png" /></a><a title="'.__("Link", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[url]", "[/url]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/url.png" /></a><a title="'.__("Image", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[img]", "[/img]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/img.png" /></a><a title="'.__("Email", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[email]", "[/email]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/email.png" /></a><a title="'.__("Add Hex Color", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[color=#]", "[/color]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/color.png" /></a><a title="'.__("Embed YouTube Video", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[embed]", "[/embed]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/yt.png" /></a><a title="'.__("Embed Google Map", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText("[map]", "[/map]", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/bbc/gm.png" /></a>';
		return $button;
	}

	function form_smilies(){
		$button = '
	<a title="'.__("Smile", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText(" :) ", "", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/smilies/smile.gif" /></a><a title="'.__("Big Grin", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText(" :D ", "", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/smilies/biggrin.gif" /></a><a title="'.__("Sad", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText(" :( ", "", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/smilies/sad.gif" /></a><a title="'.__("Neutral", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText(" :| ", "", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/smilies/neutral.gif" /></a><a title="'.__("Razz", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText(" :P ", "", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/smilies/razz.gif" /></a><a title="'.__("Mad", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText(" :x ", "", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/smilies/mad.gif" /></a><a title="'.__("Confused", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText(" :? ", "", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/smilies/confused.gif" /></a><a title="'.__("Eek!", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText(" 8O ", "", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/smilies/eek.gif" /></a><a title="'.__("Wink", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText(" ;) ", "", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/smilies/wink.gif" /></a><a title="'.__("Surprised", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText(" :o ", "", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/smilies/surprised.gif" /></a><a title="'.__("Cool", "mingleforum").'" href="javascript:void(0);" onclick=\'surroundText(" 8-) ", "", document.forms.addform.message); return false;\'><img src="'.$this->skin_url.'/images/smilies/cool.gif" /></a>';
		return $button;
	}

	function footer(){
    $o = "";
		switch($this->current_view){
			case MAIN:
				$o = apply_filters('mf_ad_above_info_center', ''); //Adsense Area -- Above Info Center
				$o .= "<div class='wpf'>";
				$o .= "<table class='wpf-table' width='100%' cellspacing='0' cellpadding='0'>";
						$o .= "<tr>
									<th align='center' colspan='2'>".__("Info Center", "mingleforum")."</th>
								</tr>
								<tr>
								</tr>
								<tr>
									<td width='3%' class='forumIcon' align='center'><img alt='' src='$this->skin_url/images/icons/info.gif' /></td>
									<td>
										".$this->num_posts_total()." ".__("Posts in", "mingleforum")." ".$this->num_threads_total()." ".__("Topics Made by", "mingleforum")." ".count($this->get_users())." ".__("Members", "mingleforum").". ".__("Latest Member:", "mingleforum")." ".$this->profile_link($this->latest_member())."
										<br />".$this->get_lastpost_all()."
									</td>
								</tr>
						</table>";
								$o .= "</div>";
				break;
			case FORUM: break;
			case THREAD: break;
		}
		$this->o .= $o;
	}

	function latest_member(){
		global $wpdb;
		return $wpdb->get_var("select ID from $wpdb->users order by user_registered DESC limit 1");
	}

	function show_new(){
	$this->current_view = NEWTOPICS;
		global $wpdb;
		$this->header();
		$lastvisit = $this->last_visit();
		$threads = $wpdb->get_results("select distinct($this->t_threads.id) from $this->t_posts inner join $this->t_threads on $this->t_posts.parent_id = $this->t_threads.id where $this->t_posts.date > '$lastvisit' order by $this->t_posts.date desc");
			$o = "<div class='wpf'><table class='wpf-table' cellpadding='0' cellspacing='0'>
							<tr>
							<th colspan='5' class='wpf-bright'>".__("New topics since your last visit", "mingleforum")."</th>
						</tr>
						<tr>
							<th width='7%'>".__("Status", "mingleforum")."</th>
							<th>".__("Topic Title", "mingleforum")."</th>
							<th width='11%' nowrap='nowrap'>".__("Started by", "mingleforum")."</th>
							<th width='4%'>".__("Replies", "mingleforum")."</th>
							<th width='22%'>".__("Last post", "mingleforum")."</th>
						</tr>";
				foreach($threads as $thread){
						if($this->have_access($this->forum_get_group_from_post($thread->id)))
						{
							$starter_id = $wpdb->get_var("SELECT starter FROM $this->t_threads WHERE id = $thread->id");
							$o .= "<tr>
							<td align='center' class='forumIcon'>".$this->get_topic_image($thread->id)."</td>
							<td class='wpf-alt' align='top'><a href='"
								.$this->get_paged_threadlink($thread->id)."'>"
								.$this->output_filter($this->get_threadname($thread->id))."</a>
							</td>
							<td>".$this->profile_link($starter_id)."</td>
							<td class='wpf-alt' align='center'>".( $this->num_posts($thread->id) - 1 )."</td>
							<td><small>".$this->get_lastpost($thread->id)."</small></td>
						</tr>";
						}
				}
		$o .= "</table></div>";
		$this->o .= $o;
		$this->footer();
	}

	function num_post_user($user){
		global $wpdb;
		return $wpdb->get_var("SELECT count(author_id) FROM $this->t_posts WHERE author_id = $user");
	}

	function view_profile(){
	global $wpdb, $user_ID;
	$this->current_view = PROFILE;
	if(is_numeric($_GET['id'])) //Security fix to prevent SQL injections
		$user_id = $_GET['id'];
    else
		$user_id = 0;
	$user = get_userdata($user_id);
	$this->header();
	$o = "<div class='wpf'>
			<table class='wpf-table' cellpadding='0' cellspacing='0' width='100%'>
				<tr>
					<th class='wpf-bright'>".__("Summary", "mingleforum")." - ".$this->get_userdata($user_id, $this->options['forum_display_name'])."</th>
				</tr>
				<tr>
					<td>
						<table class='wpf-table' cellpadding='0' cellspacing='0' width='100%'>
							<tr>
								<td width='20%'><strong>".__("Name:", "mingleforum")."</strong></td>
								<td>$user->first_name $user->last_name</td>
								<td rowspan='9' valign='top' width='1%'>".$this->get_avatar($user_id, 60)."</td>
							</tr>
							<tr>
								<td><strong>".__("Registered:", "mingleforum")."</strong></td>
								<td>".$this->format_date($user->user_registered)."</td>
							</tr>
							<tr>
								<td><strong>".__("Posts:", "mingleforum")."</strong></td>
								<td>".$this->num_post_user($user_id)."</td>
							</tr>
							<tr>
								<td><strong>".__("Position:", "mingleforum")."</strong></td>
								<td>".$this->get_userrole($user_id)."</td></tr>
							<tr>
								<td><strong>".__("Website:", "mingleforum")."</strong></td>
								<td><a href='$user->user_url'>$user->user_url</a></td>
							</tr>
							<tr>
								<td><strong>".__("AIM:", "mingleforum")."</strong></td>
								<td>$user->aim</td>
							</tr>
							<tr>
								<td><strong>".__("Yahoo:", "mingleforum")."</strong></td>
								<td>$user->yim</td></tr>
							<tr>
								<td><strong>".__("Jabber/google Talk:", "mingleforum")."</strong></td>
								<td>$user->jabber</td>
							</tr>
							<tr>
								<td valign='top'><strong>".__("Biographical Info:", "mingleforum")."</strong></td>
								<td valign='top'>".$this->output_filter(make_clickable(convert_smilies(wpautop($user->description))))."</td>
							</tr>
						</table>
					</td>
				</tr>
			</table></div>";
		$this->o .= $o;
		$this->footer();
	}
  
	function search_results(){
		global $wpdb;
		$o = "";
		$this->current_view = SEARCH;
		$this->header();
		$search_string = $wpdb->escape($_POST['search_words']);
		$sql = "SELECT $this->t_posts.id, `text`, $this->t_posts.subject, $this->t_posts.parent_id, $this->t_posts.`date`, MATCH (`text`) AGAINST (' {$search_string}') AS score
		FROM $this->t_posts JOIN $this->t_threads on $this->t_posts.parent_id = $this->t_threads.id
		AND MATCH (`text`) AGAINST ('{$search_string}')
		ORDER BY score DESC
		LIMIT 30";
		$results = $wpdb->get_results($sql);
		$max = 0;
		foreach($results as $result)
			if($result->score > $max)
				$max = $result->score;
		if($results)
			$const = 100/$max;
		$o .= "<table class='wpf-table' cellspacing='0' cellpadding='0' width='100%'>
				<tr>
					<th width='8%'>Status</th>
					<th width='100%'>".__("Subject", "mingleforum")."</th>
					<th>".__("Relevance", "mingleforum")."</th>
					<th>".__("Started by", "mingleforum")."</th>
					<th>".__("Posted", "mingleforum")."</th>
				</tr>";
		foreach($results as $result){
			if($this->have_access($this->forum_get_group_from_post($result->parent_id))){
			$starter = $wpdb->get_var("select starter from {$this->t_threads} where id = {$result->parent_id}");
				$o .= "<tr>
							<td valign='top' align='center'>".$this->get_topic_image($result->parent_id)."</td>
							<td valign='top' class='wpf-alt'><a href='".$this->get_threadlink($result->parent_id)."'>".stripslashes($result->subject)."</a>
							</td>
							<td valign='top'><small>".round($result->score*$const, 1)."%</small></td>
							<td valign='top' nowrap='nowrap' class='wpf-alt'>".$this->profile_link($starter)."</td>
							<td valign='top' class='wpf-alt' nowrap='nowrap'>".$this->format_date($result->date)."</td>
						</tr>";
			}
		}
		$o .= "</table>";
		$this->o .= $o;
		$this->footer();
	}

	function ext_str_ireplace($findme, $replacewith, $subject){ 
 	 	return substr($subject, 0, stripos($subject, $findme)).
 	 	       str_replace('$1', substr($subject, stripos($subject, $findme), strlen($findme)), $replacewith).
 	 	       substr($subject, stripos($subject, $findme)+strlen($findme));
	}

	function cuttext($value, $length){    
		if(is_array($value)) list($string, $match_to) = $value;
		else { $string = $value; $match_to = $value{0}; }
		$match_start = stristr($string, $match_to);
		$match_compute = strlen($string) - strlen($match_start);
		if (strlen($string) > $length)
		{
			if ($match_compute < ($length - strlen($match_to)))
			{
				$pre_string = substr($string, 0, $length);
				$pos_end = strrpos($pre_string, " ");
				if($pos_end === false) $string = $pre_string."...";
				else $string = substr($pre_string, 0, $pos_end)."...";
			}
			else if ($match_compute > (strlen($string) - ($length - strlen($match_to))))
			{
				$pre_string = substr($string, (strlen($string) - ($length - strlen($match_to))));
				$pos_start = strpos($pre_string, " ");
				$string = "...".substr($pre_string, $pos_start);
				if($pos_start === false) $string = "...".$pre_string;
				else $string = "...".substr($pre_string, $pos_start);
			}
			else
			{        
				$pre_string = substr($string, ($match_compute - round(($length / 3))), $length);
				$pos_start = strpos($pre_string, " "); $pos_end = strrpos($pre_string, " ");
				$string = "...".substr($pre_string, $pos_start, $pos_end)."...";
				if($pos_start === false && $pos_end === false) $string = "...".$pre_string."...";
				else $string = "...".substr($pre_string, $pos_start, $pos_end)."...";
			}
			$match_start = stristr($string, $match_to);
			$match_compute = strlen($string) - strlen($match_start);
		}
		return $string;
	}

	function get_topic_image($thread){
		$post_count = $this->num_posts($thread);
		if($this->is_closed($thread)){
			return "<img src='$this->skin_url/images/topic/closed.gif' alt='".__("Closed topic", "mingleforum")."' title='".__("Closed topic", "mingleforum")."'>";
		}
		if($post_count < $this->opt['hot_topic']){
			return "<img src='$this->skin_url/images/topic/normal_post.gif' alt='".__("Normal topic", "mingleforum")."' title='".__("Normal topic", "mingleforum")."'>";
		}
		if($post_count >= $this->opt['hot_topic'] && $post_count < $this->opt['veryhot_topic']){
			return "<img src='$this->skin_url/images/topic/hot_post.gif' alt='".__("Hot topic", "mingleforum")."' title='".__("Hot topic", "mingleforum")."'>";
		}
		if($post_count >= $this->opt['veryhot_topic']){
			return "<img src='$this->skin_url/images/topic/my_hot_post.gif' alt='".__("Very Hot topic", "mingleforum")."' title='".__("Very Hot topic", "mingleforum")."'>";
		}
	}

	function get_topic_image_two($thread){
		$post_count = $this->num_posts($thread);
		if($this->is_closed($thread)){
			return "closed.gif";
		}
		if($post_count < $this->opt['hot_topic']){
			return "normal_post.gif";
		}
		if($post_count >= $this->opt['hot_topic'] && $post_count < $this->opt['veryhot_topic']){
			return "hot_post.gif";
		}
		if($post_count >= $this->opt['veryhot_topic']){
			return "my_hot_post.gif";
		}
	}

	function get_captcha(){
		global $user_ID;
		$out = "";
		if(!$user_ID && $this->opt['forum_captcha'])
		{
			include_once(WPFPATH."captcha/shared.php");
			include_once(WPFPATH."captcha/captcha_code.php");
			$wpf_captcha = new CaptchaCode();
			$wpf_code = wpf_str_encrypt($wpf_captcha->generateCode(6));
			$out .= "	<tr>
						<td><img alt='' src='".WPFURL."captcha/captcha_images.php?width=120&height=40&code=".$wpf_code."' />
						<input type='hidden' name='wpf_security_check' value='".$wpf_code."'></td>
						<td>".__("Security Code:", "mingleforum")."<input id='wpf_security_code' name='wpf_security_code' type='text' class='wpf-input'/></td>
						</tr>";
		}
		return $out;
	}

	function get_quick_reply_captcha(){
		global $user_ID;
		$out = "";
		$out .= apply_filters('wpwf_quick_form_guestinfo',"");//--weaver-- show the guest info form
		if(!$user_ID && $this->opt['forum_captcha'])
		{
			include_once(WPFPATH."captcha/shared.php");
			include_once(WPFPATH."captcha/captcha_code.php");
			$wpf_captcha = new CaptchaCode();
			$wpf_code = wpf_str_encrypt($wpf_captcha->generateCode(6));
			$out .= "	<tr>
							<td>
								<img src='".WPFURL."captcha/captcha_images.php?width=120&height=40&code=".$wpf_code."' />
								<input type='hidden' name='wpf_security_check' value='".$wpf_code."'><br/>
								<input id='wpf_security_code' name='wpf_security_code' type='text' class='wpf-input'/>".__("Enter Security Code: (required)", "mingleforum")
							."</td>
						</tr>";
		}
		return $out;
	}

	function notify_thread_subscribers($thread_id, $subject, $content, $date)
	{
		global $user_ID;
		$submitter_name = (!$user_ID)?"Guest":$this->get_userdata($user_ID, $this->options['forum_display_name']);
		$submitter_email = (!$user_ID)?"guest@nosite.com":$this->get_userdata($user_ID, 'user_email');
		$sender = get_bloginfo("name");
		$to = get_option("mf_thread_subscribers_".$thread_id, array());
		$subject =	__("Forum post - ", "mingleforum").$subject;
		$message =	__("DETAILS:", "mingleforum")."<br/><br/>".
					__("Name:", "mingleforum")." ".$submitter_name."<br/>".
//					__("Email:", "mingleforum")." ".$submitter_email."<br/>".
					__("Date:", "mingleforum")." ".$this->format_date($date)."<br/>".
					__("Reply Content:", "mingleforum")."<br/>".$content."<br/><br/>".
					__("View Post Here:", "mingleforum")." ".$this->get_threadlink($thread_id);
		$headers =	"MIME-Version: 1.0\r\n".
					"From: ".$sender." "."<".get_bloginfo("admin_email").">\r\n".
					"Content-Type: text/HTML; charset=\"".get_option('blog_charset')."\"\r\n".
					"BCC: ".implode(",", $to)."\r\n";
			if(!empty($to))
				wp_mail("", $subject, make_clickable(convert_smilies(wpautop($this->output_filter(stripslashes($message))))), $headers);
	}

	function notify_forum_subscribers($thread_id, $subject, $content, $date, $forum_id)
	{
		global $user_ID;
		$submitter_name = (!$user_ID)?"Guest":$this->get_userdata($user_ID, $this->options['forum_display_name']);
		$submitter_email = (!$user_ID)?"guest@nosite.com":$this->get_userdata($user_ID, 'user_email');
		$sender = get_bloginfo("name");
		$to = get_option("mf_forum_subscribers_".$forum_id, array());
		$subject =	__("Forum post - ", "mingleforum").$subject;
		$message =	__("DETAILS:", "mingleforum")."<br/><br/>".
					__("Name:", "mingleforum")." ".$submitter_name."<br/>".
//					__("Email:", "mingleforum")." ".$submitter_email."<br/>".
					__("Date:", "mingleforum")." ".$this->format_date($date)."<br/>".
					__("Reply Content:", "mingleforum")."<br/>".$content."<br/><br/>".
					__("View Post Here:", "mingleforum")." ".$this->get_threadlink($thread_id);
		$headers =	"MIME-Version: 1.0\r\n".
					"From: ".$sender." "."<".get_bloginfo("admin_email").">\r\n".
					"Content-Type: text/HTML; charset=\"".get_option('blog_charset')."\"\r\n".
					"BCC: ".implode(",", $to)."\r\n";
			if(!empty($to))
				wp_mail("", $subject, make_clickable(convert_smilies(wpautop($this->output_filter(stripslashes($message))))), $headers);
	}

	function notify_admins($thread_id, $subject, $content, $date)
	{
		global $user_ID;
		$submitter_name = (!$user_ID)?"Guest":$this->get_userdata($user_ID, $this->options['forum_display_name']);
		$submitter_email = (!$user_ID)?"guest@nosite.com":$this->get_userdata($user_ID, 'user_email');
		$sender = get_bloginfo("name");
		$to = get_bloginfo("admin_email");
		$subject =	__("New Forum content - ", "mingleforum").$subject;
		$message =	__("DETAILS:", "mingleforum")."<br/><br/>".
					__("Name:", "mingleforum")." ".$submitter_name."<br/>".
					__("Email:", "mingleforum")." ".$submitter_email."<br/>".
					__("Date:", "mingleforum")." ".$this->format_date($date)."<br/>".
					__("Reply Content:", "mingleforum")."<br/>".$content."<br/><br/>".
					__("View Post Here:", "mingleforum")." ".$this->get_threadlink($thread_id);
		$headers =	"MIME-Version: 1.0\r\n".
					"From: ".$sender." "."<".$to.">\n".
					"Content-Type: text/HTML; charset=\"".get_option('blog_charset')."\"\r\n";
		if($this->options['notify_admin_on_new_posts'])
			if(!empty($to))
				wp_mail($to, $subject, make_clickable(convert_smilies(wpautop($this->output_filter(stripslashes($message))))), $headers);
	}

	function autoembed($string)
	{
		global $wp_embed;
		if (is_object($wp_embed))
			return $wp_embed->autoembed($string);
		else
			return $string;
	}

	function rewriting_on()
	{
		$permalink_structure = get_option('permalink_structure');
		return ($permalink_structure and !empty($permalink_structure));
	}

	//Integrate forum with Cartpauj PM OR Mingle -- Following two functions
	function get_inbox_link()
	{
		if(!function_exists('is_plugin_active'))
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active('cartpauj-pm/pm-main.php'))
		{
			global $cartpaujPMS;
			if($this->convert_version_to_int($cartpaujPMS->get_version()) >= 1009)
			{
				$URL = get_permalink($cartpaujPMS->getPageID());
				$numNew = $cartpaujPMS->getNewMsgs();
				return "<a href='".$URL."'>".__("Inbox", "mingleforum")."</a> (<font color='red'>".$numNew."</font>)<br/>";
			}
		}
		if(is_plugin_active('mingle/mingle.php'))
		{
			if($this->convert_version_to_int($this->get_mingle_version()) >= 32)
			{
				global $mngl_options, $mngl_message, $mngl_user;
				$numNew = $mngl_message->get_unread_count();
				if( MnglUtils::is_user_logged_in() and MnglUser::user_exists_and_visible($mngl_user->id) )
					return "<a href='".get_permalink($mngl_options->inbox_page_id)."'>".__("Inbox", "mingleforum")."</a> (<font color='red'>".$numNew."</font>)<br/>";
			}
		}
		return "";
	}

	function get_send_message_link($id)
	{
		if(!function_exists('is_plugin_active'))
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active('cartpauj-pm/pm-main.php'))
		{
			global $cartpaujPMS;
			if($this->convert_version_to_int($cartpaujPMS->get_version()) >= 1009)
			{
				$cartpaujPMS->setPageURLs();
				$URL = $cartpaujPMS->actionURL."newmessage&to=".$id;
				return "<a href='".$URL."'>".__("Send Message", "mingleforum")."</a><br/>";
			}
		}
		if(is_plugin_active('mingle/mingle.php'))
		{
			if($this->convert_version_to_int($this->get_mingle_version()) >= 32)
			{
				global $mngl_options, $mngl_friend, $mngl_user, $user_ID;
				if( (MnglUtils::is_user_logged_in() and
				MnglUser::user_exists_and_visible($mngl_user->id) and
				$mngl_friend->is_friend($mngl_user->id, $id)) or current_user_can('administrator') or is_super_admin($user_ID))
				{
					$param_char = MnglAppController::get_param_delimiter_char($permalink);
					return "<a href='".get_permalink($mngl_options->inbox_page_id).$param_char."u=".$id."'>".__("Send Message", "mingleforum")."</a><br/>";
				}
			}
		}
		return "";
	}
	function get_mingle_version()
	{
		$plugin_data = implode('', file(ABSPATH."wp-content/plugins/mingle/mingle.php"));
		if (preg_match("|Version:(.*)|i", $plugin_data, $version))
			$version = $version[1];
		return (string)$version;
	}
	function convert_version_to_int($version)
	{
		$result = str_replace(".", "", $version);
		return (int)$result;
	}

	function admin_get_pages()
	{
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'page'";

		$results = $wpdb->get_results( $query );

		if($results)
			return $results;
		else
			return array();
	}

	//SEO Friendly URL stuff -- Pain in the @$$! -- But I am the bomb.com
	function get_seo_friendly_query()
	{
		$end = array();
		$request_uri = $_SERVER['REQUEST_URI'];
		$link = str_replace(site_url(), '', get_permalink($this->get_pageid()));
		$uri = trim(str_replace($link, '', $request_uri), '/');
		$uri = explode('/', $uri);
		if (array_count_values($uri))
		{
			$m = end($uri);
			preg_match("/.*-(group|forum|thread)(\d*(\.?\d+)?)$/", $m, $found);
		}
		if (!empty($found))
		{
			$end = array('action' => $found[1],	'id' => $found[2]);
		}
		return $end;
	}

	function get_seo_friendly_title($str, $replace=array())
	{
		if(!empty($replace)) //Currently not used
		{
			$str = str_replace((array)$replace, ' ', $str);
		}
		if(function_exists('ctl_sanitize_title')) //perfect for crillic languages
			return ctl_sanitize_title($str);

		return sanitize_title_with_dashes($str); //Seems to work for most other languages
	}

	function flush_wp_rewrite_rules()
	{
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	function set_seo_friendly_rules($args)
	{
		$new = array();
		$link = trim(str_replace(array(site_url(), 'index.php/'), '', get_permalink($this->get_pageid())), '/');
		$new['('.$link.')(/[-/0-9a-zA-Z]+)?/(.*)$'] = 'index.php?pagename=$matches[1]&page=$matches[2]';
		return $new + $args;
	}

	//Add a dynamic sitemap for the forum posts
	function do_sitemap()
	{
		global $wpdb;
		$priority = "0.8";
		$freq = "daily";
		$threads = $this->get_threads(false);
		$ind = "	";
		$nl = "\n";

		if(!empty($threads))
		{
			$out = "<?xml version='1.0' encoding='UTF-8'?>".$nl."<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>".$nl;
			foreach($threads as $t)
			{
				$time = explode(' ', $t->last_post, 2);
				$time = explode('-', $time[0], 3);
				$out .= $ind."<url>".$nl.$ind.$ind."<loc>".$this->clean_link($this->get_threadlink($t->id))."</loc>".$nl.$ind.$ind."<lastmod>".date('Y-m-d', mktime(0, 0, 0, $time[1], $time[2], $time[0]))."</lastmod>".$nl.$ind.$ind."<changefreq>".$freq."</changefreq>".$nl.$ind.$ind."<priority>".$priority."</priority>".$nl.$ind."</url>".$nl;
			}
			$out .= "</urlset>";
		}
		echo $out;
	}

	function clean_link($l)
	{
		$l = str_replace('&', '&amp;', $l);
		return $l;
	}

	//Filter function for ads
	function mf_ad_above_forum($value)
	{
		if($this->ads_options['mf_ad_above_forum_on'])
			$str = "<div class='mf-ad-above-forum'>".stripslashes($this->ads_options['mf_ad_above_forum'])."</div><br/>";
		else
			$str = '';
		return $str;
	}

	function mf_ad_below_forum($value)
	{
		if($this->ads_options['mf_ad_below_forum_on'])
			$str = "<br/><div class='mf-ad-below-forum'>".stripslashes($this->ads_options['mf_ad_below_forum'])."</div>";
		else
			$str = '';
		return $str;
	}

	function mf_ad_above_branding($value)
	{
		if($this->ads_options['mf_ad_above_branding_on'])
			$str = "<br/><div class='mf-ad-above-branding'>".stripslashes($this->ads_options['mf_ad_above_branding'])."</div><br/>";
		else
			$str = '';
		return $str;
	}

	function mf_ad_above_info_center($value)
	{
		if($this->ads_options['mf_ad_above_info_center_on'])
			$str = "<div class='mf-ad-above-info-center'>".stripslashes($this->ads_options['mf_ad_above_info_center'])."</div><br/>";
		else
			$str = '';
		return $str;
	}

	function mf_ad_above_quick_reply($value)
	{
		if($this->ads_options['mf_ad_above_quick_reply_on'])
			$str = "<div class='mf-ad-above-quick-reply'>".stripslashes($this->ads_options['mf_ad_above_quick_reply'])."</div>";
		else
			$str = '';
		return $str;
	}

	function mf_ad_above_breadcrumbs($value)
	{
		if($this->ads_options['mf_ad_above_breadcrumbs_on'])
			$str = "<br/><div class='mf-ad-above-breadcrumbs'>".stripslashes($this->ads_options['mf_ad_above_breadcrumbs'])."</div>";
		else
			$str = '';
		return $str;
	}

	function mf_ad_below_first_post($value)
	{
		if($this->ads_options['mf_ad_below_first_post_on'])
			$str = "<tr><td colspan='2'><div class='mf-ad-below-first-post'>".stripslashes($this->ads_options['mf_ad_below_first_post'])."</div></td></tr>";
		else
			$str = '';
		return $str;
	}

	//Integrate WP Posts with the Forum
	function send_wp_posts_to_forum()
	{
		add_meta_box('mf_posts_to_forum', __('Mingle Forum Post Options', 'mingleforum'), array(&$this, 'show_meta_box_options'), 'post');
	}

	function show_meta_box_options()
	{
		$forums = $this->get_forums();
		echo '<input type="checkbox" name="mf_post_to_forum" value="true" />&nbsp;'.__('Add this post to', 'mingleforum');
		echo '&nbsp;<select name="mf_post_to_forum_forum">';
		foreach($forums as $f)
			echo '<option value="'.$f->id.'">'.$f->name.'</option>';
		echo '</select><br/><small>'.__('Do not check this if this post has already been linked to the forum!', 'mingleforum').'</small>';
	}

	function saving_posts($post_id)
	{
		global $wpdb, $user_ID;
		$this->setup_links();

		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		if('post' == $_POST['post_type'])
		{
			if(!current_user_can('edit_post', $post_id))
				return;
		}
		else
			return;

		$mydata = ($_POST['mf_post_to_forum'] == 'true')?true:false;

		if($mydata)
		{
			$date = $this->wpf_current_time_fixed('mysql', 0);
			$fid = (int)$_POST['mf_post_to_forum_forum'];
			$_POST['mf_post_to_forum'] = 'false'; //Eternal loop if this isn't set to false
			$post = get_post($post_id);
			$sql_thread = "INSERT INTO {$this->t_threads} (last_post, subject, parent_id, `date`, status, starter) VALUES('{$date}', '".$this->strip_single_quote($post->post_title)."', '{$fid}', '{$date}', 'open', '{$user_ID}')";
			$wpdb->query($sql_thread);
			$tid = $wpdb->insert_id;
			$sql_post = "INSERT INTO {$this->t_posts} (text, parent_id, `date`, author_id, subject) VALUES('".$this->input_filter($wpdb->escape($post->post_content))."', '{$tid}', '{$date}', '{$user_ID}', '".$this->strip_single_quote($post->post_title)."')";
			$wpdb->query($sql_post);
			$new = $post->post_content."\n".'<p><a href="'.$this->get_threadlink($tid).'">'.__("Join the Forum discussion on this post", "mingleforum").'</a></p>';
			$post->post_content = $new;
			wp_update_post($post);
		}
	}

	function strip_single_quote($string)
	{
		global $wpdb;
		$Find = array("'", "\\");
		$Replace = array("", "");
		$newStr = str_replace($Find, $Replace, $string);
		return $newStr;
	}

} // End class
} // End
?>