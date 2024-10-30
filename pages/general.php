<?php
  if ( ! defined( 'ABSPATH' ) ) exit;

  wp_enqueue_script('general', MDSM_SM_PLUGIN_URL . 'js/general.js');
  global $mdwp_object, $mdsm_plugin_settings, $mdwp_options;

  function mdsm_get_current_team_url($teams, $team_id) {
    foreach($teams as $team) {
      if ($team['team_id'] == $team_id) {
        return $team['link'];
      }
    }
  }

  function check_auth_token() {
    global $mdwp_object, $mdsm_plugin_settings, $mdwp_options;
    $mdwp_options = get_option(MDSM_CONNECT_OPTION);
    $token = $mdwp_options['auth_token'];
    if ($token) {
      $ping_request = $mdwp_object -> execRequest( 'local_sfx/user_teams', 'GET', array() );
      if (mdsm_is_fx_error($ping_request)) {
        $error_message = strip_tags( $ping_request -> get_error_message() );
        if ($error_message === 'Unauthorized') {
          unset($mdwp_options['auth_token']);
          unset($mdwp_options['auth_token_type']);
          unset($mdwp_options['user']);
          update_option(MDSM_CONNECT_OPTION, $mdwp_options);
          return '<div class="notice notice-error mdsm_error"><p>'.esc_attr('Your auth token has been expired due to inactivity').'</p></div>';
        } 
      }
    }
  }

  function delete_doodle() {
    global $mdwp_object, $mdwp_options;

    $delete_current_doodle = $mdwp_object -> execRequest('doodle/map', 'DELETE', array(
      'doodle_id' => $mdwp_options['doodle_id'],
      'schema_id' => $mdwp_options['schema_id'],
      'team_id' => $mdwp_options['schema_id']
    ));
    if (!mdsm_is_fx_error($delete_current_doodle)) {
      unset($mdwp_options['doodle_id']);
      update_option(MDSM_CONNECT_OPTION, $mdwp_options);
    } else {
      echo mdsm_show_wp_error_message($delete_current_doodle);
    }
  }

  $mdwp_options = get_option(MDSM_CONNECT_OPTION);

  if (isset($_POST['md_team_select']) && wp_verify_nonce( $_POST['nonce'], 'mdsm_team_select_nonce' )) {
    if (current_user_can('manage_options')) {
      $selected_team = sanitize_key($_POST['team']);
      if ($selected_team) {
        $pages = get_pages([
          'post_type' => 'page',
          'post_status' => 'publish,draft,trash'
        ]);
        foreach ( $pages as &$page ) {
          $permalink = get_permalink( $page->ID );
          $page->guid = $permalink;
        }
        // unset($page);

        $sitemap_options = get_option(MDSM_SITEMAP_OPTION);
        // mdsm_print($pages);
        $data = array(
          'schema_id' => $selected_team,
          'name' => get_bloginfo('name'),
          'description' => get_bloginfo('description'),
          'type' => 'wp',
          'wp_url' => get_site_url(),
          'site_map' => array(
            'pages' => $pages,
            'options' => $sitemap_options
          )
        );

        $doodle_init = $mdwp_object -> execRequest('doodle/map', 'POST', $data);

        if (!mdsm_is_fx_error($doodle_init)) {
          if ($mdwp_options['doodle_id']) {
            delete_doodle();
          }

          echo '<div class="notice notice-success"><p>Doodle has been succesfully created</p></div>';
          $mdwp_options['selected_team'] = $selected_team;
          $mdwp_options['schema_id'] = $selected_team;
          $mdwp_options['doodle_id'] = $doodle_init['doodle_id'];
          $mdwp_options['doodle_name'] = urldecode($doodle_init['name']);
          $mdwp_options['nodes'] = $doodle_init['mapping'];
          update_option(MDSM_CONNECT_OPTION, $mdwp_options);
        } else {
          echo mdsm_show_wp_error_message($doodle_init);
        }

      } else {
        delete_doodle();
        $mdwp_options['selected_team'] = $selected_team;
        update_option(MDSM_CONNECT_OPTION, $mdwp_options);
      }
    }
  }

  $mdwp_options = get_option(MDSM_CONNECT_OPTION);

  if ($mdwp_options['doodle_id'] && $mdwp_options['schema_id']) {
    $agile = $mdwp_object -> execRequest('doodle/agile_board', 'GET', array('doodle_id' => $mdwp_options['doodle_id'], 'schema_id' => $mdwp_options['schema_id'], 'team_id' => $mdwp_options['schema_id'], 'nodes' => 1));
    if (mdsm_is_fx_error($agile)) {
      $agile = false;
    }
  }
