<?php
function register_button( $buttons ) {
  $buttons[] = "sitemap";
  return $buttons;
}

function add_plugin( $plugin_array ) {
  $plugin_array['sitemap'] = MDSM_PLUGIN_URL. '/shortcode/sitemap.js';
  return $plugin_array;
}

function sitemap_shortcode_button() {
  if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) {
    return;
 }

 if ( get_user_option('rich_editing') == 'true' ) {
    add_filter( 'mce_external_plugins', 'add_plugin' );
    add_filter( 'mce_buttons', 'register_button' );
 }
}

add_action('admin_init', 'sitemap_shortcode_button');


// --------------------------------------------------
// settings popup window

function buildTree(array &$elements, $parentId = 0) {
  $branch = array();

  foreach ($elements as $element) {
    if ($element->post_parent == $parentId) {
      $children = buildTree($elements, $element->ID);
      if ($children) {
        $element->children = $children;
      }
      $branch[$element->ID] = $element;
      unset($elements[$element->ID]);
    }
  }
  return $branch;
}

function render_nested_list($tree_pages) {
  foreach ($tree_pages as $page) {
    $str .= '
      <li>
        <label class="menu-item-title" for="page-'.$page->ID.'">
          <input class="menu-item-checkbox" type="checkbox" name="pages" id="page-'.$page->ID.'" value="'.$page->ID.'" checked/>'
          .$page->post_title
        .'</label>
    ';
    if ($page->children) {
      $str .= '<ul class="nested_list">'.render_nested_list($page->children).'</ul>';
    }
    $str .= '
      </li>
    ';
  }

  return $str;
}

function sitemap_shortcode_settings() {
  $pages = get_pages([
    'post_type' => 'page',
    'post_status' => 'publish'
  ]);
  // mdsm_print($pages);

  $tree_pages = buildTree($pages, 0);
  // mdsm_print($tree_pages);

  echo '
    <link rel="stylesheet" type="text/css" href="'.MDSM_PLUGIN_URL.'/assets/css/sitemap.css?'.mktime().'"/>
    <form id="sitemap_settings" class="shortcode_settings">
      <div class="setting_param_label">Pages to include in Sitemap</div>
      <div class="pages_list md_input">
  ';

  echo '<ul id="pages_list">'.render_nested_list($tree_pages).'</ul>';

  echo '
      </div>
      <div class="setting_param_label">
        <label class="menu-item-title" for="show_border">
          <input class="menu-item-checkbox" type="checkbox" name="border" id="show_border" checked/>
            Draw border around sitemap
        </label>
      </div>
      <div class="sitemap_size_options">
        <div class="sitemap_size_options__basic" id="basic_settings">
          <div class="table">
            <div class="row">
              <div class="cell sitemap_size_options__label">Sitemap Width</div>
              <div class="cell sitemap_size_options__value"><input class="md_input" value="400" id="sitemap_width" name="width" type="number" min="0" step="10"/></div>
              <div class="cell">
                <select id="width_unit" class="md_input" name="width_unit">
                  <option value="px">px</option>
                  <option value="%">%</option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="cell sitemap_size_options__label">Sitemap Height</div>
              <div class="cell sitemap_size_options__value"><input class="md_input" value="400" id="sitemap_height" name="height" type="number" min="0" step="10"/></div>
              <div class="cell">
                <select id="height_unit" class="md_input" name="height_unit">
                  <option value="px">px</option>
                  <option value="%">%</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      <div class="sitemap_size_options__advanced" id="advanced_settings">
      
        <ul class="tabs clearfix" data-tabgroup="first-tab-group">
          <li><a href="#mobile_tab" class="active">Mobile</a></li>
          <li><a href="#tablet_tab">Tablet</a></li>
          <li><a href="#desktop_tab">Desktop</a></li>
        </ul>
        <section id="first-tab-group" class="tabgroup">
          <div id="mobile_tab">
            <div class="table">
              <div class="row">
                <div class="cell sitemap_size_options__label">Sitemap Width</div>
                <div class="cell sitemap_size_options__value"><input class="md_input" value="250" id="mobile_width" name="mobile_width" type="number" min="0" step="10"/></div>
                <div class="cell">
                  <select id="mobile_width_unit" class="md_input" name="width_unit">
                    <option value="px">px</option>
                    <option value="%">%</option>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="cell sitemap_size_options__label">Sitemap Height</div>
                <div class="cell sitemap_size_options__value"><input class="md_input" value="250" id="mobile_height" name="mobile_height" type="number" min="0" step="10"/></div>
                <div class="cell">
                  <select id="mobile_height_unit" class="md_input" name="height_unit">
                    <option value="px">px</option>
                    <option value="%">%</option>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="cell sitemap_size_options__label">Responsive Width</div>
                <div class="cell sitemap_size_options__value"><input class="md_input" value="400" id="mobile_rwidth" name="mobile_rwidth" type="number" min="0" step="10"/></div>
                <div class="cell colspan">(px)</div>
              </div>
            </div>
          </div>
          <div id="tablet_tab" style="display: none">
            <div class="table">
              <div class="row">
                <div class="cell sitemap_size_options__label">Sitemap Width</div>
                <div class="cell sitemap_size_options__value"><input class="md_input" value="600" id="tablet_width" name="tablet_width" type="number" min="0" step="10"/></div>
                <div class="cell">
                  <select id="tablet_width_unit" class="md_input" name="width_unit">
                    <option value="px">px</option>
                    <option value="%">%</option>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="cell sitemap_size_options__label">Sitemap Height</div>
                <div class="cell sitemap_size_options__value"><input class="md_input" value="600" id="tablet_height" name="tablet_height" type="number" min="0" step="10"/></div>
                <div class="cell">
                  <select  id="tablet_height_unit" class="md_input" name="height_unit">
                    <option value="px">px</option>
                    <option value="%">%</option>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="cell sitemap_size_options__label">Responsive Width</div>
                <div class="cell sitemap_size_options__value"><input class="md_input" value="800" id="tablet_rwidth" name="tablet_rwidth" type="number" min="0" step="10"/></div>
                <div class="cell colspan">(px)</div>
              </div>
            </div>
          </div>
          <div id="desktop_tab" style="display: none">
            <div class="table">
              <div class="row">
                <div class="cell sitemap_size_options__label">Sitemap Width</div>
                <div class="cell sitemap_size_options__value"><input class="md_input" value="1000" id="desktop_width" name="desktop_width" type="number" min="0" step="10"/></div>
                <div class="cell">
                  <select id="desktop_width_unit" class="md_input" name="width_unit">
                    <option value="px">px</option>
                    <option value="%">%</option>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="cell sitemap_size_options__label">Sitemap Height</div>
                <div class="cell sitemap_size_options__value"><input class="md_input" value="1000" id="desktop_height" name="desktop_height" type="number" min="0" step="10"/></div>
                <div class="cell">
                  <select  id="desktop_height_unit" class="md_input" name="height_unit">
                    <option value="px">px</option>
                    <option value="%">%</option>
                  </select>
                </div>
              </div>'
              // <div class="row">
              //   <div class="cell sitemap_size_options__label">Responsive Width</div>
              //   <div class="cell sitemap_size_options__value"><input class="md_input" id="desktop_rwidth" name="desktop_rwidth" type="number" min="0" step="10"/></div>
              //   <div class="cell colspan">(px)</div>
              // </div>
              .'
            </div>
          </div>
        </section>
        <div style="font-size: 0.7em; color: #777; display: initial !important; font-style: italic;">
          NOTE: the sitemap width and height will be applied when the screen width is less than the value entered for the responsive width.
        </div>
    
      </div>
      <div class="setting_param_label">
        <label><input id="advanced_size" type="checkbox">Advanced Configuration Options</label>
      </div>
    </form>
    <script type="text/javascript" src="'.MDSM_PLUGIN_URL.'/shortcode/sitemap_shortcode_settings.js?'.mktime().'"></script>
  ';
  wp_die();
}

