<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// function mdsm_menu_item_delete ($page_id) {
//   // echo 'delete '.$page_id; exit;
//   $menu = wp_get_nav_menu_object( MDSM_MENU_NAME );
//   if ($menu) {
//     $menu_items = wp_get_nav_menu_items($menu, array('post_status' => 'publish, draft, trash'));
//     foreach ($menu_items as $menu_item) {
//       echo $menu_item->object_id.'<br>';
//       if ($menu_item->object === 'page' && $menu_item->object_id === $page_id) {
//         wp_update_nav_menu_item( $menu, $menu_item->db_id, array(
//           'menu-item-object-id' =>  $menu_item->object_id,
//           'menu-item-status' => 'unpublish'
//         ));
//         break;
//       }
//     }
//   }
// }

function mdsm_get_parent_menu_item ($menu_items, $page) {
  $menu_parent = null;
  foreach ($menu_items as $menu_item) {
    if ($menu_item->object_id == $page->post_parent) {
      $menu_parent = $menu_item->ID;
      break;
    }
  }

  return $menu_parent;
}

function mdsm_get_menu_item ($menu_items, $page) {
  // $current_menu_item;
  foreach ($menu_items as $menu_item) {
    if ($menu_item->object_id == $page->ID) {
      // $current_menu_item = $menu_item;
      return $menu_item;
      break;
    }
  }

  // return $current_menu_item; 
}

function mdsm_menu_item_create ($page_id, $page) {
  $menu = wp_get_nav_menu_object( MDSM_MENU_NAME );
  if ($menu) {
    $menu_id = $menu->term_id;
    $menu_items = wp_get_nav_menu_items($menu, array('post_status' => 'publish, draft'));
    $already_exist = false;
    foreach ($menu_items as $menu_item) {
      if ($menu_item->object_id == $page_id) {
        $already_exist = true;
        break;
      }
    }
    if (!$already_exist) {
      $menu_parent = mdsm_get_parent_menu_item($menu_items, $page);

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
  }
}

function mdsm_menu_item_change_parent ($page_id, $page) {
  $menu = wp_get_nav_menu_object( MDSM_MENU_NAME );
  if ($menu) {
    $menu_id = $menu->term_id;
    $menu_items = wp_get_nav_menu_items($menu, array('post_status' => 'publish, draft'));
    $menu_parent = mdsm_get_parent_menu_item($menu_items, $page);
    $current_menu_item = mdsm_get_menu_item($menu_items, $page);

    if ($current_menu_item) {
      wp_update_nav_menu_item($menu_id, $current_menu_item->db_id, array(
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
  }
}

// $menu = wp_get_nav_menu_object( MDSM_MENU_NAME );
// $menu_items = wp_get_nav_menu_items($menu, array('post_status' => 'publish'));
// mdsm_print($menu_items);
// exit;



?>