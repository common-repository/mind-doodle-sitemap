<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $mdwp_object, $mdwp_options, $sitemap_options;
$mdwp_options = get_option(MDSM_CONNECT_OPTION);
$sitemap_options = get_option(MDSM_SITEMAP_OPTION);

function mdsm_get_color($post) {
  global $mdwp_object, $mdwp_options, $sitemap_options;
  $newColor = $sitemap_options['pages'][$post->ID]['color'];

  $DEFAULT_COLORS = array('535353', 'ce74c6', '4ab2d4', '829a50', 'cf3b3e', 'd58d3f', '9174bc', '5fb48b', 'ad9f78', 'c6c6c6', 'e8bee4', '9ecfdf', 'b5c496', 'f3afb1', 'e2ae77', 'bca7dc', 'a3dfc2', 'ddd6c4');
  $color = $post->post_parent
    ? $sitemap_options['pages'][$post->post_parent]['color']
    : $DEFAULT_COLORS[array_rand($DEFAULT_COLORS)];
  ;

  return $color;
}

function mdsm_node_recolor($post, $color, &$child_nodes) {
  global $mdwp_object, $mdwp_options, $sitemap_options;
  
  $children = get_children('post_type=page&post_parent='+$post->ID);
  
  if (sizeof($children)) {
    foreach ($children as $child) {
      $child_nodes[] = array(
        'node_id' => $mdwp_options['nodes'][$child->ID],
        'color' => $color,
        'options'=>array(
          'wp'=>array(
            'post_status'=>$child->post_status,
            'post_parent'=>$child->post_parent,
            'id'=> $child->ID,
            'post_content' => get_permalink($child->ID)
          )
        )
      );
      $sitemap_options['pages'][$child->ID] = array('color' => $color);
      update_option(MDSM_SITEMAP_OPTION, $sitemap_options);
      mdsm_node_recolor($child, $color, $child_nodes);
    }
  }
}

function mdsm_node_create($post_id, $post_after) {
  global $mdwp_object, $mdwp_options, $sitemap_options;

  $parent_id = $post_after->post_parent ? $mdwp_options['nodes'][$post_after->post_parent] : $mdwp_options['nodes']['root'];
  $color = mdsm_get_color($post_after);

  $node = array(
    'name' => $post_after->post_title ?: '(no title)',
    'doodle_id' => $mdwp_options['doodle_id'],
    'team_id'=>$mdwp_options['selected_team'],
    'schema_id'=>$mdwp_options['selected_team'],
    'parent'=>$parent_id,
    'color'=>$color,
    'tmp_id'=>$post_id,
    'options'=>array(
      'wp'=>array(
        'id'=>$post_id,
        'post_status'=>$post_after->post_status,
        'post_parent'=>$post_after->post_parent,
        'post_content' => get_permalink($post_after->ID)
      )
    )
  );

  if (current_user_can('manage_options')) {
    $add_result = $mdwp_object -> execRequest('doodle/node', 'POST', $node);

    if (!mdsm_is_fx_error($add_result)) {
      $mdwp_options['nodes'][$post_id] = $add_result['node_id'];
      $sitemap_options['pages'][$post_id] = array('color' => $color);
      update_option(MDSM_SITEMAP_OPTION, $sitemap_options);
      update_option(MDSM_CONNECT_OPTION, $mdwp_options);
      
      if (explode('.', get_bloginfo('version'))[0] < 5) {
        if (has_post_thumbnail($post_id)) {
          mdsm_node_update($post_id, $post_after);
        }
      }
    }

    return $add_result;
  } else {
    return;
  }
}

function mdsm_node_update($post_id, $post_after, $post_before) {
  global $mdwp_object, $mdwp_options, $sitemap_options;

  $parent_id = $post_after->post_parent ? $mdwp_options['nodes'][$post_after->post_parent] : $mdwp_options['nodes']['root'];
  $node = array(
    'node_id' => $mdwp_options['nodes'][$post_id],
    'name' => rawurlencode($post_after->post_title ?: '(no title)'),
    'options'=>array(
      'wp'=>array(
        'post_status'=>$post_after->post_status,
        'post_parent'=>$post_after->post_parent,
        'id'=> $post_id,
        'post_content' => get_permalink($post_after->ID)
      )
    )
  );
  $child_nodes = [];
  if ($post_before->post_parent !== $post_after->post_parent) {  // PARENT CHANGE
    $node['parent'] = $post_after->post_parent ? $mdwp_options['nodes'][$post_after->post_parent] : $mdwp_options['nodes']['root'];
    $node['parent_old'] = $post_before->post_parent ? $mdwp_options['nodes'][$post_before->post_parent] : $mdwp_options['nodes']['root'];
    if ($post_after->post_parent) {
      $color = mdsm_get_color($post_after);
    } else {
      $color = $sitemap_options['pages'][$post_after->ID]['color'];
    }
    $node['color'] = $color;
    $sitemap_options['pages'][$post_id] = array('color' => $color);
    mdsm_node_recolor($post_after, $color, $child_nodes);
  }

  if (explode('.', get_bloginfo('version'))[0] < 5) {
    if (has_post_thumbnail($post_after)) {
      $image = get_the_post_thumbnail_url($post_after, 'medium');
      $node['featured_image'] = $image;
    } else {
      $node['featured_image'] = '';
    }
  }


  $nodes = array_merge(array($node), $child_nodes);
  $node_properties = array(
    'nodes'=> $nodes,
    'doodle_id' => $mdwp_options['doodle_id'],
    'team_id'=>$mdwp_options['selected_team'],
    'schema_id'=>$mdwp_options['selected_team'],
  );
  if (current_user_can('manage_options')) {
    $update_result = $mdwp_object -> execRequest('doodle/node_properties', 'PUT', $node_properties);

    if (!mdsm_is_fx_error($update_result) && $post_before->post_parent !== $post_after->post_parent) {
      update_option(MDSM_SITEMAP_OPTION, $sitemap_options);
    }

    return $update_result;
  } else {
    return;
  }
}

