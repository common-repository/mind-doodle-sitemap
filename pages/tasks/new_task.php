<?php
  if ( ! defined( 'ABSPATH' ) ) exit;
  global $mdsm_plugin_settings, $mdwp_options, $mdwp_object, $agile, $md_user_id, $href;

  if ($mdwp_options['selected_team_url'] && $mdwp_options['doodle_id']) {
    $href = $mdwp_options['selected_team_url']."/doodle/".$mdwp_options['doodle_id']."/agileboard";
  }

  require_once(MDSM_SM_PLUGIN_DIR . '/lib/md/get_agile.php');

  wp_enqueue_style('LoadingStyles', MDSM_SM_PLUGIN_URL . 'assets/css/loading/loading.css');
  wp_enqueue_style('LoadingStylesBtn', MDSM_SM_PLUGIN_URL . 'assets/css/loading/loading-btn.css');

  if (!mdsm_is_fx_error($agile)) {
    $users = $agile['users'];
    $enums = $agile['enums'];
    $nodes = $agile['nodes'];
    $sprints = $agile['sprints'];
    $tasks = $agile['work_items'];

    $users_list = mdsm_get_agile_users($users);
    $nodes_list = mdsm_get_agile_nodes($nodes);

    usort($nodes_list, function($a, $b) {
      if (mb_strtolower($a['title']) ==  mb_strtolower($b['title'])) {
        return 0;
      }
      return (mb_strtolower($a['title']) < mb_strtolower($b['title'])) ? -1 : 1;
    });

    usort($users_list, function($a, $b) {
      if (mb_strtolower($a['name']) ==  mb_strtolower($b['name'])) {
        return 0;
      }
      return (mb_strtolower($a['name']) < mb_strtolower($b['name'])) ? -1 : 1;
    });

    if (isset($_POST) && count($_POST) > 0) {
      if (current_user_can('manage_options')) {
        if (isset($_POST['add_task']) && wp_verify_nonce($_POST['nonce'], 'mdsm_new_task_nonce')) {
          $notes = nl2br(sanitize_textarea_field($_POST['notes']));
          $request_array = array(
            'doodle_id' => $doodle_id,
            'schema_id' => $schema_id,
            'team_id' => $schema_id,
            'node_id' => sanitize_key($_POST['node']),
            'sprint_id' => mdsm_get_backlog_sprint($agile['sprints']),
            'assigned_to' => sanitize_key($_POST['assigned_to']),
            'item' => array(
              'title' => sanitize_text_field($_POST['title']),
              'notes' => $notes,
              'priority' => sanitize_key($_POST['priority']),
              'item_type' => sanitize_key($_POST['item_type']),
              'estimate' => sanitize_key($_POST['estimate']),
              'remaining' => sanitize_key($_POST['estimate']),
              'status' => $enums['status'][0]['value']
            )
          );


          if (isset($_POST['task'])) {
            $method = 'PUT';
            $request_array['item']['item_id'] = sanitize_key($_POST['task']);
            $request_array['item']['node'] = $request_array['node_id'];
            $request_array['item']['assigned_to'] = $request_array['assigned_to'];
            $request_array['items'] = [$request_array['item']];
          } else {
            $method= 'POST';
          }

          $added_task = $mdwp_object -> execRequest('doodle/agile_work_item', $method, $request_array);
          if (mdsm_is_fx_error($added_task)) {
            $msg_class = 'notice-error mdsm_error';
            $msg = $added_task -> get_error_message();
          } else {
            if (!isset($_POST['task'])) {
              unset($_POST); $_POST= [];
            }
            $msg_class = 'notice-success';
            $msg = 'Task has been saved';
          }
          echo '<div style="margin-left: 2px; margin-top: 2em;" class="notice '.$msg_class.'"><p>'.esc_attr($msg).'</p></div>';
        }
      }
    }

    $predefined_values = [];

    if (isset($_GET['task'])) {
      $task = $tasks[$_GET['task']];
      if ($task) {
        $predefined_values = $task;
      }
    }
    if (isset($_POST) && count($_POST) > 0) {
      $predefined_values = array_merge($predefined_values, $_POST);
    }
    // mdsm_print($agile['options']); 
    $default_measurement_unit = $enums['estimation_measurement_unit'][0]['label'];
    $measurement_unit = ($agile['options']['estimation_measurement_unit'] && $agile['options']['estimation_measurement_unit']['label']) ? $agile['options']['estimation_measurement_unit']['label'] : $default_measurement_unit;
    // echo $measurement_unit; exit;
  ?>

<?php 
require_once(dirname(__FILE__).'/agile_summary.php');
if (isset($_GET['noheader'])) {
  require_once(ABSPATH . 'wp-admin/admin-header.php');
}
?>
    <div class="agile-wrap">
      <?php 
        $header = '
          <span style="display: inline-block; position: relative;">Add Task</span>
          <a style="display: inline; font-size: initial; margin: 0; position: relative; left: 10px; top: -3px; line-height: initial;" class="btn-link btn-wide green" href="?page=md_tasks">Back</a>
        ';
        echo mdsm_print_summary($header);
      ?>
    </div>

    <div class="agile-wrap content-block" style="margin-top: 2em;">
      
      <div class="content-block__header">Task</div>
      <div class="content-block__body" style="display: flex; flex-flow: wrap row">
        <div class="sidebar-info">
          <h3>Add Task</h3>
          <div>
            This tool allows you to add tasks to your WordPress pages.
            Assign tasks to yourself or other team members for implementation.
            Manage tasks from within Mind Doodle using the agile task board.
            Use Mind Doodle to simplify project management activities and team communication.
          </div>
        </div>

        <div class="add-task-wrap">
          <form method="POST" id="save_task_form">
            <input type="hidden" name="add_task">
            <input type="hidden" name="nonce" value=<?php echo wp_create_nonce('mdsm_new_task_nonce'); ?>>
            <?php
              if ($_GET['task']) {
                echo '<input type="hidden" name="task" value="'.$_GET['task'].'"/>';
              }
            ?>
            <div class="task-field">
              <div class="task-field__name">Web Page</div>
              <div class="task-field__description">
                Select the WordPress page that the task is related to.
                Tasks are used to describe functionality and updates that need to be applied to a specific page.
              </div>
              <div class="task-field__input">
                <select name="node">
                  <?php mdsm_show_select_options($nodes_list, 'node_id', 'title', $predefined_values['node'] ?: $_GET['idea']); ?>
                </select>
              </div>
            </div>

            <div class="task-field">
              <div class="task-field__name">Task Title</div>
              <div class="task-field__description">Add a title to summarise your task.</div>
              <div class="task-field__input">
                <input type="text" name="title" placeholder="Enter title" value="<?php echo $predefined_values['title'] ? esc_html(urldecode($predefined_values['title'])) : '';?>">
              </div>
            </div>

            <div class="task-field">
              <div class="task-field__name">Task Type<span class="asterix">*</span></div>
              <div class="task-field__description">Select the type of task you want to add.</div>
              <div class="task-field__input content-row wrap">
                <?php 
                foreach ($enums['item_type'] as $item_type) {
                  $selected = $predefined_values['item_type'] || $predefined_values['item_type'] == 0
                  ? $item_type['value'] == $predefined_values['item_type'] ? 'checked="checked"' : ''
                  : $item_type['is_default'] ? 'checked="checked"' : '';
                  echo '
                    <div class="radio">
                      <label for="item_type_'.$item_type['value'].'">'.esc_attr($item_type['label']).'</label>
                      <input style="color:'.$item_type['color'].'; background-color:'.$item_type['color'].';" type="radio" name="item_type" id="item_type_'.$item_type['value'].'" value="'.$item_type['value'].'" '.$selected.'/>
                    </div>
                  ';
                }
                ?>
              </div>
            </div>

            <div class="task-field">
              <div class="task-field__name">Task Priority<span class="asterix">*</span></div>
              <div class="task-field__description">Specify the importance of this task so that you and your team can identify high-priority tasks that need to be tackled first.</div>
              <div class="task-field__input content-row wrap">
                <?php 
                foreach ($enums['priority'] as $priority) {
                  $selected = $predefined_values['priority'] || $predefined_values['priority'] == 0
                  ? $priority['value'] == $predefined_values['priority'] ? 'checked="checked"' : ''
                  : $priority['is_default'] ? 'checked="checked"' : '';
                  echo '
                    <div class="radio">
                      <label for="priority_'.$priority['value'].'">'.esc_attr($priority['label']).'</label>
                      <input style="color:'.$priority['color'].'; background-color:'.$priority['color'].';" type="radio" name="priority" id="priority_'.$priority['value'].'" value="'.$priority['value'].'" '.$selected.'/>
                    </div>
                  ';
                }
                ?>
              </div>
            </div>

            <div class="task-field">
              <div class="task-field__name">Task Description</div>
              <div class="task-field__description">
                Add a detailed description for you task, explaining what needs to be done and the business value.
                If this task relates to a bug, specify the steps to reproduce the problem.
              </div>
              <?php 
                $description = $predefined_values['notes'] ?: '';
                $description = urldecode($description);
                $description = strip_tags($description, '<div><p><li><br />');
                $description = str_replace('<p>', '', str_replace('</p>', '&#13;', $description));
                $description = str_replace('<li>', '', str_replace('</li>', '&#13;', $description));
                $description = str_replace('<div>', '', str_replace('</div>', '&#13;', $description));
              ?>
              <div class="task-field__input">
                <textarea rows="5" name="notes" placeholder="Enter description"><?php echo esc_html($description);?></textarea>
              </div>
            </div>

            <div class="task-field">
              <div class="task-field__name">Task Estimate (<?php echo $measurement_unit; ?>)<span class="asterix">*</span></div>
              <div class="task-field__description">
                Add a time estimate for how long this task will take to complete.
                If you are adding this task for another team member to implement, you can leave it blank for the person assigned to the task to set the estimate.
              </div>
              <div class="task-field__input">
                <input type="number" min="0" step="1" name="estimate" placeholder="Enter estimate (<?php echo esc_attr($measurement_unit); ?>)" value="<?php echo $predefined_values['estimate'] ? esc_attr($predefined_values['estimate']) : '';?>">
              </div>
            </div>

            <div class="task-field">
              <div class="task-field__name">Assigned Team Member</div>
              <div class="task-field__description">
                Choose the team member who will be responsible for completing the task.
              </div>
              <div class="task-field__input">
                <select name="assigned_to">
                  <?php mdsm_show_select_options($users_list, 'sfx_id', 'name', $predefined_values['assigned_to'] ?: ''); ?>
                </select>
              </div>
            </div>

            <div style="color: red;">
              <span class="asterix" style="font-size: 1em">*</span>Customisable in Mind Doodle.
            </div>

            <div class="btn-link green ld-ext-right" onClick="mdsm_save_task(this)">
              Save Task
              <div class="ld ld-ring ld-spin"></div>
            </div>
            <!-- <input type="button" class="btn-link green" onClick="mdsm_save_task(this)" value="Save Task"/> -->
          </form>
        </div>
      </div>
    </div>

<?php
  }
?>