add_action('wp_ajax_sitemap_shortcode_settings', 'sitemap_shortcode_settings');




// --------------------------------------------------
// render content

function sitemap_shortcode_content($atts) {
  // preg_match('/^([0-9]+)(px|%)?$/i', $atts['height'], $matches_height);
  // if (sizeof($matches_height)) {
  //   $height = $matches_height[1] . ($matches_height[2] ?: 'px');
  // }

  // $matches_width = [];
  // preg_match('/^([0-9]+)(px|%)?$/i', $atts['width'], $matches_width);
  // if (sizeof($matches_width)) {
  //   $width = $matches_width[1] . $matches_width[2] ?: 'px';
  // }

  // return '<div class="sitemap_view_block '. ($atts['no_border'] ? 'no_border' : '') .'" style="'. ($height ? ('height: '.$height.';') : '') .' '. ($width ? ('width: '.$width.';') : '') .'" data-excluded="'.base64_encode($atts['excluded']).'"></div>';
  $div = '<div class="sitemap_view_block"';
  foreach ($atts as $attr_name=>$attr_value) {
    if ($attr_name!=="advanced") {
      $div .= ' data-'.$attr_name.'="'.$attr_value.'"';
    } else {
      $div .= ' data-'.$attr_name.'="'.base64_encode($attr_value).'"';
    }
  }
  $div .= '></div>';

  return $div;
};

add_shortcode('sitemap', 'sitemap_shortcode_content');





// --------------------------------------------------
// enqueue scripts for shortcodes


function enqueue_shortcode_scripts() {
  wp_enqueue_style('sm', MDSM_SM_PLUGIN_URL . 'js/react-based-sitemap/D3/D3.production.css');
  wp_enqueue_style('sm_user', MDSM_SM_PLUGIN_URL . 'assets/css/sitemap.css');

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
  $pages_options = json_encode( array_merge(get_option(MDSM_SITEMAP_OPTION, (array)[]), get_option(MDSM_CONNECT_OPTION, (array)[]))['pages'] );
  echo '<script type="text/javascript">var mdsmOptions='.$pages_options.'</script>';
  wp_enqueue_script('sitemap_shortcode', MDSM_SM_PLUGIN_URL . 'js/sitemap_view.js', array( 'jquery' ), null, 'in_footer');
  wp_localize_script('sitemap_shortcode', 'frontendajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
}

add_action('wp_enqueue_scripts', 'enqueue_shortcode_scripts');

