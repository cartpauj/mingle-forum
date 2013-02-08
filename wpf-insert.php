<?php
global $wpdb, $mingleforum, $user_ID, $user_level;
$error = false;
$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-load.php');
$mingleforum->setup_linksdk($_POST['add_topic_plink']);
$options = get_option("mingleforum_options");

//Checking if current categories have been disabled to admin posting only
$the_forum_id = false;
if(isset($_POST['add_topic_forumid']) && !empty($_POST['add_topic_forumid']))
  $the_forum_id = $mingleforum->check_parms($_POST['add_topic_forumid']);
if(isset($_POST['add_post_forumid']) && !empty($_POST['add_post_forumid'])) {
  $the_thread_id = $mingleforum->check_parms($_POST['add_post_forumid']);
  $the_forum_id = $wpdb->get_var($wpdb->prepare("SELECT `parent_id` FROM {$mingleforum->t_threads} WHERE `id` = %d", $the_thread_id));
}
if(isset($_POST['thread_id']) && !empty($_POST['thread_id']) && isset($_POST['edit_post_submit'])) {
  $the_thread_id = $mingleforum->check_parms($_POST['thread_id']);
  $the_forum_id = $wpdb->get_var($wpdb->prepare("SELECT `parent_id` FROM {$mingleforum->t_threads} WHERE `id` = %d", $the_thread_id));
}
if(is_numeric($the_forum_id))
{
  $the_cat_id = $wpdb->get_var("SELECT `parent_id` FROM {$mingleforum->t_forums} WHERE `id` = {$the_forum_id}");
  
  if(in_array($the_cat_id, $options['forum_disabled_cats']) && !is_super_admin($user_ID) && !$mingleforum->is_moderator($user_ID, $the_forum_id) && !$mingleforum->options['allow_user_replies_locked_cats'])
    wp_die(__("Oops only Administrators can post in this Forum!", "mingleforum"));
}
//End Check

//Spam time interval check
if(!is_super_admin() && !$mingleforum->is_moderator($user_ID, $the_forum_id))
{
  $last_post_time = get_user_meta((int)$user_ID, 'mingle_forum_last_post_time_'.ip_to_string(), true);
  if((time() - (int)$last_post_time) < stripslashes($mingleforum->options['forum_posting_time_limit']))
    wp_die(__('To help prevent spam, we require that you wait', 'mingleforum').' '.ceil(((int)(stripslashes($mingleforum->options['forum_posting_time_limit']))/60)).' '.__('minutes before posting again. Please use your browsers back button to return.', 'mingleforum'));
  else
    update_user_meta((int)$user_ID, 'mingle_forum_last_post_time_'.ip_to_string(), time());
}

