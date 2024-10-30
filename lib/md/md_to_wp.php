<?php 
if ( ! defined( 'ABSPATH' ) ) exit;

function mdsm_md_update ($request) {
  remove_action( 'post_updated', 'mdsm_page_update', 10 );
  remove_action( 'wp_insert_post', 'mdsm_page_create', 10 );

  $mdwp_options = get_option(MDSM_CONNECT_OPTION);
  $sitemap_options = get_option(MDSM_SITEMAP_OPTION);
  $autopublish = $sitemap_option['autopublish'];

  $parameters = $request->get_params();

  $result = '';
  $return = array(
    'error' => null,
    'result' => null
  );

  $post = array();
  switch ($parameters['action']) {
    case 'update':
      if (!isset($parameters['post_id'])) {
        break;
      }
      $post = get_post($parameters['post_id'], ARRAY_A);
      $post['ID'] = $parameters['post_id'];
    case 'create':
      $post['post_type'] = 'page';
      if (!$post['ID']) {
        $post['post_status'] = $autopublish ? 'publish' : 'draft';
      }

      if ($parameters['post_title']) { $post['post_title'] = urldecode($parameters['post_title']); }
      if ($parameters['post_content']) { $post['post_content'] = urldecode($parameters['post_content']); }
      if (isset($parameters['post_status'])) { $post['post_status'] = $parameters['post_status']; }
      if (isset($parameters['post_parent'])) { $post['post_parent'] = $parameters['post_parent']; }

      $result = wp_insert_post($post);
      if ($result) {
        if ($parameters['action'] === 'create') {
          $mdwp_options['nodes'][$result] = $parameters['md_node_id'];
        }
        if ($parameters['color']) {
          $sitemap_options['pages'][$result]['color'] = $parameters['color'];
        }
        if (isset($parameters['post_image'])) {
          $featured_image_id = attachment_url_to_postid($parameters['post_image']);
          if ($featured_image_id) {
            set_post_thumbnail($parameters['post_id'], $featured_image_id);
          } else {
            delete_post_thumbnail($parameters['post_id']);
          }
        } 
      }
    break;
    case 'delete':
      $delete_result = wp_delete_post($parameters['post_id'], true);
      if ($delete_result) {
        $result = $delete_result->ID;
        unset($mdwp_options['nodes'][$result]);
      }
    break;
    default:
      break;
  }

  update_option(MDSM_CONNECT_OPTION, $mdwp_options);
  update_option(MDSM_SITEMAP_OPTION, $sitemap_options);

  if ($result) {
    $return['result'] = $result;
  } else {
    $return['error'] = 'Can not update post.';
  }

  return $return;
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'MDWP/v1', 'md_post', array(
    array(
      'methods' => 'POST',
      'callback' => 'mdsm_md_update',
    ),
  )
 );
});