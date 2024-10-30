<?php
  if ( ! defined( 'ABSPATH' ) ) exit;
  
  $node_tasks = [];
  $node_id = $_GET['idea'];
  

  foreach($tasks as $task_id=>$task) {
    if ($task['node'] == $node_id) {
      $node_tasks[] = array(
        'task_id' => $task['object_id'],
        'title' => $task['title'],
        'sprint' => mdsm_get_sprint_name($sprints, $task['sprint']),
        'who' => mdsm_get_user_name($users_list, $task['assigned_to']),
        'status' => mdsm_get_enum_field($enums, 'status', $task['status']),
        'priority' => mdsm_get_enum_field($enums, 'priority', $task['priority'], true),
        'type' => mdsm_get_enum_field($enums, 'item_type', $task['item_type'])
      );
    }
  }
  usort($node_tasks, function($a, $b) {
    if ($a['sprint'] ==  $b['sprint']) {
      return 0;
    }
    return ($a['sprint'] < $b['sprint']) ? -1 : 1;
  });
?>
<div>
  <a href="<?php echo $new_task_link;?>" class="btn-link green">
    <span class="icon icon-md-node-expand"></span>
    Add New
  </a>
  <a href="<?php echo $href;?>" target="MindDoodle" class="btn-link blue">
    <span class="icon icon-md-view"></span>
    Agile Task Board
  </a>
    <a href="?page=md_tasks" class="btn-link">
      <!-- <span class="icon icon-md-move-left"></span> -->
      Back
    </a>
</div>


<table class="md_table">
  <thead>
    <tr>
      <th width="40%">Task</th>
      <th width="20%">Sprint Name</th>
      <th width="20%">Who</th>
      <th width="10%">Status</th>
      <th width="10%">Priority</th>
      <th width="10%">Type</th>
      <th width="10%">Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php 
  if (count($node_tasks) > 0) { 
    foreach ($node_tasks as $row) {
  ?>
    <tr>
      <td><?php echo esc_attr(urldecode($row['title'])); ?></td>
      <td class="centered bold"><?php echo esc_attr($row['sprint']); ?></td>
      <td class="centered bold"><?php echo $row['who'] ? esc_attr($row['who']) : '<div class="non_assigned">Non assigned</div>'; ?></td>
      <td class="centered bold" style="white-space: nowrap"><?php echo esc_attr($row['status']); ?></td>
      <td class="centered bold" style="white-space: nowrap; border-top: solid 5px transparent; border-bottom: solid 5px <?php echo esc_attr($row['priority']['color']);?>"><?php echo esc_attr($row['priority']['label']); ?></td>
      <td class="centered bold" style="white-space: nowrap"><?php echo esc_attr($row['type']); ?></td>
      <td class="actions">
        <div class="flex-row">
          <div class="centered">
            <a class="btn-link blue" href="<?php echo "?page=md_task&task=".$row['task_id']; ?>">
              <span class="icon icon-md-edit"></span>
              Edit
            </a>
          </div>
          <div class="centered">
            <a target="MindDoodle" class="btn-link blue" href="<?php echo esc_attr($md_url."/doodle/".$doodle_id."/agileboard/task/".$row['task_id']); ?>">
              <span class="icon icon-md-cog"></span>
              Manage
            </a>
          </div>
          <div class="centered">
            <form method="POST">
              <input type="hidden" name="delete_task" value="<?php echo esc_attr($row['task_id']); ?>"/>
              <input type="hidden" name="nonce" value=<?php echo wp_create_nonce('mdsm_delete_task_nonce'); ?>>
              <button type="submit" class="btn-link red">
                <span class="icon icon-md-delete"></span>
                Delete
              </button>
            </form>
          </div>
        </div>
      </td>
    </tr>
  <?php }
  } else {
    ?>
      <tr>
        <td colspan=7 style="text-align: center; font-style: italic">No tasks for this page.</td>
      </tr>
    <?php
  }
  ?>
  </tbody>
</table>
