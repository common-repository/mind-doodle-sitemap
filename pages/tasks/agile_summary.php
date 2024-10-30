<?php 
  if ( ! defined( 'ABSPATH' ) ) exit;

  global $total_grouped, $agile, $tasks;

  $agile = $mdwp_object -> execRequest('doodle/agile_board', 'GET', array('doodle_id' => $doodle_id, 'schema_id' => $schema_id, 'team_id' => $schema_id, 'nodes' => 1));

  $total_grouped = array('backlog' => 0, 'active' => 0);

  if ($agile && !mdsm_is_fx_error($agile)) {
    $tasks = $agile['work_items'];
    $users = $agile['users'];
    $nodes = $agile['nodes'];

    $node_grouped = [];
    $backlog_id = mdsm_get_backlog_sprint($sprints);

    if (!$_REQUEST['only_tasks']) {
      foreach($nodes as $node) {
        if ($node['parent']) {
          $node_grouped[$node['node_id']] = array('tasks' => [], 'backlog' => 0, 'active' => 0, 'title'=> $node['title']);
        }
      }
    }

    foreach($tasks as $task_id=>$task) {
      $completed = sizeof($agile['enums']['status']) - 1;
      if ($task['status'] !== $completed) {
        $node_id = $task['node'];
        if (!$node_grouped[$node_id]) { $node_grouped[$node_id] = array('tasks' => [], 'backlog' => 0, 'active' => 0, 'title'=> ''); }
        if (!$node_grouped[$node_id]['title']) { $node_grouped[$node_id]['title'] = mdsm_get_node_name($nodes, $node_id); }
        if ($task['sprint'] === $backlog_id) {
          $total_grouped['backlog']++;
          $node_grouped[$node_id]['backlog']++;
        } else {
          $total_grouped['active']++;
          $node_grouped[$node_id]['active']++;
        }
      }
    }
  }

  function mdsm_print_summary($header='Task Summary') {
    global $total_grouped, $href, $agile, $tasks;

    $return = '
      <div class="flex-row" style="align-items: baseline;">
        <h1 style="flex: 1 0; line-height: 1em;" class="wp-heading-inline primary">
          '.$header.'
        </h1>
        <a class="btn-link" target="MindDoodle" href="https://help.minddoodle.com">
          <span class="icon icon-md-help"></span>
          Help
        </a>
      </div>
      <div class="agile-summary flex-row">
        <div class="agile-summary__statistics">
          <table cellspacing="0" cellpadding="6">
            <tr class="bordered-bottom">
              <td width="100%">Total active tasks</td>
              <td class="centered"><span class="total-tasks total_active">'.esc_attr($total_grouped['active']).'</span></td>
            </tr>
            <tr class="bordered-bottom">
              <td>Total tasks in backlog</td>
              <td class="centered"><span class="total-tasks total_backlog">'.esc_attr($total_grouped['backlog']).'</span></td>
            </tr>
            <tr>
              <td>Task board</td>
              <td><a style="margin:0;" class="btn-link btn-wide green '. ((!$agile || mdsm_is_fx_error($agile) || !$href) ? "disabledLink" : "") .' " target="MindDoodle" href="'.$href.'">View</a></td>
            </tr>
          </table>
        </div>
        <div class="agile-summary__count">
          Task Count
          <div class="agile-summary__count__number centered">
            '.sizeof($tasks).'
          </div>
        </div>
      </div>
    ';

    echo $return;
  }
?>