function mdsm_node_delete($post_id) {
  global $mdwp_object, $mdwp_options;
  $post = get_post($postid);
  $parent_id = $post->post_parent;
  $parent_node_id = $parent_id ? $mdwp_options['nodes'][$parent_id] : $mdwp_options['nodes']['root'];
  $node = array(
    'node_id' => $mdwp_options['nodes'][$post_id],
    'doodle_id' => $mdwp_options['doodle_id'],
    'team_id'=>$mdwp_options['selected_team'],
    'schema_id'=>$mdwp_options['selected_team'],
    'keep_children'=>$parent_node_id,
    'options'=>array(
      'wp'=>array(
        'id'=> $post_id
      )
    )
  );
  if (current_user_can('manage_options')) {
    $delete_result = $mdwp_object -> execRequest('doodle/node', 'DELETE', $node);

    if (!mdsm_is_fx_error($delete_result)) {
      unset($mdwp_options['nodes'][$post_id]);
      update_option(MDSM_CONNECT_OPTION, $mdwp_options);
    }

    return $delete_result;
  } else {
    return;
  }
}

function mdsm_page_update($post_id, $post_after, $post_before) {
  global $mdwp_object;
  $existing_statuses = array('publish', 'draft', 'trash');


  $post = get_post($post_id);
  if ($post_after->post_type === 'page') {
    
    // DELETE NODE
    
    if ( in_array($post_before->post_status, $existing_statuses) && !in_array($post_after->post_status, $existing_statuses)) {
      mdsm_node_delete($post_id, $post_after, $post_before);
      // mdsm_menu_item_delete($post_id);
    }

    // ADD NODE
    if ( !in_array($post_before->post_status, $existing_statuses) && in_array($post_after->post_status, $existing_statuses)) {
    // if ($post_before->post_status !== 'publish' && $post_after->post_status === 'publish') {
      mdsm_node_create($post_id, $post_after);
    }

    // UPDATE NODE

    if ( in_array($post_before->post_status, $existing_statuses) && in_array($post_after->post_status, $existing_statuses)) {
    // if ($post_before->post_status === 'publish' && $post_after->post_status === 'publish') {
      mdsm_node_update($post_id, $post_after, $post_before);
      if ($post_before->post_parent !== $post_after->post_parent) {
        mdsm_menu_item_change_parent($post_id, $post_after);
      }
    }
  }
}

function mdsm_page_create($post_id, $post, $update) {
  $existing_statuses = array('publish', 'draft');
  if (in_array($post->post_status, $existing_statuses) && $post->post_type === 'page' && !$update) {
    mdsm_node_create($post_id, $post);
    mdsm_menu_item_create($post_id, $post);
  }
}

function mdsm_page_delete($post_id) {
  // echo '__!!__';
  // echo $post_id; exit;
  mdsm_node_delete($post_id);
}

function mdsm_set_node_featured_image($post_id, $featured_id, $post) {
  global $mdwp_object, $mdwp_options, $sitemap_options;

  $node = array(
    'node_id' => $mdwp_options['nodes'][$post_id],
    'options'=>array(
      'wp'=>array(
        'post_status'=>$post->post_status,
        'post_parent'=>$post->post_parent,
        'id'=> $post_id,
        'post_content' => get_permalink($post->ID)
      )
    )
  );

  if ($featured_id) {
    $node['featured_image'] = wp_get_attachment_image_url($featured_id, 'medium');
  } else {
    $node['featured_image'] = '';
  }


  $node_properties = array(
    'nodes'=> array($node),
    'doodle_id' => $mdwp_options['doodle_id'],
    'team_id'=>$mdwp_options['selected_team'],
    'schema_id'=>$mdwp_options['selected_team'],
  );
  if (current_user_can('manage_options')) {
    $update_result = $mdwp_object -> execRequest('doodle/node_properties', 'PUT', $node_properties);
    return $update_result;
  } else {
    return;
  }
}

function mdsm_change_post_meta($meta_id, $post_id, $meta_key, $meta_value) {
  if ($meta_key === '_thumbnail_id') {

    $post = get_post($post_id);
    if ($post->post_type === 'page') {
      mdsm_set_node_featured_image($post_id, $meta_value, $post);
    }
  }
}

add_action( 'post_updated', 'mdsm_page_update', 10, 3 );
add_action( 'wp_insert_post', 'mdsm_page_create', 10, 3 );
add_action( 'before_delete_post', 'mdsm_page_delete', 10 );

if (explode('.', get_bloginfo('version'))[0] >= 5) {
  add_action( 'added_post_meta', 'mdsm_change_post_meta', 10, 4 );
  add_action( 'updated_post_meta', 'mdsm_change_post_meta', 10, 4 );
  add_action( 'deleted_post_meta', 'mdsm_change_post_meta', 10, 4 );
}