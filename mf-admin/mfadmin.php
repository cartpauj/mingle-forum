<?php
if(!class_exists("MFAdmin"))
{
  class MFAdmin
  {
    public static function load_hooks()
    {
      add_action('admin_init', 'MFAdmin::maybe_save_options');
      // add_action('admin_menu', 'MFAdmin::admin_menus');
      add_action('admin_enqueue_scripts', 'MFAdmin::enqueue_admin_scripts');
    }

    public static function enqueue_admin_scripts($hook)
    {
      $plug_url = plugin_dir_url(__FILE__) . '../';

      //Let's only load our shiz on mingle-forum admin pages
      if (strstr($hook, 'mingle-forum') !== false)
      {
        $wp_scripts = new WP_Scripts();
        $ui = $wp_scripts->query('jquery-ui-core');
        $url = "//ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/start/jquery-ui.css";

        wp_enqueue_style('mingle-forum-ui-css', $url);
        wp_enqueue_style('mingle-forum-admin-css', $plug_url . "css/mf_admin.css");
        wp_enqueue_script('mingle-forum-admin-js', $plug_url . "js/mf_admin.js", array('jquery-ui-accordion'));
      }
    }

    public static function options_page()
    {
      global $mingleforum;

      $saved = (isset($_GET['saved']) && $_GET['saved'] == 'true');

      require('views/options_page.php');
    }

    public static function maybe_save_options()
    {
      global $wpdb, $mingleforum;

      $saved_ops = array();

      if(!isset($_POST['mf_options_submit']) || empty($_POST['mf_options_submit']))
        return;

      foreach($mingleforum->default_ops as $k => $v)
      {
        if(isset($_POST[$k]) && !empty($_POST[$k]))
        {
          if(is_array($v))
            $saved_ops[$k] = explode(',', $_POST[$k]);
          elseif(is_numeric($v))
            $saved_ops[$k] = (int)$_POST[$k];
          elseif(is_bool($v))
            $saved_ops[$k] = true;
          else
            $saved_ops[$k] = $wpdb->escape(stripslashes($_POST[$k]));
        }
        else
        {
          if(is_array($v))
            $saved_ops[$k] = array();
          elseif(is_numeric($v))
            $saved_ops[$k] = $v;
          elseif(is_bool($v))
            $saved_ops[$k] = false;
          else
            $saved_ops[$k] = '';
        }
      }

      //Set some stuff that isn't on the options page
      $saved_ops['forum_skin'] = $mingleforum->options['forum_skin'];
      $saved_ops['forum_db_version'] = $mingleforum->options['forum_db_version'];

      update_option('mingleforum_options', $saved_ops);
      wp_redirect(admin_url('admin.php?page=mingle-forum&saved=true'));
    }
  } //End class
} //End if
?>
