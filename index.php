<?php
/*
Plugin Name: Mind Doodle Visual Sitemaps & Tasks
Description: Visual, interactive sitemaps that actually build your sites. Improve workflows with task management within WordPress, enhancing productivity and connecting teams.
Version: 1.6
Author: Mind Doodle
Author URI: https://minddoodle.com
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define('MDSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MDSM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MDSM_IMAGE_URL', plugin_dir_url(__FILE__) . 'assets/images/');

// define('MDSM_OPTIONS_NAME', 'md_options');

define('MDSM_SM_PLUGIN_DIR', __DIR__);
define('MDSM_SM_PLUGIN_URL', plugin_dir_url(__FILE__));

define('MDSM_SITEMAP_OPTION', 'sitemap');
define('MDSM_CONNECT_OPTION', 'mdwp');

define('MDSM_MENU_NAME', 'Mind Doodle Menu');

register_activation_hook(__FILE__, 'mdsm_activation');
register_deactivation_hook(__FILE__, 'mdsm_deactivation');

register_uninstall_hook(__FILE__, 'mdsm_uninstall');

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
  echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
  exit;
}

require_once(MDSM_PLUGIN_DIR . './api_methods.php');
require_once(MDSM_PLUGIN_DIR . './config.php');
require_once(MDSM_PLUGIN_DIR . './shortcode/shortcode.php');

function mdsm_activation() {
  $pages = get_pages([
    'post_type' => 'page',
    'post_status' => 'publish,draft,trash'
  ]);

  $new_options = [];
  $colors = [
    'ce74c6',
    '4ab2d4',
    '829a50',
    'cf3b3e',
    'd58d3f',
    '9174bc',
    '5fb48b',
    'ad9f78',
    'c6c6c6',
    'e8bee4',
    '9ecfdf',
    'b5c496',
    'f3afb1',
    'e2ae77',
    'bca7dc',
    'a3dfc2',
    'ddd6c4'
  ];

  $pages_object = [];

  // MENU
  $menu_exists = wp_get_nav_menu_object( MDSM_MENU_NAME );

  if( !$menu_exists) {
    $menu_id = wp_create_nav_menu(MDSM_MENU_NAME);
  }
  // END MENU

  foreach ($pages as $key => $page) {
    $id = $page->ID;
    $pages_object[$id] = $page;

    if ($page->post_parent != 0) {
      continue;
    }

    $new_options[$id] = ['color' => $colors[$key] ?: $colors[rand(0, count($colors))]];
  }

  function mdsm_sitemap_get_highlevel_parent ($page_id, $pages) {
    if ($pages[$page_id]->post_parent == 0) {
      return $page_id;
    }

    return mdsm_sitemap_get_highlevel_parent($pages[$page_id]->post_parent, $pages);
  }

  foreach ($pages as $key => $page) {
    $id = $page->ID;
    $menu_parent = null;
    if ($menu_id) {
      $menu_items = wp_get_nav_menu_items($menu_id, array('post_status' => 'publish'));
      if (!is_array($menu_items)) {
        continue;
      }
      
      foreach ($menu_items as $menu_item) {
        // Item already in menu?
        if ($menu_item->object_id == $page->ID) {
            continue 2;
        }
        if ($menu_item->object_id == $page->post_parent) {
          $menu_parent = $menu_item->ID;
        }
      }

      wp_update_nav_menu_item($menu_id, 0, array(
        'menu-item-object-id' => $page->ID,
        'menu-item-title' =>  $page->post_title,
        'menu-item-parent-id' => $menu_parent,
        'menu-item-object' => $page->post_type,
        'menu-item-type' => 'post_type',
        'menu-item-type_label' => 'Page',
        'menu-item-url' => get_permalink( $page->ID),
        'menu-item-status' => 'publish'
      ));
    }

    if ($new_options[$id]['color']) {
      continue;
    }

    $parent_id = mdsm_sitemap_get_highlevel_parent($id, $pages_object);
    $new_options[$id]['color'] = $new_options[$parent_id]['color'];
  }

  update_option(MDSM_SITEMAP_OPTION, ['pages' => $new_options]);
}

function mdsm_remove_md_connection() {
  global $mdwp_object, $mdwp_options;
  $delete_result = $mdwp_object -> execRequest('doodle/map', 'DELETE', array(
    'doodle_id' => $mdwp_options['doodle_id'],
    'schema_id' => $mdwp_options['schema_id'],
    'team_id' => $mdwp_options['schema_id']
  ));
  unset($mdwp_options['doodle_id']);
  unset($mdwp_options['schema_id']);
  unset($mdwp_options['selected_team']);
  update_option(MDSM_CONNECT_OPTION, $mdwp_options);
}

function mdsm_deactivation() {
  global $mdwp_object, $mdwp_options;
  $menu_exists = wp_get_nav_menu_object( MDSM_MENU_NAME );
  
  if($menu_exists) {
    wp_delete_nav_menu( MDSM_MENU_NAME );
  }

  delete_option(MDSM_SITEMAP_OPTION);
  mdsm_remove_md_connection();
}

function mdsm_uninstall() {
  global $mdwp_object, $mdwp_options;
  
  delete_option(MDSM_SITEMAP_OPTION);
  // delete_option(MDSM_OPTIONS_NAME);
  delete_option(MDSM_CONNECT_OPTION);
}

function mdsm_init() {
  global $mdsm_plugin_settings, $dfx_api_object, $mdwp_object, $mdwp_options;
  
  if (!class_exists('MDSM_FX_Error')) {
    require_once(plugin_dir_path(__FILE__) . 'lib/functions.php');
  }

  if (!class_exists('MDSM_DFX_API_OBJECT')) {
    require_once(MDSM_SM_PLUGIN_DIR . '/lib/dfx_oauth.php');
  }

  require_once(MDSM_PLUGIN_DIR . './lib/menus.php');


  function mdsm_show_wp_error_message($msg) {
    $message = '';
    if (mdsm_is_fx_error($msg)) {
      $message = $msg -> get_error_message();
    } elseif ($msg['error']) {
      $message = $msg['error_description'];
    }
    return $message ? '<div class="notice notice-error mdsm_error"><p>'.$message.'</p></div>' : '';
  }

  global $mdsm_plugin_settings;
  $mdwp_options = get_option(MDSM_CONNECT_OPTION);

  // mdsm_print($mdwp_options); exit;

  $current_token = $mdwp_options['auth_token'];
  $current_token_type = $mdwp_options['auth_token_type'];
  
  $mdwp_object = new MDSM_DFX_API_OBJECT($current_token, $current_token_type, $mdsm_plugin_settings['api']);

  if (current_user_can('manage_options')) {
    if (isset($_POST['md_signin']) && !$mdwp_options['auth_token'] && wp_verify_nonce($_POST['nonce'], 'mdsm_get_user_token_nonce')) {
      global $mdwp_object;
      $username = sanitize_email($_POST['md_user_login']);
      $password = sanitize_text_field($_POST['md_user_pass']);

      $auth = $mdwp_object -> execRequest('local_sfx/oauth_token', 'POST', 'grant_type=client_credentials', array(CURLOPT_HTTPAUTH => CURLAUTH_BASIC, CURLOPT_USERPWD => $username . ":" . $password));

      if (mdsm_is_fx_error($auth) || $auth['error']) {
        echo mdsm_show_wp_error_message($auth);
      } else {
        $mdwp_object -> set_token($auth['access_token'], $auth['token_type']);
        $mdwp_options['auth_token'] = $auth['access_token'];
        $mdwp_options['auth_token_type'] = $auth['token_type'];

        $user = $mdwp_object -> execRequest('local_sfx/token_user', 'GET');

        if (!mdsm_is_fx_error($user)) {
          $mdwp_options['user'] = $user;
        } else {
          echo mdsm_show_wp_error_message($user); 
        }

        update_option(MDSM_CONNECT_OPTION, $mdwp_options);
      }
    }
  }

  if (isset($_POST['md_logout']) && wp_verify_nonce( $_POST['nonce'], 'mdsm_user_logout_nonce' )) {
    mdsm_remove_md_connection();
    unset($mdwp_options['auth_token']);
    unset($mdwp_options['auth_token_type']);
    unset($mdwp_options['user']);
    update_option(MDSM_CONNECT_OPTION, $mdwp_options);
  }

  require_once(MDSM_SM_PLUGIN_DIR . '/lib/md/md_to_wp.php');
  require_once(MDSM_SM_PLUGIN_DIR . '/lib/md/wp_to_md.php');
}

add_action('init', 'mdsm_init');

function mdsm_add_stylesheet_to_admin() {
  wp_register_style('MDPluginStylesheet', MDSM_SM_PLUGIN_URL . 'assets/css/style.css');
  wp_enqueue_style('MDPluginStylesheet');
  wp_register_style('MdIconsStyle', MDSM_SM_PLUGIN_URL . 'assets/icons/style.css');
  wp_enqueue_style('MdIconsStyle');

  wp_enqueue_script('general', MDSM_SM_PLUGIN_URL . 'js/general.js');
}

add_action( 'admin_enqueue_scripts', 'mdsm_add_stylesheet_to_admin' );

function mdsm_include_md_page() {
  $file = MDSM_PLUGIN_DIR . 'pages/general.php';
  if (file_exists($file))
    include $file; else echo '<h2>File not found!</h2>';
}

function mdsm_include_task_page() {
  $file = MDSM_PLUGIN_DIR . 'pages/tasks/tasks.php';
  if (file_exists($file))
    include $file; else echo '<h2>File not found!</h2>';
}

function mdsm_include_new_task() {
  $file = MDSM_PLUGIN_DIR . 'pages/tasks/new_task.php';
  if (file_exists($file))
    include $file; else echo '<h2>File not found!</h2>';
}

function mdsm_include_sm_page() {
  $file = MDSM_PLUGIN_DIR . 'pages/sitemap.php';
  if (file_exists($file))
    include $file; else echo '<h2>File not found!</h2>';
}

function mdsm_add_toolbar_items($admin_bar) {
  $mdwp_options = get_option(MDSM_CONNECT_OPTION, []);

  if ($mdwp_options['doodle_id'] && $mdwp_options['user']) {
    $admin_bar->add_menu(array(
      'id' => 'new-task',
      'parent' => 'new-content',
      'title' => 'Task',
      'href' => '/wp-admin/admin.php?page=md_task',
      'meta' => array(
        'title' => __('Task'),
        'class' => 'new_task_toolbar_btn'
      )
    ));
  }
  if (current_user_can('edit_pages')) {
    $admin_bar->add_menu( array(
      'id'    => 'sitemap',
      'title' => '<span class="ab-icon dashicons-before dashicons dashicons-networking"></span> Sitemap',
      'href'  => '/wp-admin/admin.php?page=sitemap',
      'meta'  => array(
          'title' => __('Sitemap')
      )
    ));
  }
}

function mdsm_admin_menu() {
  global $mdwp_object;

  $site_name = get_bloginfo('name');
  $mdwp_options = get_option(MDSM_CONNECT_OPTION, []);

  $cp = 'manage_options';

  $md_icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIxLjAuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCA1MCA1MCIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNTAgNTA7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4KCS5zdDB7ZmlsbDojZjFmMWYxO30KPC9zdHlsZT4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTQ5LjgsMjUuNGMwLTMuNS0xLjctNi43LTQuNS04LjRjMC4xLTAuNSwwLjEtMSwwLjEtMS41YzAtNC44LTMuMy04LjktNy43LTkuN2MtMC41LTEtMS4yLTEuOS0yLjEtMi43CgljLTEuNC0xLjItMy4yLTEuOC01LjEtMS44Yy0yLjIsMC00LjIsMC45LTUuNywyLjRjLTEuNC0xLjQtMy40LTIuMy01LjYtMi4zYy0xLjgsMC0zLjYsMC43LTUuMSwxLjhjLTAuOSwwLjctMS42LDEuNi0yLjEsMi43CgljLTQuNCwwLjgtNy43LDQuOS03LjcsOS43YzAsMC41LDAsMSwwLjEsMS41QzEuNywxOC44LDAsMjEuOSwwLDI1LjVjMCwyLjIsMC43LDQuMywxLjksNmMtMC4xLDAuNy0wLjIsMS40LTAuMiwyLjEKCWMwLDUuMSwzLjYsOS4zLDguMyw5LjljMS40LDMuNCw0LjcsNS43LDguMyw1LjdjMi42LDAsNS0xLjIsNi43LTMuMWMxLjcsMS45LDQsMyw2LjYsM2MzLjYsMCw2LjgtMi4zLDguMy01LjcKCWM0LjctMC42LDguMy00LjgsOC4zLTkuOWMwLTAuNy0wLjEtMS40LTAuMi0yLjFDNDkuMSwyOS42LDQ5LjgsMjcuNSw0OS44LDI1LjR6IE00NC42LDMwLjJjLTAuMiwwLjItMC4yLDAuNS0wLjIsMC43CgljMC4zLDAuOCwwLjQsMS42LDAuNCwyLjVjMCwzLjctMi44LDYuNy02LjIsNi44bC0wLjIsMGwwLTAuMmMtMC4xLTEuOC0xLjItMy41LTIuOS00LjNjLTAuMSwwLTAuMi0wLjEtMC4zLTAuMQoJYy0wLjIsMC0wLjQsMC4xLTAuNSwwLjJsMCwwbDAsMGwwLDBsMCwwYy0wLjgsMS0yLDEuNS0zLjMsMS41Yy0wLjgsMC0xLjYtMC4yLTIuMy0wLjdMMzAsMzVsLTEuNC0wLjZsLTEuOCw0LjVsMS40LDAuNmwwLjctMS42CgljMC41LDAuMywxLDAuNSwxLjUsMC42bDAsMGwwLDBjMC4xLDAuMSwwLjYsMC4xLDEuNSwwLjFjMC4yLDAsMC40LDAsMC40LDBjMS4yLTAuMSwyLjMtMC42LDMuMi0xLjVsMC4xLTAuMWwwLjEsMC4xCgljMSwwLjcsMS42LDEuOSwxLjYsMy4yYzAsMC4xLDAsMC4yLDAsMC4zYzAsMCwwLDAuMSwwLDAuMWwwLDBsMCwwYy0wLjYsMi45LTMsNC45LTUuNyw0LjljLTIuOCwwLTUuMi0yLjEtNS43LTVsMCwwdjBsMC03LjVsMC0wLjIKCWwwLjIsMGMwLjIsMCwwLjQsMCwwLjYsMGMyLjMsMCw0LjQtMS4zLDUuNS0zLjRsMC4yLTAuM2wwLjIsMC4zYzAuOSwxLjMsMi4zLDIuMiwzLjgsMi40YzAsMCwwLjEsMCwwLjEsMGgwbDAsMGMwLjEsMCwwLjIsMCwwLjMsMAoJbDAsMGwwLDBjMC4zLDAsMC43LDAuMSwxLDAuM2MwLjksMC40LDEuNSwxLjIsMS43LDIuMWwtMC4yLDBjMCwwLDAtMC4xLDAtMC4xYzAsMCwwLDAuMSwwLDAuMWwtMS42LDAuMWwwLjEsMS42bDUtMC40bC0wLjEtMS42CglsLTIsMC4xYy0wLjItMS0wLjctMS45LTEuNS0yLjZsMCwwbDAsMGMwLDAtMS0xLTIuMy0xYzAsMCwwLDAsMCwwbDAsMGgwbDAsMGMtMC4xLDAtMC4yLDAtMC4zLDBsMCwwbDAsMGMtMS45LTAuMi0zLjUtMS44LTMuNi0zLjgKCWwwLDBsMCwwYzAtMC4xLDAtMC4yLDAtMC40YzAtMC40LTAuMy0wLjYtMC42LTAuNmMtMC4zLDAtMC42LDAuMi0wLjYsMC41bDAsMGwwLDBjMCwwLjEsMCwwLjEsMCwwLjJjMCwwLjEsMCwwLjIsMCwwLjNsMCwwbDAsMAoJYzAsMC43LTAuMiwxLjMtMC41LDEuOWMtMC45LDEuOC0yLjYsMi45LTQuNSwyLjljLTAuMiwwLTAuNCwwLTAuNiwwbC0wLjIsMGwwLTAuMmwwLTcuOWwwLTAuMWwwLjEtMC4xYzEuMS0xLDIuNS0xLjYsNC0xLjYKCWMyLjMsMCw0LjQsMS40LDUuNCwzLjZsLTEuNSwwLjFsMC4xLDEuNWw0LjYtMC4zbC0wLjEtMS41bC0xLjgsMC4xYzAtMC4xLDAtMC4xLDAtMC4yYzAtMC4xLTAuMS0wLjItMC4yLTAuNGwtMC4xLTAuMmwwLjItMC4xCgljMC44LTAuNSwxLjgtMC44LDIuOC0wLjhjMC40LDAsMC44LDAsMS4yLDAuMWMwLDAsMC4xLDAsMC4xLDBsMC4xLDEuOGwxLjYtMC4xbC0wLjMtNC44bC0xLjYsMC4xbDAuMSwxLjVjLTAuMSwwLTAuMiwwLTAuMywwCgljMC4xLDAsMC4yLDAsMC4zLDBsMCwwLjJsMCwwYy0wLjQtMC4xLTAuOC0wLjEtMS4yLTAuMWMtMS4yLDAtMi40LDAuMy0zLjUsMWwtMC4yLDAuMWwtMC4xLTAuMWMtMS40LTEuNy0zLjQtMi43LTUuNi0yLjcKCWMtMS4zLDAtMi42LDAuNC0zLjcsMS4xbC0wLjMsMC4ybDAtMC40bDAtNC4ydi0ydi0xLjdsMC4yLDBjMCwwLDAuMSwwLDAuMSwwYzAuNywwLDEuOS0wLjEsMi45LTAuN2MxLjMtMC43LDIuMS0xLjcsMi40LTIuMwoJbDEuNSwwLjlsMC43LTEuM2wtMy45LTIuM0wyOSw5LjJsMS40LDAuOGMtMC4zLDAuNS0wLjksMS40LTEuOSwxLjljLTAuOSwwLjQtMS44LDAuNS0yLjQsMC41YzAsMC0wLjEsMC0wLjEsMGwtMC4yLDB2LTEuOWwwLDAKCWMwLTAuMywwLTEuMiwwLTJjMC40LTIuMywyLjQtNC4xLDQuOC00LjFjMi4zLDAsNC4yLDEuNiw0LjcsMy45YzAsMC4xLDAuMSwwLjIsMC4xLDAuMmwwLDBsMCwwYzAuMiwwLjYsMC4zLDEuNiwwLjEsMi4ybDAsMC4xCgljLTAuNCwxLjgtMC40LDEuOC0xLjIsMi43Yy0wLjIsMC4zLTAuNSwwLjYtMC45LDEuMWMwLDAtMC4xLDAuMS0wLjEsMC4xbC0xLjMtMS4ybC0xLjEsMS4ybDMuNywzLjRsMS4xLTEuMmwtMS4yLTEuMWMwLDAsMCwwLDAsMAoJYzAsMCwwLDAsMCwwbC0wLjItMC4xYzAuMS0wLjEsMC4xLTAuMSwwLjItMC4yYzAuMS0wLjEsMC41LTAuNiwxLjEtMS4yYzAuOC0xLjIsMS4zLTIuNiwxLjMtNGMwLTAuNCwwLTAuOC0wLjEtMS4xbDAtMC4zbDAuMywwCgljMS40LDAuMiwyLjgsMSwzLjcsMi4yYzEsMS4yLDEuNSwyLjcsMS41LDQuM2MwLDEtMC4yLDEuOS0wLjUsMi43Yy0wLjEsMC4yLTAuMSwwLjQsMCwwLjVjMC4xLDAuMiwwLjIsMC4zLDAuNCwwLjMKCWMyLjcsMC43LDQuNiwzLjMsNC42LDYuM0M0Ni42LDI3LjIsNDUuOSwyOC45LDQ0LjYsMzAuMnogTTEyLjUsNDAuOEwxMi41LDQwLjhMMTIuNSw0MC44YzAtMC4xLDAtMC4xLDAtMC4xYzAtMC4xLDAtMC4yLDAtMC4zCgljMC0xLjMsMC42LTIuNSwxLjYtMy4ybDAuMS0wLjFsMC4xLDAuMWMwLjksMC44LDIsMS4zLDMuMiwxLjVjMCwwLDAuMiwwLDAuNCwwYzAuOSwwLDEuNC0wLjEsMS41LTAuMWwwLDBsMCwwCgljMC41LTAuMSwxLTAuMywxLjUtMC42bDAuMSwwLjJjLTAuMSwwLTAuMSwwLjEtMC4yLDAuMWMwLjEsMCwwLjEtMC4xLDAuMi0wLjFsMC42LDEuNGwxLjQtMC42bC0xLjgtNC41TDE5LjgsMzVsMC43LDEuNwoJYy0wLjcsMC40LTEuNSwwLjctMi4zLDAuN2MtMS4zLDAtMi41LTAuNi0zLjMtMS41bDAsMGwwLDBsMCwwbDAsMGMtMC4xLTAuMS0wLjMtMC4yLTAuNS0wLjJjLTAuMSwwLTAuMiwwLTAuMywwLjEKCWMtMS42LDAuOC0yLjcsMi40LTIuOSw0LjNsMCwwLjJsLTAuMiwwYy0zLjQsMC02LjItMy4xLTYuMi02LjhjMC0wLjksMC4xLTEuNywwLjQtMi41YzAuMS0wLjIsMC0wLjUtMC4yLTAuN2MtMS4zLTEuMi0yLTMtMi00LjkKCWMwLTMsMS45LTUuNiw0LjYtNi4zYzAuMiwwLDAuMy0wLjIsMC40LTAuM2MwLjEtMC4yLDAuMS0wLjQsMC0wLjVjLTAuNC0wLjktMC41LTEuOC0wLjUtMi43YzAtMS42LDAuNS0zLjEsMS41LTQuMwoJYzEtMS4yLDIuMy0yLDMuNy0yLjJsMC4zLDBMMTMsOWMtMC4xLDAuNC0wLjEsMC44LTAuMSwxLjFjMCwxLjQsMC40LDIuOCwxLjMsNGMwLjYsMC42LDEsMS4yLDEuMSwxLjJjMC4xLDAuMSwwLjIsMC4yLDAuMiwwLjIKCWwtMC4yLDAuMWMwLDAsMCwwLDAsMGMwLDAsMCwwLDAsMGwtMS4yLDEuMWwxLjEsMS4ybDMuNy0zLjRsLTEuMS0xLjJsLTEuMywxLjJjMCwwLTAuMS0wLjEtMC4xLTAuMWMtMC40LTAuNS0wLjctMC44LTAuOS0xLjEKCWMtMC44LTAuOS0wLjgtMC45LTEuMi0yLjdsMC0wLjFjLTAuMS0wLjYsMC0xLjYsMC4xLTIuMmwwLDBsMCwwYzAuMS0wLjEsMC4xLTAuMiwwLjEtMC4yQzE1LDYsMTcsNC40LDE5LjIsNC40YzIuNywwLDQuOCwyLjIsNC44LDUKCWwwLDIuOWwwLDAuMmwtMC4yLDBjMCwwLTAuMSwwLTAuMSwwYy0wLjYsMC0xLjUtMC4xLTIuNC0wLjVjLTEtMC41LTEuNi0xLjQtMS45LTEuOWwwLjItMC4xYzAsMCwwLDAsMCwwLjFjMCwwLDAsMCwwLTAuMWwxLjItMC43CglsLTAuNy0xLjNsLTMuOSwyLjNsMC43LDEuM2wxLjUtMC45YzAuMywwLjYsMS4xLDEuNiwyLjQsMi4zYzEuMSwwLjYsMi4yLDAuNywyLjksMC43YzAsMCwwLjEsMCwwLjEsMGwwLjIsMGwwLDAuMmwwLDcuN2wwLDAuNAoJbC0wLjMtMC4yYy0xLjEtMC43LTIuNC0xLjEtMy43LTEuMWMtMi4yLDAtNC4yLDEtNS42LDIuN2wtMC4xLDAuMWwtMC4yLTAuMWMtMS0wLjYtMi4yLTEtMy41LTFjLTAuNCwwLTAuOCwwLTEuMiwwLjFsMCwwbDAuMS0xLjcKCWwtMS42LTAuMWwtMC4zLDQuOGwxLjYsMC4xbDAuMS0xLjhjMCwwLDAuMSwwLDAuMSwwYzAuNC0wLjEsMC44LTAuMSwxLjItMC4xYzEsMCwyLDAuMywyLjgsMC44bDAuMiwwLjFsLTAuMSwwLjIKCWMtMC4xLDAuMS0wLjEsMC4zLTAuMiwwLjRjMCwwLjEsMCwwLjEsMCwwLjJsLTEuOC0wLjFsLTAuMSwxLjVMMTYsMjdsMC4xLTEuNWwtMS41LTAuMWMxLTIuMiwzLjEtMy42LDUuNC0zLjZjMS41LDAsMi45LDAuNiw0LDEuNgoJbDAuMSwwLjFsMCwwLjFsMCw3LjlsMCwwLjJsLTAuMiwwYy0wLjIsMC0wLjQsMC0wLjYsMGMtMS45LDAtMy42LTEuMS00LjUtMi45Yy0wLjMtMC42LTAuNS0xLjMtMC41LTEuOWwwLDBsMCwwYzAtMC4xLDAtMC4yLDAtMC4zCgljMC0wLjEsMC0wLjEsMC0wLjJsMCwwbDAsMGMtMC4xLTAuMy0wLjMtMC41LTAuNi0wLjVjLTAuMywwLTAuNiwwLjMtMC42LDAuNmMwLDAuMSwwLDAuMiwwLDAuNGwwLDBsMCwwYy0wLjEsMi0xLjcsMy42LTMuNiwzLjdsMCwwCglsMCwwYy0wLjEsMC0wLjIsMC0wLjMsMGwwLDBoMGwwLDBjMCwwLDAsMCwwLDBjLTEuMywwLTIuMywxLTIuMywxbDAsMGwwLDBjLTAuOCwwLjctMS4zLDEuNi0xLjUsMi42bC0yLTAuMWwtMC4xLDEuNmw1LDAuNGwwLjEtMS42CglsLTEuNi0wLjFjMC0wLjEsMC4xLTAuMiwwLjEtMC4zYzAsMC4xLTAuMSwwLjItMC4xLDAuM2wtMC4yLDBjMC4yLTAuOSwwLjgtMS43LDEuNy0yLjFjMC4zLTAuMiwwLjctMC4yLDEtMC4zbDAsMGwwLDAKCWMwLjEsMCwwLjIsMCwwLjMsMGwwLDBoMGMwLDAsMC4xLDAsMC4xLDBjMS41LTAuMiwyLjktMSwzLjgtMi40bDAuMi0wLjNsMC4yLDAuM2MxLjEsMi4xLDMuMiwzLjQsNS41LDMuNGMwLjIsMCwwLjQsMCwwLjYsMGwwLjIsMAoJbDAsMC4ybC0wLjEsNy41djBsMCwwYy0wLjYsMi45LTIuOSw1LTUuNyw1QzE1LjUsNDUuNywxMy4xLDQzLjcsMTIuNSw0MC44eiIvPgo8L3N2Zz4K';
  // $md_page = add_menu_page('Mind Doodle', 'Mind Doodle', $cp, 'md_plugin', 'mdsm_include_md_page', MDSM_IMAGE_URL . 'logobrain_white16x16.png');
  $md_page = add_menu_page('Mind Doodle', 'Mind Doodle', $cp, 'md_plugin', 'mdsm_include_md_page', $md_icon);
  // $settings_page = add_submenu_page('md_plugin', 'Settings', 'Settings', $cp, 'md_plugin', 'mdsm_include_md_page', MDSM_IMAGE_URL . 'tasks.ico');
  
  if (current_user_can('edit_pages')) {
    $sitemap_page = add_submenu_page('md_plugin', 'Sitemap', 'Sitemap', $cp, 'sitemap', 'mdsm_include_sm_page', MDSM_IMAGE_URL.'fx.ico');
  }

  $tasks_page = add_submenu_page('md_plugin', 'Tasks', 'Tasks', $cp, 'md_tasks', 'mdsm_include_task_page', MDSM_IMAGE_URL . 'tasks.ico');

  if ($mdwp_options['doodle_id'] && $mdwp_options['user'] && $mdwp_options['selected_team']) {
      // $new_task_page = add_submenu_page('md_tasks', 'All Tasks', 'All Tasks', $cp, 'md_tasks', 'mdsm_include_task_page', MDSM_IMAGE_URL . 'tasks.ico');
      $new_tasks_page = add_submenu_page('md_plugin', 'Add New', 'Add New', $cp, 'md_task', 'mdsm_include_new_task', MDSM_IMAGE_URL . 'new_tasks.ico');
  }
}

add_action('admin_menu', 'mdsm_admin_menu');
add_action('admin_bar_menu', 'mdsm_add_toolbar_items', 100);

if (isset($_REQUEST['page']) && in_array( $_REQUEST['page'], array('md_tasks', 'md_task', 'sitemap', 'md_plugin') )) {
  add_filter('update_footer', 'my_footer_admin', 100);
}

function my_footer_admin( $default ) {
	ob_start();
  echo "
    <span class=\"anchor_SEO\">
      Powered by <a target=\"MindDoodle\" href=\"https://www.minddoodle.com\">Mind Doodle</a>
    </span>
  ";
  $content = ob_get_clean();
  return $content.' '.$default;
}



// Register custom gutenberg block
function load_sitemap_block() {
  wp_enqueue_style('MDSitemapStylesheet', MDSM_SM_PLUGIN_URL . 'assets/css/sitemap.css');
  wp_enqueue_script(
    'sitemap-block',
    // MDSM_PLUGIN_URL . 'gutenberg_custom_blocks/sitemap_block.js',
    MDSM_PLUGIN_URL . 'gutenberg_custom_blocks/sitemap_block/block.build.js',
    array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' )
  );
}
   
add_action('enqueue_block_editor_assets', 'load_sitemap_block');

