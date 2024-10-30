jQuery(document).ready( function($){

  var _window$D = window.D3,
    SitemapUI = _window$D.containers.D3,
    functions = _window$D.functions,
    sitemap;

  function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

  function mdsmUnescape(str) {
    var parser = new DOMParser;
    var dom = parser.parseFromString(
        '<!doctype html><body>' + str,
        'text/html');
    return dom.body.textContent
  }

  function setSizes (el, width, height) {
    $(el).css('width', width)
    if (!height.match(/%/g)) {
      $(el).css('height', height)
    } else {
      const elementWidth = $(el).width()
      const heightValue = parseInt(height) / 100
      $(el).css('height', elementWidth * heightValue)
    }
  }

  function checkWindowSize(sizes, width, height) {
    var returned = {
      width: width,
      height: height,
      query: null
    }
    const advanced = JSON.parse(atob(sizes))

    advanced.desktop.response = {...advanced.desktop.response, value: 10000}

    if (advanced.desktop.response && advanced.desktop.response.value && window.matchMedia('(max-width: ' + advanced.desktop.response.value + 'px)').matches)  {
      if (advanced.desktop.width.value) {
        returned.width = advanced.desktop.width.value+advanced.desktop.width.unit || '100%'
      }
      if (advanced.desktop.height.value) {
        returned.height = advanced.desktop.height.value+advanced.desktop.height.unit || '400px'
      }
      returned.query = 'desktop'
    }
    if (advanced.tablet.response && advanced.tablet.response.value && window.matchMedia('(max-width: ' + advanced.tablet.response.value + 'px)').matches)  {
      if (advanced.tablet.width.value) {
        returned.width = advanced.tablet.width.value+advanced.tablet.width.unit || '90%'
      }
      if (advanced.tablet.height.value) {
        returned.height = advanced.tablet.height.value+advanced.tablet.height.unit || '350px'
      }
      returned.query = 'tablet'
    }
    if (advanced.mobile.response && advanced.mobile.response.value && window.matchMedia('(max-width: ' + advanced.mobile.response.value + 'px)').matches)  {
      if (advanced.mobile.width.value) {
        returned.width = advanced.mobile.width.value+advanced.mobile.width.unit || '90%'
      }
      if (advanced.mobile.height.value) {
        returned.height = advanced.mobile.height.value+advanced.mobile.height.unit || '300px'
      }
      returned.query = 'mobile'
    }
    return returned
  }

  var TREE_NODE_WIDTH = 170;
  var TREE_NODE_HEIGHT = 37;
  var ROOT_ID = -17;
  var NODE_DEFAULTS_COLORS_QUICK = ['535353', 'ce74c6', '4ab2d4', '829a50', 'cf3b3e', 'd58d3f', '9174bc', '5fb48b', 'ad9f78'];
  var NODE_DEFAULTS_COLORS = [].concat(NODE_DEFAULTS_COLORS_QUICK, ['c6c6c6', 'e8bee4', '9ecfdf', 'b5c496', 'f3afb1', 'e2ae77', 'bca7dc', 'a3dfc2', 'ddd6c4']);
  var colorOrder = 0;


  var sitemap_blocks = $('.sitemap_view_block').each(function(i, el) {
    $(this).attr( 'id', '_' + Math.random().toString(36).substr(2, 9) );
    const bordered = $(this).data('border')

    if (!bordered) {
      $(this).css({'border': 'none', 'boxShadow': 'none'})
    }

    const excluded = $(this).data('excluded') ? $(this).data('excluded') : ''
    const advanced = $(this).data('advanced')
    const root = el;

    if (i === 0) {
      var basicWidth = $(this).data('width') || '100%'
      var basicHeight = $(this).data('height') || '400px'

      if (advanced) {
        mediaQuerySizes = checkWindowSize(advanced, basicWidth, basicHeight)
        basicWidth = mediaQuerySizes.width
        basicHeight = mediaQuerySizes.height
        // if (mediaQuerySizes.query) { console.log(mediaQuerySizes.query) }
      }

      // console.log(basicWidth, basicHeight)

      setSizes(this, basicWidth, basicHeight)
      const height = this.getBoundingClientRect().height || 400

      // console.log('final height', height)

      $.ajax({
        url: frontendajax.ajaxurl  + '?action=get_wp_pages',
        method: 'get',
        dataType: 'json',
        data: {
          ...excluded ? {exclude: excluded} : {},
        }
      }).done(function(data) {
        // console.log('done', data)
        const pages = data

        var nodesList = pages.map(function (page, i) {
          var id = page.ID;
          var nodeOptions = mdsmOptions && mdsmOptions[id] || {};
          if (page.parent === 0) {
            colorOrder++;
          }
          return {
            id: id,
            // width: +nodeOptions.width,
            width: 0,
            height: TREE_NODE_HEIGHT,
            parent: page.post_parent || ROOT_ID,
            color: nodeOptions.color || NODE_DEFAULTS_COLORS[colorOrder],
            name: mdsmUnescape(page.post_title),
            link: page.link,
            priority: id,
            order: page.menu_order,
            status: page.post_status,
          };
        });
      
        var extended = [{
          id: ROOT_ID,
          width: TREE_NODE_WIDTH,
          height: TREE_NODE_HEIGHT,
          parent: 0,
          name: 'Website'
        }].concat(_toConsumableArray(nodesList));

        const sitemap = React.createElement(SitemapUI, {
          ref: (D3_sitemap) => {window.D3_sitemap = D3_sitemap},
          height: height,
          buttons: [{
            title: 'View Page',
            icon: 'md-view',
            color: 'rgb(26, 92, 134)',
            visible: node => !node.isRoot && node.status !== "trash",
            action: function action(d) {
              window.open(d.link, '_self');
              // mdsmGetPage(d.id).then(link => {
              // })
            }
          }],
          showHidden: false,
          nodesList: extended,
          viewOnly: true
        }, null);
        
        // console.log('nodesList', extended)
        ReactDOM.render(sitemap, root);
      })
    } else {
      console.error('There can be only one sitemap at page')
      $(el).css({'display': 'none', 'height': 'auto'}).html('<span>There can be only one sitemap at page<span>')
    }
  });
})