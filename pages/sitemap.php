<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function mdsm_site_map() {
  $pages = get_pages([
    'post_type' => 'page',
    'post_status' => 'publish,draft,trash'
  ]);

  // mdsm_print($pages);

  $trashed = get_pages([
    'post_status' => 'trash',
    'post_type' => 'page',
    'hierarchical' => 0,
  ]);

  foreach ($pages as $page) {
    $page->link = get_page_link($page->ID);
  }

  wp_enqueue_script('postbox');
  wp_enqueue_script('jquery-ui-core');
  wp_enqueue_script('jquery-ui-draggable');

  try {
    global $mdwp_options;

    $site_url = get_site_url();
    $href = $mdwp_options['selected_team_url']."/doodle/".$mdwp_options['doodle_id'];
    $trashed_count = count($trashed);
    $title = "Sitemap | 
      <span style='font-family: icomoon;'></span> <a class='trashedCount' data-count='$trashed_count' href='{$site_url}/wp-admin/edit.php?post_status=trash&post_type=page'>Trash ({$trashed_count})</a> | 
      <a href='{$site_url}/wp-admin/admin.php?page=md_tasks'>Tasks</a>
    ";
    $md_link='';

    if ($mdwp_options['auth_token'] && $mdwp_options['selected_team_url'] && $mdwp_options['doodle_id']) {
      $md_link = "
        <a class='btn-link green' target='MindDoodle' class='page-title-action' href='".$href."' style='font-family: \"HelveticaNue 35 Thin\", Arial, sans-serif; font-size: 11px; margin: 0'>
          <span class='icon icon-md-view'></span>
          View in Mind Doodle
        </a>
      ";
    }

    $sitemap_option = get_option(MDSM_SITEMAP_OPTION);
    $autopublish = isset($sitemap_option['autopublish']) ? $sitemap_option['autopublish'] : false;
    $show_hidden = isset($sitemap_option['showhidden']) ? $sitemap_option['showhidden'] : false;
    $checked_autopublish = ($autopublish && $autopublish != 'false') ? 'checked="checked"' : ''; 
    $checked_hidden = ($show_hidden && $show_hidden != 'false') ? 'checked="checked"' : ''; 
    echo " 
    <div class='agile-wrap' style='display: flex; max-width: unset'>
      <div style='flex: 1'>
        ".$md_link."
      </div>
      <div style='align-self: center;'>
        <div>
          <input type='checkbox' name='autopublish' id='autopublish' ".$checked_autopublish." onclick='changeAutoPublish(this)'>
          <label for='autopublish'>Automatically publish new pages</label>
        </div>
        <div>
          <input type='checkbox' name='showHiddenNodes' id='showHiddenNodes' ".$checked_hidden." onclick='changeShowHidden(this)'>
          <label for='showHiddenNodes'>Include deleted and draft pages</label>
        </div>
      </div>
      
    </div>
    ";

    add_meta_box("mdsm_sitemap", $title, "mdsm_sitemap_metabox", "mdsm_sitemap", 'advanced', 'default', ['pages' => $pages]);
  } catch (Exception $e) {
    echo $e->getMessage();
  }

//  define("MDSM_SM_PLUGIN_DIR", plugin_dir_url(__FILE__));

  function mdsm_sitemap_metabox($post, $metabox) {
    $sitemap_option = get_option(MDSM_SITEMAP_OPTION);
    $show_hidden = isset($sitemap_option['showhidden']) ? $sitemap_option['showhidden'] : false;
    $legend_display_style = ($show_hidden && $show_hidden !== 'false') ? 'block' : 'none';

    echo "
      <main id=\"root\"></main>
      <div id=\"mdsm_legend\" style=\"display: ".$legend_display_style."\">
        <div>
          <span class=\"legend_item draft\"></span> - draft page
        </div>
        <div>
          <span class=\"legend_item trash\"></span> - trash page
        </div>
      </div>
    ";

    wp_enqueue_style('sm', MDSM_SM_PLUGIN_URL . 'js/react-based-sitemap/D3/D3.production.css');

    wp_enqueue_script('react', 'https://cdnjs.cloudflare.com/ajax/libs/react/15.6.1/react.js');
    wp_enqueue_script('react-dom', 'https://cdnjs.cloudflare.com/ajax/libs/react-dom/15.6.1/react-dom.js');
    wp_enqueue_script('babel', 'https://cdnjs.cloudflare.com/ajax/libs/babel-core/5.8.34/browser.js');
    wp_enqueue_script('fetch', 'https://cdnjs.cloudflare.com/ajax/libs/fetch/3.0.0/fetch.min.js');

    if (explode('.', get_bloginfo('version'))[0] < 5) {
      wp_enqueue_script('babel-polyfil', 'https://cdnjs.cloudflare.com/ajax/libs/babel-polyfill/7.2.5/polyfill.min.js');
    }

    wp_enqueue_script('sm-polyfills', MDSM_SM_PLUGIN_URL . 'js/react-based-sitemap/D3/src/polyfills.production.js');
    wp_enqueue_script('sm-vendors', MDSM_SM_PLUGIN_URL . 'js/react-based-sitemap/D3/src/vendors.production.js');
    wp_enqueue_script('sm-code', MDSM_SM_PLUGIN_URL . 'js/react-based-sitemap/D3/src/D3.production.js');
    wp_enqueue_script('sm', MDSM_SM_PLUGIN_URL . 'js/sitemap.js');

    wp_enqueue_script('promise-polyfill', 'https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.min.js');

    wp_localize_script('sm', 'sitemapData', array(
      'rest_url' => esc_url_raw(rest_url()),
      'nonce' => wp_create_nonce('wp_rest'),
      'nonce2' => wp_create_nonce('mdsm_update_options'),
      'cookie'=> $_COOKIE,
      'pages' => json_encode($metabox['args']['pages'], JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS)
    ));
  }

  ?>
  <div class="wrap">
    <div class="postbox-container" style="width: 100%">
      <div class="metabox-holder">
        <?php do_meta_boxes('mdsm_sitemap', 'advanced', null); ?>
      </div>
    </div>
  </div>

  <script>
    jQuery(document).ready(function ($) {
      jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
      postboxes.add_postbox_toggles('fx_blog_templates')
    });
  </script>
  <?php
}

mdsm_site_map();
