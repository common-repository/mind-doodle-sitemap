'use strict';

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

var _window$D = window.D3,
    SitemapUI = _window$D.containers.D3,
    functions = _window$D.functions,
    sitemap;

var root = document.getElementById('root');
var TREE_NODE_WIDTH = 170;
var TREE_NODE_HEIGHT = 37;
var NODE_DEFAULTS_COLORS_QUICK = ['535353', 'ce74c6', '4ab2d4', '829a50', 'cf3b3e', 'd58d3f', '9174bc', '5fb48b', 'ad9f78'];
var NODE_DEFAULTS_COLORS = [].concat(NODE_DEFAULTS_COLORS_QUICK, ['c6c6c6', 'e8bee4', '9ecfdf', 'b5c496', 'f3afb1', 'e2ae77', 'bca7dc', 'a3dfc2', 'ddd6c4']);
var ROOT_ID = -17;
var pages = JSON.parse(sitemapData.pages);
var _functions$d = functions.d3,
    getChildren = _functions$d.getChildren,
    getColor = _functions$d.getColor;


var colorOrder = 0;

function mdsmApiWrapper(endpoint, data) {
  return fetch('' + sitemapData.rest_url + endpoint, _extends({}, data, {
    headers: _extends({
      'X-WP-Nonce': sitemapData.nonce
    }, data.headers),
    credentials: "same-origin"
  })).then(function (result) {
    return new Promise(function (resolve, reject) {
      return result.json().then(function (data) {
        return result.ok ? resolve(data) : reject(data);
      });
    });
  });
}

function mdsmAddPage(data) {
  return mdsmApiWrapper('wp/v2/pages', {
    method: "POST",
    headers: {
      'Content-Type': 'application/json' // send json
    },
    body: JSON.stringify(_extends({
      title: 'Your Desired Page Title',
      content: 'Some content',
      type: 'page',
      status: 'draft'
    }, data))
  });
}

function mdsmUpdatePage(pageId, data) {
  return mdsmApiWrapper('wp/v2/pages/' + pageId, {
    method: "POST",
    headers: {
      'Content-Type': 'application/json' // send json
    },
    body: JSON.stringify(data)
  });
}

function mdsmDeletePage(pageId, force) {
  const urlExtend = pageId + (force ? '?force=true' : '')
  return mdsmApiWrapper('wp/v2/pages/' + urlExtend, {
    method: "DELETE",
  });
}

function mdsmRestorePage(pageId) {
  return mdsm_apiPostWrapper({ action: 'untrash_page', pageId: pageId });
}

function mdsm_apiPostWrapper(params) {
  return new Promise(function (resolve, reject) {
    var nonceParams = _extends({}, params, {
      wpnonce: sitemapData.nonce2
    }); 
    jQuery.post(ajaxurl, nonceParams, function (response, d, request) {
      return request.status === 200 ? resolve(JSON.parse(response)) : reject(response);
    });
  });
}

function mdsm_getOptions() {
  return mdsm_apiPostWrapper({ action: 'get_plugin_settings' });
}

function mdsmUpdatePages(data) {
  return mdsm_apiPostWrapper({ action: 'update_pages', data: data });
}

function mdsmUpdatePageMeta(pageId, changes) {
  return mdsm_apiPostWrapper({ action: 'update_page', pageId: pageId, changes: changes });
}

function mdsmGetPage(pageId) {
  return mdsm_apiPostWrapper({ action: 'get_wp_page', pageId: pageId });
}

function mdsmUnescape(str) {
  var parser = new DOMParser;
  var dom = parser.parseFromString(
      '<!doctype html><body>' + str,
      'text/html');
  return dom.body.textContent
}

// function getMdOptions() {
//   return mdsm_apiPostWrapper({ action: 'get_md_settings' });
// }

function getMainChildren(nodes, id) {
  return nodes[id] ? nodes[id].children.reduce((res, child) => [...res, +child], []) : []
}

