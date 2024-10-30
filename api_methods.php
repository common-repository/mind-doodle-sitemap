<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_get_plugin_settings', 'mdsm_sitemap_get_plugin_settings' );
add_action('wp_ajax_set_plugin_settings', 'mdsm_sitemap_set_plugin_settings' );
add_action('wp_ajax_update_pages', 'mdsm_sitemap_update_pages' );
add_action('wp_ajax_update_page', 'mdsm_sitemap_update_page' );
add_action('wp_ajax_untrash_page', 'mdsm_sitemap_untrash_page' );
add_action('wp_ajax_get_wp_pages', 'mdsm_sitemap_get_wp_pages');
add_action('wp_ajax_get_wp_page', 'mdsm_sitemap_get_wp_page');
add_action('wp_ajax_change_node_order', 'mdsm_sitemap_change_node_order' );


/* api calls */

function mdsm_sitemap_get_plugin_settings () {
  echo $options = json_encode(array_merge(get_option(MDSM_SITEMAP_OPTION, (array)[]), get_option(MDSM_CONNECT_OPTION, (array)[])));

  wp_die(); 
}

function mdsm_sitemap_get_wp_pages($data) {
  ob_clean();

  $pages_data = [
    'post_type' => 'page',
    'post_status' => 'publish'
  ];
  if ($_REQUEST['exclude']) {
    $pages_data['exclude'] = $_REQUEST['exclude'];
  }
  $pages = get_pages($pages_data);

  foreach ($pages as $page) {
    $page->link = get_permalink( $page->ID );
  }

  echo json_encode($pages);
  wp_die();
}

function mdsm_sitemap_get_wp_page($data) {
  ob_clean();

  if (wp_verify_nonce($_POST['wpnonce'], 'mdsm_update_options')) {
    $page_id = sanitize_key($_POST['pageId']);
    $url = get_permalink( $page_id );
    echo json_encode($url);
  }

  wp_die();
}

function mdsm_sitemap_update_settings ($data) {
  ob_clean();

  if (wp_verify_nonce($_POST['wpnonce'], 'mdsm_update_options')) {
    update_option(MDSM_SITEMAP_OPTION, $data);

    return get_option(MDSM_SITEMAP_OPTION);
  }
}

function mdsm_sitemap_set_plugin_settings () {
  if (wp_verify_nonce($_POST['wpnonce'], 'mdsm_update_options')) {
    $sitemap_data = get_option(MDSM_SITEMAP_OPTION);
    $merged = mdsm_sitemap_array_merge_recursive_ex($sitemap_data, mdsm_array_sanitize($_POST['data']));

    $result = mdsm_sitemap_update_settings($merged);

    echo json_encode($result);
  }
// 
  wp_die();
}

function mdsm_sitemap_update_pages () {
  if (wp_verify_nonce($_POST['wpnonce'], 'mdsm_update_options')) {
    $options = mdsm_sitemap_update_settings(['pages' => mdsm_array_sanitize($_POST['data'])]);

    echo json_encode($options['pages']);
  }

  wp_die();

}

function mdsm_sitemap_update_page () {
  if (wp_verify_nonce($_POST['wpnonce'], 'mdsm_update_options')) {
    $existing_options = get_option(MDSM_SITEMAP_OPTION);
    
    $changes = mdsm_array_sanitize($_POST['changes']);

    $update = [
      'pages' => [
        sanitize_key($_POST['pageId']) => $changes
      ]
    ];

    $merged = mdsm_sitemap_array_merge_recursive_ex($existing_options, $update);

    $options = mdsm_sitemap_update_settings($merged);

    echo json_encode($options['pages']);
  }

  wp_die();

}

function mdsm_sitemap_untrash_page () {
  if (wp_verify_nonce($_POST['wpnonce'], 'mdsm_update_options')) {
    $page_id = sanitize_key($_POST['pageId']);
    $post_status = get_post_meta($page_id, '_wp_trash_meta_status', true);
    if (wp_untrash_post($page_id)) {
      echo json_encode($post_status);
    }
  }

  wp_die();
}

function mdsm_sitemap_change_node_order () {
  require_once(MDSM_PLUGIN_DIR . './lib/page_reorder.php');

  if (wp_verify_nonce($_POST['wpnonce'], 'mdsm_update_options')) {
    $page_id = sanitize_key($_POST['nodeId']);
    $move_order = sanitize_key($_POST['moveOrder']);
    $existing_options = get_option(MDSM_SITEMAP_OPTION);
    $post = get_post($page_id);
    $parent_id = $post->post_parent;
    $children_pages = get_pages([
      'post_type' => 'page',
      'post_status' => 'publish,draft,trash',
      'sort_column' => 'menu_order',
      'parent' => $parent_id
    ]);
    usort($children_pages, function($a, $b) {
      return $a->menu_order - $b->menu_order;
    });
    $sorted_ids = array_map(function($a) {
      return $a->ID;
    }, $children_pages);
    // mdsm_print($children_pages);
    $index = array_search($page_id, $sorted_ids);

    if ($index || $index == 0) {
      switch ($move_order) {
        case 'top':
          array_splice($sorted_ids, $index, 1);
          array_unshift($sorted_ids, (int)$page_id);
        break;
        case 'bottom':
          array_splice($sorted_ids, $index, 1);
          array_push($sorted_ids, (int)$page_id);
        break;
        case 'up':
          array_splice($sorted_ids, $index, 1);
          array_splice($sorted_ids, $index-1, 0, (int)$page_id);
        break;
        case 'down':
          array_splice($sorted_ids, $index, 1);
          array_splice($sorted_ids, $index+1, 0, (int)$page_id);
        break;
      }
      foreach ($sorted_ids as $key=>$ordered_node_id) {
        // echo $key, $ordered_node_id;
        $current_post = array_filter($children_pages, function($page) use ($ordered_node_id) {
          return $page->ID == $ordered_node_id;
        });
        $current_post = current($current_post);
        if ($current_post->menu_order !== $key) {
          // echo 'update '.$current_post->post_title.' order from '.$current_post->menu_order. ' to '.$key.'<br/>';
          wp_update_post( array('ID'=>$ordered_node_id, 'menu_order'=>$key) );
        } 
      }

      echo json_encode($sorted_ids);
    }

    // echo $page_id, $move_order;
    // if ($move_order === 'top') {
    //   $set_order_to = 0;
    //   mdsm_recursive_up_order($post, $children_pages, $set_order_to);
      // mdsm_print($children_pages);
    // }


    wp_die();
  }
}