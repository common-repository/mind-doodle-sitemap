<?php
  if ( ! defined( 'ABSPATH' ) ) exit;
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
    <form method="GET" style="display: inline-block; margin-left: 1em">
      <input type="hidden" name="page" value="md_tasks">
      <label for="only_tasks">Show only pages with tasks</label>
      <input id="only_tasks" name="only_tasks" type="checkbox" style="vertical-align: -webkit-baseline-middle;" onChange="this.form.submit()" <?php echo $_REQUEST['only_tasks'] ? "checked" : ''; ?> />
    </form>
  </div>

  <table class="md_table">
    <thead>
      <tr>
        <th width="80%">Page</th>
        <th width="5%">Tasks Backlog </th>
        <th width="5%">Active Tasks</th>
        <th width="10%">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php 
      if (count($node_grouped) > 0) { 
        foreach ($node_grouped as $node_id => $row) {
    ?>
      <tr>
        <td><?php echo esc_attr(urldecode($row['title'])); ?></td>
        <td class="centered numbered <?php echo $row['backlog'] ? "positive" : ""?>"><?php echo esc_attr($row['backlog']); ?></td>
        <td class="centered numbered <?php echo $row['active'] ? "positive" : ""?>"><?php echo esc_attr($row['active']); ?></td>
        <td class="actions">
          <div class="flex-row">
            <div class="centered">
              <a title="View" class="btn-link blue" href="admin.php?page=md_tasks&idea=<?php echo esc_attr($node_id); ?>">
                <span class="icon icon-md-file"></span>
                Tasks
              </a>
            </div>
            <div class="centered">
              <a target="MindDoodle" class="btn-link blue" title="Agile" href="<?php echo esc_attr($md_url."/doodle/".$doodle_id."/idea/".$node_id."/tasks"); ?>">
                <span class="icon icon-md-cog"></span>
                Manage
              </a>
            </div>
          </div>
        </td>
      </tr>
    <?php 
        }
      } else {
        ?>
          <tr>
            <td colspan=4 style="text-align: center; font-style: italic">No tasks for this doodle.</td>
          </tr>
        <?php
      }
    ?>
    </tbody>
  </table>
