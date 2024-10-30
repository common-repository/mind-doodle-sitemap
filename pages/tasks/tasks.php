<?php
  if ( ! defined( 'ABSPATH' ) ) exit;

  $new_task_link = '/wp-admin/admin.php?page=md_task';
  if (isset($_GET['idea'])) {
    $new_task_link .= '&idea='.$_GET['idea'];
  }

  global $mdsm_plugin_settings, $agile, $md_url, $mdwp_object, $mdwp_options, $href;

  require_once(MDSM_SM_PLUGIN_DIR . '/lib/md/get_agile.php');

  if (!mdsm_is_fx_error($agile)) {
    $sprints = $agile['sprints'];
    $tasks = $agile['work_items'];
    $enums = $agile['enums'];
    $users = $agile['users'];
    $users_list = mdsm_get_agile_users($users);

    if ($mdwp_options['selected_team_url'] && $mdwp_options['doodle_id']) {
      $href = $mdwp_options['selected_team_url']."/doodle/".$mdwp_options['doodle_id']."/agileboard";
    }

    require_once(dirname(__FILE__).'/agile_summary.php');
?>
<div class="agile-wrap">
  <div class="md_setting_wrapper">
  <?php
      if (!$_GET['idea']) {
        mdsm_print_summary('Task Summary');
        include dirname(__FILE__).'/grouped_tasks.php';
      } else {
        $node_id = $_GET['idea'];
        $node = mdsm_get_node($agile['nodes'], $node_id);
        // mdsm_print($agile['nodes']);
        if ($node) {
          $header = '
            Page: <b>'.mdsm_get_node_name($agile['nodes'], $node_id).'</b>
          ';
          mdsm_print_summary($header);
          include dirname(__FILE__).'/idea_tasks.php';
        } else {
          echo '<div id="message-single-banner"><p>You attempted to view a page that doesn’t exist. Perhaps it was deleted?</p></div>';
        }
      }
    } else {
      require_once(dirname(__FILE__).'/agile_summary.php');
    ?>
        <div class="agile-wrap">
          <?php mdsm_print_summary(); ?>
        </div>
        <div class="content-block agile-wrap">
          <div class="content-block__header">
            Welcome! We're so pleased you're ready to start adding tasks
          </div>
          <div class="content-block__body">
            <div style="margin-bottom: 2em">
              To unlock this feature and make the most of project management for WordPress, sign up for a free Mind Doodle account.
            </div>
            <div class="content-row">
              <div class="content-section">
                <div class="md-connecting-image image1"></div>
                Add task to <b>Agile Board</b>
              </div>
              <div class="content-section">
                <div class="md-connecting-image image2"></div>
                <b>Chat Tool</b> with Slack Integration
              </div>
            </div>
            <?php // if (!$mdwp_options['auth_token'] || !$mdwp_options['selected_team']) { ?>
              <!-- <a class="btn-link green" href="https://www.minddoodle.com/free-sign-up/?ref=wp" target="MindDoodle">Free Sign Up</a> -->
            <?php // } else { ?>
              <a class="btn-link green" href="/wp-admin/admin.php?page=md_plugin">Select Team</a>
            <?php // } ?>
          </div>
        </div>
      <?php
    }
  ?>
  </div>
</div>