mdsm_getOptions().then(function (options) {
  var showHidden = options && options.showhidden && options.showhidden != 'false'

  var nodesList = pages.map(function (page, i) {
    var id = page.ID;
    var nodeOptions = options && options.pages && options.pages[id] || {};
    if (page.post_parent === 0) {
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
      hidden: (page.post_status === 'draft' || page.post_status === 'trash') && !showHidden
    };
  });

  var extended = [{
    id: ROOT_ID,
    width: TREE_NODE_WIDTH,
    height: TREE_NODE_HEIGHT,
    parent: 0,
    name: 'Website'
  }].concat(_toConsumableArray(nodesList));

  var mdsmUpdateTrashCount = function mdsmUpdateTrashCount() {
    var removed = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 1;

    [].concat(_toConsumableArray(document.getElementsByClassName('trashedCount'))).forEach(function (element) {
      var count = parseInt(element.dataset.count) + removed;
      element.innerHTML = 'Trash (' + count + ')';
      element.dataset.count = count;
    });
  };

  var buttons = [
    {
      title: 'Add Page',
      icon: 'md-add',
      color: 'rgb(62, 130, 48)',
      visible: node => node.status !== "trash",
      action: function action(d) {
        var _this = this;

        mdsm_getOptions().then(function (options) {
          var node = {
            name: 'New Page',
            parent: d.id,
            id: Math.floor(Math.random() * 1000),
            width: TREE_NODE_WIDTH,
            height: TREE_NODE_HEIGHT,
            color: d.color,
            priority: d.id,
            status: (options && options.autopublish && options.autopublish != 'false') ? 'publish' : 'draft'
          };

          _this.hideMenu();
          _this.showMenu(d.id);

          mdsmAddPage({
            parent: d.isRoot ? 0 : d.id,
            title: node.name,
            status: node.status
          }).then(function (result) {
            mdsm_getOptions().then(newOptions => {
              var createdNode = _extends({}, node, {
                color: d.isRoot ? (newOptions && newOptions.pages[result.id] && newOptions.pages[result.id].color) || getColor(_this.state.topLevels) : node.color,
                id: result.id,
                link: result.link
              });
              _this.addNode(createdNode);
              _this.showNameEdit(createdNode.id);

              mdsmUpdatePageMeta({ width: createdNode.width, color: createdNode.color });
            })
          });
        });
      }
    }, {
      title: 'View Page',
      icon: 'md-view',
      color: 'rgb(26, 92, 134)',
      visible: node => !node.isRoot && node.status !== "trash",
      action: function action(d) {
        mdsmGetPage(d.id).then(link => {
          window.open(link, '_self');
        })
      }
    }, {
      title: 'Edit Page',
      icon: 'md-edit',
      color: 'rgb(26, 92, 134)',
      visible: node => !node.isRoot && node.status !== "trash",
      action: function action(d) {
        if (!d.isRoot) {
          window.open(window.location.origin + '/wp-admin/post.php?post=' + d.id + '&action=edit', '_self');
        }
      }
    }, {
      title: 'Delete Page',
      icon: 'lr-cross',
      color: 'rgb(193, 40, 27)',
      visible: node => !node.isRoot && node.status === "trash",
      action: function action(d) {
        var _this2 = this;

        if (!d.isRoot) {
          var confirmation = confirm('Are you sure you want to remove \'' + d.name + '\'?');

          if (confirmation) {
            // var withChildren = d.children.length && confirm('Are you sure you want to remove children pages as well?');
            var children = getMainChildren(this.state.nodes, d.id);
            const firstChild = children[0]
            var list =[d.id].concat(_toConsumableArray(children));

            
            mdsmDeletePage(d.id, true).then(function (e) {
              children.forEach(function (id) {
                _this2.changeParent(id, d.parent, d.color)
              });
              _this2.removeNode(d.id);
              if (d.status === 'trash') {
                mdsmUpdateTrashCount(-1);
              }
            }).catch(console.error);
          }
        }
      }
    }
  ]
  if (options && options.doodle_id && options.user) {
    buttons.push(
      {
        title: 'View Tasks!',
        icon: 'lr-tick',
        color: 'rgb(26, 92, 134)',
        visible: node => !node.isRoot && node.status !== "trash",
        action: function action(d) {
          if (!d.isRoot) {
            window.open(window.location.origin + '/wp-admin/admin.php?page=md_tasks&idea=' + options.nodes[d.id], '_self');
          }
        }
      }
    )
  }

  buttons.push(
    {
      title: 'Publish Page',
      icon: 'md-publish-1',
      color: 'rgb(62, 130, 48)',
      visible: node => node.status === "draft",
      action: function action(d) {
        var _this5 = this;
        var prevStatus = d.status
        _this5.changeStatus(d.id, 'publish');
        if (!d.isRoot) {
          mdsmUpdatePage(d.id, { status: 'publish'}).then(function (e) {
            // mdsmUpdatePageMeta(nodeId, { color: color, width: width });
          }).catch(function (e) {
            console.error(e);
            _this5.changeStatus(d.id, prevStatus);
          });
        }
      }
    },
    {
      title: 'Convert to Draft Page',
      icon: 'md-unpublish-1',
      color: 'rgb(193, 40, 27)',
      visible: node => node.status === "publish",
      action: function action(d) {
        var _this6 = this;
        var prevStatus = d.status
        _this6.changeStatus(d.id, 'draft');
        mdsmUpdatePage(d.id, { status: 'draft'}).then(function (e) {
          // mdsmUpdatePageMeta(nodeId, { color: color, width: width });
        }).catch(function (e) {
          console.error(e);
          _this6.changeStatus(d.id, prevStatus);
        });
      }
    },
    {
      title: 'Move to trash',
      icon: 'md-delete2',
      color: 'rgb(193, 40, 27)',
      visible: node => node.status !== "trash",
      action: function action(d) {
        var _this6 = this;
        var prevStatus = d.status
        mdsmDeletePage(d.id).then(function (e) {
          _this6.changeStatus(d.id, 'trash');
          mdsmUpdateTrashCount();
        }).catch(function (e) {
          console.error(e);
          _this6.changeStatus(d.id, prevStatus);
        });
      }
    },
    {
      title: 'Restore Page',
      icon: 'md-undo',
      color: 'rgb(193, 40, 27)',
      visible: node => node.status === "trash",
      action: function action(d) {
        var _this6 = this;
        var prevStatus = d.status
        mdsmRestorePage(d.id).then(function (e) {
          const postStatus = e || 'publish'
          _this6.changeStatus(d.id, postStatus);
          mdsmUpdateTrashCount(-1);
        }).catch(function (e) {
          console.error(e);
          _this6.changeStatus(d.id, prevStatus);
        });
      }
    }
  )

  const documentHeight = document.documentElement.clientHeight
  const offsetY = document.getElementById('mdsm_sitemap').offsetTop
  const initSitemapHeight = documentHeight - offsetY - 200

  // console.log(extended)

  sitemap = React.createElement(SitemapUI, {
    ref: (D3_sitemap) => {window.D3_sitemap = D3_sitemap},
    height: initSitemapHeight > 500 ? initSitemapHeight : 500,
    buttons: buttons,
    showHidden: options && options.showhidden && options.showhidden != 'false',

    onParentChange: function onParentChange(nodeId, parentId) {
      var _this3 = this;

      var prevParent = this.state.nodes[nodeId].parent;

      const newColor = NODE_DEFAULTS_COLORS[Math.floor(Math.random()*NODE_DEFAULTS_COLORS.length)]

      this.changeParent(nodeId, parentId, newColor);
      let data = {
        parent: parentId === ROOT_ID ? 0 : parentId
      }
      if (parentId === ROOT_ID) {
        mdsmUpdatePageMeta(nodeId, { color: newColor });
      }
      mdsmUpdatePage(nodeId, data).then(function (e) {
        // mdsmUpdatePageMeta(nodeId, { color: newColor });
      }).catch(function (e) {
        console.error(e);
        _this3.changeParent(nodeId, prevParent);
      });

      // return this.changeParent(nodeId, parentId)
    },
    onNameChange: function onNameChange(nodeId, value) {
      var _this4 = this;

      var prevName = this.state.nodes[nodeId].name;
      this.changeName(nodeId, value);

      var _state$nodes$nodeId = this.state.nodes[nodeId],
          width = _state$nodes$nodeId.width,
          color = _state$nodes$nodeId.color;


      mdsmUpdatePage(nodeId, { title: value, slug: string_to_slug(value) }).then(function (e) {
        mdsmUpdatePageMeta(nodeId, { color: color, width: width });
      }).catch(function (e) {
        console.error(e);
        _this4.changeName(nodeId, prevName);
      });
    },
    onOrderChange: function(nodeId, moveOrder, updatedNodesList) {
      var _this5 = this;
      mdsm_apiPostWrapper({ action: 'change_node_order', nodeId, moveOrder }).then(function (e) {
        _this5.updateNodes(updatedNodesList)
      }).catch(function (e) {
        console.error(e);
      });
    },
    nodesList: extended
  }, null);

  // const localData = nodesList.reduce((res, node) => {
  //   return {...res, [node.id]: {color: node.color, width: node.width}}
  // }, {})

  ReactDOM.render(sitemap, root);
}).catch(console.error);

function changeAutoPublish (el) {
  var autoPublish = el.checked
  mdsm_apiPostWrapper({ action: 'set_plugin_settings', data: {autoPublish: autoPublish} })
}

function changeShowHidden (el) {
  var showHidden = el.checked
  mdsm_apiPostWrapper({ action: 'set_plugin_settings', data: {showHidden: showHidden} }).then(() => {
    var legend = document.getElementById('mdsm_legend')
    legend.style.display = (legend.style.display == 'none') ? 'block' : 'none'
    if (sitemap) {
      sitemap.props.onHiddenToggle.bind(D3_sitemap)()
    }
  })
}