?>

<?php
  check_auth_token();
?>

<div class="wrap flex-row md_connect_wrap">
  <div class="md_connect">
    <h1>Mind Doodle Visual Sitemaps & Tasks</h1>
    <div class="page-grid">
      <div class="main-block">
        <div class="content-block">
          <div class="content-block__header">
            <?php if (!$mdwp_options['auth_token']) { ?>
              <span class="icon icon-md-lock"></span>
              Connect to Mind Doodle
            <?php } else { ?>
              <span style="color: #8eb447" class="icon icon-md-ok"></span>
              Connected
            <?php } ?>
          </div>
          <div class="content-block__body">
          <?php if (!$mdwp_options['auth_token']) { ?>
            <div class="md_signin">
              <form method="POST">
                <input type="hidden" name="md_signin">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mdsm_get_user_token_nonce'); ?>">
                <div class="input-wrap">
                  <input class="md_input" name="md_user_login" type="text" placeholder="Enter name">
                </div>
                <div class="input-wrap">
                  <input class="md_input" name="md_user_pass" type="password" placeholder="Enter password">
                </div>
                <div class="login-actions">
                  <div>
                    <input style="margin-top: 0.5em;" class="btn-link green" type="submit" value="Connect">
                  </div>
                  <div style="margin-left: 1em;">
                    <span class="login-links primary">
                      <a href="https://minddoodle.com/login" target="MindDoodle">Forgot your password</a>
                    </span>
                    <br/>
                    <span class="login-links purple">
                      Need an account?
                      <a href="https://www.minddoodle.com/free-sign-up/?ref=wp" target="MindDoodle">Sign up here</a>
                    </span>
                  </div>
                </div>
              </form>
            </div>
          <?php } else { ?>
          <?php 
            $user = $mdwp_options['user']; 
            $teams = $mdwp_object -> execRequest('local_sfx/user_teams', 'GET', array());

            // mdsm_print($teams);

          ?>
            Connected with Mind Doodle as 
            <span style="white-space: nowrap;">
              <span class="user_avatar">
                <img src="<?php echo $user['avatar']; ?>">
              </span>
              <span class="user_name">
                <?php echo $user['display_name']; ?>
              </span>
            </span>
          <?php if (!mdsm_is_fx_error($teams)) { ?>
          <?php if (!$mdwp_options['selected_team']) {?>
            <div style="color: #007704">Almost there! Please select a team to connect your WordPress website to.</div>
          <?php }?>
            <div class="md_team_select">
              <form method="POST">
                <input type="hidden" name="md_team_select">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mdsm_team_select_nonce')?>">
                <div class="team-wrap">
                  Team
                  <select name="team" class="md_input" onChange="mdsm_teamChange(this)">
                    <?php mdsm_show_select_options($teams, 'team_id', 'display_name', $mdwp_options['selected_team']); ?>
                  </select>
                </div>
              </form>
            </div>
          <?php
            if ($mdwp_options['selected_team'] && $mdwp_options['doodle_id']) {
              $team_link = mdsm_get_current_team_url($teams, $mdwp_options['selected_team']);
              $mdwp_options['selected_team_url'] = $team_link;
              update_option(MDSM_CONNECT_OPTION, $mdwp_options);
              echo '
                <div>
                  <a target="MindDoodle" href="'.$team_link.'/doodle/'.$mdwp_options['doodle_id'].'">View your sitemap in Mind Doodle</a>
                </div>
              ';
            }
          } else {
            mdsm_show_wp_error_message($teams);
          }
          ?>
          <form method="POST" id="md_logout">
            <input type="hidden" name="md_logout">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mdsm_user_logout_nonce');?>">
            <a class="btn-link red" href="#" onClick="logoutConfirm(<?php echo ($agile && $agile['work_items'] ? sizeof($agile['work_items']) : '')?>)">Disconnect</a>
          </form>
            <?php } ?>
          </div>
        </div>
        <div class="content-block">
        <div class="content-block__header">
          <span class="icon icon-md-help"></span>
          Help
        </div>
          <div class="content-block__body">
            Let's get you Doodling like a pro
            <div class="video-wrapper">
              <iframe width="560" height="315" src="https://www.youtube.com/embed/0If-fGOgM8w?autoplay=0" frameBorder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowFullScreen></iframe>
            </div>
            <a class="btn-link green" href="https://help.minddoodle.com/knowledge-base/wordpress-sitemap/" target="MindDoodle">Visit Help Site</a>
          </div>
        </div>
      </div>
      <div class="info-block">
        <?php if (!$mdwp_options['auth_token']) { ?>
          <div class="content-block">
            <div class="content-block__header">
              <span class="icon icon-md-send"></span>
              Quick Start Guide
            </div>
            <div class="content-block__body">
              <div class="content-column">
                <div class="content-section guide-image image1">
                  <div>
                    <b>Add, edit, trash</b> or <b>view tasks.</b>
                  </div>
                </div>
                <div class="content-section guide-image image2">
                  <div>
                    <b>Drag and drop</b> pages to change parent page.
                  </div>
                </div>
                <div class="content-section guide-image image3">
                  <div>
                    <b>Sitemap</b> is always available from the admin bar.
                    <br/>
                    <a class="btn-link green" href="?page=sitemap">View Sitemap</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="content-block">
            <div class="content-block__header">
              <span class="icon icon-lr-connect"></span>
              Supercharge by connecting to Mind Doodle
            </div>
            <div class="content-block__body">
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
              <a class="btn-link green" href="https://www.minddoodle.com/free-sign-up/?ref=wp" target="MindDoodle">Free Sign Up</a>
            </div>
          </div>
        <?php } else { ?>
          <div class="content-block">
            <div class="content-block__header">
              <span class="icon icon-md-plan"></span>
              Upgrade Plan
            </div>
            <div class="content-block__body">
              <div class="content-column" style="margin-bottom: 1em;">
                <div class="content-row">
                  <div class="upgrade-plan-pre-section"></div>
                  <div class="upgrade-plan-section content-section" style="text-align: left;">
                    <div>
                      <p style="margin-top: 0;"><b>Thank you for creating a Mind Doodle account! We're really happy to have you on board.</b></p>
                      You can use all of Mind Doodle's features at no cost with your
                      <span style="font-weight: bold; color: #00577f">free account</span>,
                      which includes: 
                      <ul class="md-list" style="max-width: 450px;">
                        <li><span style="position: relative; top: -2px;">3 visual sitemaps</span></li>
                        <li><span style="position: relative; top: -2px;">10 tasks per task board</span></li>
                        <li><span style="position: relative; top: 2px;">4 weeks of chat history</span></li>
                        <li><span style="position: relative; top: 2px;">100MB of storage space</span></li>
                      </ul>
                      <p style="font-weight: bold">
                        Consider upgrading to the 
                        <span style="color: #aba77b">Gold Plan</span>
                        today.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="content-row" style="flex-flow: row wrap;">
                <div class="upgrade-feature-section">
                  <div>
                    <div class="feature-image image2"></div>
                    <div class="feature-header primary">Unlimited tasks</div>
                    <div>Enjoy limitless productivity with unlimited tasks. When 10 tasks is not enough, upgrade to the Gold Plan for the ultimate task management experience.</div>
                  </div>
                </div>
                <div class="upgrade-feature-section">
                  <div>
                    <div class="feature-image image3"></div>
                    <div class="feature-header primary">Unlock chat history</div>
                    <div>You've been chatty! However, you currently have only 4 weeks of chat history available. Unlock your entire chat history when you upgrade to the Gold Plan</div>
                  </div>
                </div>
                <div class="upgrade-feature-section">
                  <div>
                    <div class="feature-image image4"></div>
                    <div class="feature-header primary">More file space</div>
                    <div>We hear you! 100MB of file space is not cutting it. You need more room to spread your wings. How does 20GB per team member sound?</div>
                  </div>
                </div>
              </div>
              <a class="btn-link green" target="MindDoodle" href="https://portal.minddoodle.com/team/<?php echo $mdwp_options['selected_team'];?>/plan">Upgrade Team</a>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