function ip_to_string()
{
  return preg_replace("/[^0-9]/", "_", $_SERVER["REMOTE_ADDR"]);
}
//End Spam time interval check

  function mf_u_key()
  {
    $pref = "";
    for ($i = 0; $i < 5; $i++)
    {
    $d = rand(0,1);
    $pref .= $d ? chr(rand(97, 122)) : chr(rand(48, 57));
    }
    return $pref."-";
  }

  function MFAttachImage($temp, $name)
  {
    //GET USERS UPLOAD PATH
    $upload_dir = wp_upload_dir();
    $path = $upload_dir['path']."/";
    $url = $upload_dir['url']."/";
    $u = mf_u_key();
    $name = sanitize_file_name($name);
    if(!empty($name))
      move_uploaded_file($temp, $path.$u.$name);
    return "\n[img]".$url.$u.$name."[/img]";
  }

  function MFGetExt($str)
  {
    //GETS THE FILE EXTENSION BELONGING TO THE UPLOADED FILE
    $i = strrpos($str,".");
    if (!$i) { return ""; }
    $l = strlen($str) - $i;
    $ext = substr($str,$i+1,$l);
    return $ext;
  }

  function mf_check_uploaded_images()
  {
    $valid = array('im1' => true, 'im2' => true, 'im3' => true);
    if($_FILES["mfimage1"]["error"] > 0 && !empty($_FILES["mfimage1"]["name"]))
      $valid['im1'] = false;
    if($_FILES["mfimage2"]["error"] > 0 && !empty($_FILES["mfimage2"]["name"]))
      $valid['im2'] = false;
    if($_FILES["mfimage3"]["error"] > 0 && !empty($_FILES["mfimage3"]["name"]))
      $valid['im3'] = false;
    if(!empty($_FILES["mfimage1"]["name"]))
    {
      $ext = strtolower(MFGetExt(stripslashes($_FILES["mfimage1"]["name"])));
      if($ext != "jpg" && $ext != "jpeg" && $ext != "bmp" && $ext != "png" && $ext != "gif")
        $valid['im1'] = false;
    }
    else
      $valid['im1'] = false;
    if(!empty($_FILES["mfimage2"]["name"]))
    {
      $ext = strtolower(MFGetExt(stripslashes($_FILES["mfimage2"]["name"])));
      if($ext != "jpg" && $ext != "jpeg" && $ext != "bmp" && $ext != "png" && $ext != "gif")
        $valid['im2'] = false;
    }
    else
      $valid['im2'] = false;
    if(!empty($_FILES["mfimage3"]["name"]))
    {
      $ext = strtolower(MFGetExt(stripslashes($_FILES["mfimage3"]["name"])));
      if($ext != "jpg" && $ext != "jpeg" && $ext != "bmp" && $ext != "png" && $ext != "gif")
        $valid['im2'] = false;
    }
    else
      $valid['im3'] = false;
    return $valid;
  }

  //--weaver-- check if guest filled in form
  if(!isset($_POST['edit_post_submit'])) {
    $errormsg = apply_filters('wpwf_check_guestinfo',"");
    if($errormsg != "") {
      $error = true;
      wp_die($errormsg); //plugin failed
    }
  }
  //--weaver-- end guest form check

  if($options['forum_captcha'] == true && !$user_ID){
    include_once(WPFPATH."captcha/shared.php");
    $wpf_code = wpf_str_decrypt($_POST['wpf_security_check']);
      if(($wpf_code == $_POST['wpf_security_code']) && (!empty($wpf_code))) {
        //It passed
      }
      else {
        $error = true;
        $msg = __("Security code does not match", "mingleforum");
        wp_die($msg);
      }
  }

  $cur_user_ID = apply_filters('wpwf_change_userid', $user_ID); // --weaver-- use real id or generated guest ID

  //ADDING A NEW TOPIC?
  if(isset($_POST['add_topic_submit'])) {
    $myReplaceSub = array("'", "\\");
    $subject = str_replace($myReplaceSub, "", $mingleforum->input_filter($_POST['add_topic_subject']));
    $content = $mingleforum->input_filter($_POST['message']);
    $forum_id = $mingleforum->check_parms($_POST['add_topic_forumid']);

    if($subject == "") {
      $msg .= "<h2>".__("An error occured", "mingleforum")."</h2>";
      $msg .= ("<div id='error'><p>".__("You must enter a subject", "mingleforum")."</p></div>");
      $error = true;
    }
    elseif($content == "") {
      $msg .=  "<h2>".__("An error occured", "mingleforum")."</h2>";
      $msg .=  ("<div id='error'><p>".__("You must enter a message", "mingleforum")."</p></div>");
      $error = true;
    }
    else{
      $date = $mingleforum->wpf_current_time_fixed('mysql', 0);
      
      $sql_thread = "INSERT INTO {$mingleforum->t_threads} 
                      (last_post, subject, parent_id, `date`, status, starter) 
                    VALUES
                      (%s, %s, %d, %s, 'open', %d)";
      $wpdb->query($wpdb->prepare($sql_thread, $date, $subject, $forum_id, $date, $cur_user_ID));

      $id = $wpdb->insert_id;
      //Add to mingle board
      $myMingID = -1;
      if(!function_exists('is_plugin_active'))
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
      if(is_plugin_active('mingle/mingle.php') and is_user_logged_in())
      {
        $board_post =& MnglBoardPost::get_stored_object();
        $myMingID = $board_post->create( $cur_user_ID, $cur_user_ID, "[b]".__("created the forum topic:", "mingleforum")."[/b] <a href='" . $mingleforum->get_threadlink($id) . "'>" . $mingleforum->output_filter($subject) . "</a>" );
      }
      //End add to mingle board

      //MAYBE ATTACH IMAGES
      $images = mf_check_uploaded_images();
      if($images['im1'] || $images['im2'] || $images['im3'])
      {
        if($images['im1'])
          $content .= MFAttachImage($_FILES["mfimage1"]["tmp_name"], stripslashes($_FILES["mfimage1"]["name"]));
        if($images['im2'])
          $content .= MFAttachImage($_FILES["mfimage2"]["tmp_name"], stripslashes($_FILES["mfimage2"]["name"]));
        if($images['im3'])
          $content .= MFAttachImage($_FILES["mfimage3"]["tmp_name"], stripslashes($_FILES["mfimage3"]["name"]));
      }

      $sql_post = "INSERT INTO {$mingleforum->t_posts} 
                    (text, parent_id, `date`, author_id, subject)
                  VALUES
                    (%s, %d, %s, %d, %s)";
      $wpdb->query($wpdb->prepare($sql_post, $content, $id, $date, $cur_user_ID, $subject));
      $new_post_id = $wpdb->insert_id;
      
      //UPDATE PROPER Mngl ID
      $sql_thread = "UPDATE {$mingleforum->t_threads}
                      SET mngl_id = %d
                      WHERE id = %d";
      $wpdb->query($wpdb->prepare($sql_thread, $myMingID, $id));
      //END UPDATE PROPER Mngl ID
    }
    if(!$error){
      $mingleforum->notify_forum_subscribers($id, $subject, $content, $date, $forum_id);
      $mingleforum->notify_admins($id, $subject, $content, $date);
      $unused = apply_filters('wpwf_add_guest_sub', $id);	//--weaver-- Maybe add a subscription
      header("Location: ".html_entity_decode($mingleforum->get_threadlink($id)."#postid-".$new_post_id)); exit;
      }
    else
      wp_die($msg);
  }

  //ADDING A POST REPLY?
  if(isset($_POST['add_post_submit'])){
    $myReplaceSub = array("'", "\\");
    $subject = str_replace($myReplaceSub, "", $mingleforum->input_filter($_POST['add_post_subject']));
    $content = $mingleforum->input_filter($_POST['message']);
    $thread = $mingleforum->check_parms($_POST['add_post_forumid']);

    //GET PROPER Mngl ID
    $MngBID = $wpdb->get_var($wpdb->prepare("SELECT mngl_id FROM {$mingleforum->t_threads} WHERE id = %d", $thread));
    //END GET PROPER Mngl ID
    
    if($subject == ""){
      $msg .= "<h2>".__("An error occured", "mingleforum")."</h2>";
      $msg .= ("<div id='error'><p>".__("You must enter a subject", "mingleforum")."</p></div>");
      $error = true;
    }
    elseif($content == ""){
      $msg .=  "<h2>".__("An error occured", "mingleforum")."</h2>";
      $msg .=  ("<div id='error'><p>".__("You must enter a message", "mingleforum")."</p></div>");
      $error = true;
    }
    else{
      $date = $mingleforum->wpf_current_time_fixed('mysql', 0);
      //Add to mingle board
      if(!function_exists('is_plugin_active'))
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
      if(is_plugin_active('mingle/mingle.php') and is_user_logged_in() and $MngBID > 0)
      {
        $board_post =& MnglBoardPost::get_stored_object();
        $mngl_board_comment->create( $MngBID, $cur_user_ID, "[b]".__("replied to the forum topic:", "mingleforum")."[/b] <a href='" . $mingleforum->get_threadlink($thread) . "'>" . $mingleforum->output_filter($subject) . "</a>" );
      }
      //End add to mingle board

      //MAYBE ATTACH IMAGES
      $images = mf_check_uploaded_images();
      if($images['im1'] || $images['im2'] || $images['im3'])
      {
        if($images['im1'])
          $content .= MFAttachImage($_FILES["mfimage1"]["tmp_name"], stripslashes($_FILES["mfimage1"]["name"]));
        if($images['im2'])
          $content .= MFAttachImage($_FILES["mfimage2"]["tmp_name"], stripslashes($_FILES["mfimage2"]["name"]));
        if($images['im3'])
          $content .= MFAttachImage($_FILES["mfimage3"]["tmp_name"], stripslashes($_FILES["mfimage3"]["name"]));
      }

      $sql_post = "INSERT INTO {$mingleforum->t_posts}
            (text, parent_id, `date`, author_id, subject)
         VALUES(%s, %d, %s, %d, %s)";
      $wpdb->query($wpdb->prepare($sql_post, $content, $thread, $date, $cur_user_ID, $subject));
      $new_id = $wpdb->insert_id;
      $wpdb->query($wpdb->prepare("UPDATE {$mingleforum->t_threads} SET last_post = %s WHERE id = %d", $date, $thread));
    }

    if(!$error){
      $mingleforum->notify_thread_subscribers($thread, $subject, $content, $date);
      $mingleforum->notify_admins($thread, $subject, $content, $date);
      $unused = apply_filters('wpwf_add_guest_sub', $thread);	//--weaver-- Maybe add a subscription
      header("Location: ".html_entity_decode($mingleforum->get_paged_threadlink($thread)."#postid-".$new_id)); exit;
    }
    else
      wp_die($msg);
  }

  //EDITING A POST?
  if(isset($_POST['edit_post_submit'])) {
    $myReplaceSub = array("'", "\\");
    $subject = str_replace($myReplaceSub, "", $mingleforum->input_filter($_POST['edit_post_subject']));
    $content = $mingleforum->input_filter($_POST['message']);
    $thread = $mingleforum->check_parms($_POST['thread_id']);
    $edit_post_id = $_POST['edit_post_id'];

    if($subject == "") {
      $msg .= "<h2>".__("An error occured", "mingleforum")."</h2>";
      $msg .= ("<div id='error'><p>".__("You must enter a subject", "mingleforum")."</p></div>");
      $error = true;
    }
    if($content == "") {
      $msg .= "<h2>".__("An error occured", "mingleforum")."</h2>";
      $msg .= ("<div id='error'><p>".__("You must enter a message", "mingleforum")."</p></div>");
      $error = true;
    }
    //Major security check here, prevents hackers from editing the entire forums posts
    if(!is_super_admin($user_ID) && $user_ID != $mingleforum->get_post_owner($edit_post_id) && !$mingleforum->is_moderator($user_ID, $the_forum_id)) {
      $msg .= "<h2>".__("An error occured", "mingleforum")."</h2>";
      $msg .= ("<div id='error'><p>".__("You do not have permission to edit this post!", "mingleforum")."</p></div>");
      $error = true;
    }

    if($error)
      wp_die($msg);

    $sql = ("UPDATE {$mingleforum->t_posts} SET text = %s, subject = %s WHERE id = %d");
    $wpdb->query($wpdb->prepare($sql, $content, $subject, $edit_post_id));

    $ret = $wpdb->get_results($wpdb->prepare("select id from {$mingleforum->t_posts} where parent_id = %d order by date asc limit 1", $thread));
    if($ret[0]->id == $edit_post_id) {
      $sql = ("UPDATE {$mingleforum->t_threads} set subject = %s where id = %d");
      $wpdb->query($wpdb->prepare($sql, $subject, $thread));
    }

    header("Location: ".html_entity_decode($mingleforum->get_paged_threadlink($thread)."#postid-".$edit_post_id)); exit;
  }
?>