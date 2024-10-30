<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// function mdsm_recursive_up_order($post, $siblings, $set_order_to) {
//   echo $post->post_title.' --- '.$set_order_to.'<br/>';
//   $order = $set_order_to;
//   $conflict_nodes = array_filter($siblings, function($child) use ($order) {
//     return ($child->ID != $post->ID && $child->menu_order == $order);
//   });
  
//   $post->menu_order = $set_order_to;
//   wp_update_post( $post, true );
//   if (!empty($conflict_nodes)) {
//     foreach($conflict_nodes as $node) { 
//       mdsm_recursive_up_order($node, $siblings, ++$set_order_to);
//     }
//   }
// }

?>