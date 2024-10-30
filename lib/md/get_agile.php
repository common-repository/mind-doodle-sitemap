<?php 
  if ( ! defined( 'ABSPATH' ) ) exit;

  global $mdsm_plugin_settings, $mdwp_object, $agile, $md_user_id;

  // $md_options = get_option(MDSM_OPTIONS_NAME, []);
  $mdwp_options = get_option(MDSM_CONNECT_OPTION);
  $tasks = [];

  $doodle_id = $mdwp_options['doodle_id'];
  $schema_id = $mdwp_options['selected_team'];
  $md_url = $mdwp_options['selected_team_url'];

  if (current_user_can('manage_options')) {
    if (isset($_POST['delete_task']) && wp_verify_nonce( $_POST['nonce'], 'mdsm_delete_task_nonce' )) {
      $task_id = sanitize_key($_POST['delete_task']);
      if ($task_id) {
        $remove_task = $mdwp_object -> execRequest('doodle/agile_work_item', 'DELETE', array('doodle_id' => $doodle_id, 'schema_id' => $schema_id, 'team_id' => $schema_id, 'item_id' => $task_id));
        if (!mdsm_is_fx_error($remove_task)) {
          $msg_class = 'notice-success';
          $msg = 'Task has been removed';
        } else {
          $msg_class = 'notice-error mdsm_error';
          $msg = $remove_task -> get_error_message();
        }
      } else {
        $msg_class = 'notice-error mdsm_error';
        $msg = 'Wrong task ID';
      }
      echo '<div style="margin-left: 2px; margin-top: 2em;" class="notice '.$msg_class.'"><p>'.esc_attr($msg).'</p></div>';
    }
  }

  function mdsm_get_node($nodes, $node_id) {
    foreach ($nodes as $node) {
      if ($node['node_id'] == $node_id) {
        return $node;
        break;
      }
    }
  }

  function mdsm_get_node_name($nodes, $node_id) {
    $node_name = '';
    foreach ($nodes as $node) {
      if ($node['node_id'] == $node_id) {
        $node_name = urldecode($node['title']);
        break;
      }
    }
    return $node_name;
  }

  function mdsm_get_agile_nodes($nodes) {
    $nodes_list = [];
    foreach ($nodes as $node) {
      if ($node['parent']) {
        $nodes_list[] = array('node_id' => $node['node_id'], 'title' => urldecode($node['title']));
      }
    }
    return $nodes_list;
  }

  function mdsm_get_sprint_name($sprints, $sprint_id) {
    $sprint_name = '';
    foreach ($sprints as $sprint) {
      if ($sprint['object_id'] == $sprint_id) {
        $sprint_name = urldecode($sprint['title']);
        break;
      }
    }
    return $sprint_name;
  }

  function mdsm_get_backlog_sprint($sprints) {
    $sprint_id = 0;
    foreach ($sprints as $sprint) {
      if ($sprint['is_backlog']) {
        $sprint_id = $sprint['object_id'];
        break;
      }
    }
    return $sprint_id;
  }

  function mdsm_get_enum_field($enums, $enum, $name, $full = false) {
    foreach ($enums[$enum] as $selected_enum) {
      if ($selected_enum['value'] == $name) {
        return $full ? $selected_enum : $selected_enum['label'];
      }
    }
  }

  function mdsm_get_agile_users($users) {
    $users_list = [];
    foreach($users as $role => $role_users) {
      $users_list = array_merge($users_list, $role_users);
    }
    return $users_list;
  }

  function mdsm_get_user_name($users, $user_id) {
    $user_display_name = '';
    foreach ($users as $user) {
      if ($user['sfx_id'] === $user_id) {
        $user_display_name = $user['name'];
        break;
      }
    }
    return $user_display_name;
  }

  function mdsm_get_current_team_url($teams, $team_id) {
    foreach($teams as $team) {
      if ($team['team_id'] == $team_id) {
        return $team['link'];
      }
    }
  }

  if (current_user_can('manage_options')) {
    $agile = $mdwp_object -> execRequest('doodle/agile_board', 'GET', array('doodle_id' => $doodle_id, 'schema_id' => $schema_id, 'team_id' => $schema_id, 'nodes' => 1));
  }
?>