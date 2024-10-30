const {containers: {D3: SitemapUI}, functions} = window.D3
const root = document.getElementById('root')
const TREE_NODE_WIDTH = 170
const TREE_NODE_HEIGHT = 37
const NODE_DEFAULTS_COLORS_QUICK = [
  '535353',
  'ce74c6',
  '4ab2d4',
  '829a50',
  'cf3b3e',
  'd58d3f',
  '9174bc',
  '5fb48b',
  'ad9f78'
]
const NODE_DEFAULTS_COLORS = [
  ...NODE_DEFAULTS_COLORS_QUICK,
  'c6c6c6',
  'e8bee4',
  '9ecfdf',
  'b5c496',
  'f3afb1',
  'e2ae77',
  'bca7dc',
  'a3dfc2',
  'ddd6c4'
]
const ROOT_ID = -17
const pages = JSON.parse(sitemapData.pages)
const {getChildren, getColor} = functions.d3

let colorOrder = 0

function mdsmApiWrapper (endpoint, data) {
  return fetch(`${sitemapData.rest_url}${endpoint}`, {
    ...data,
    headers: {
      'X-WP-Nonce': sitemapData.nonce,
      ...data.headers
    },
    credentials: "same-origin"
  }).then(result => {
    return new Promise((resolve, reject) => {
      return result.json().then(data => {
        return result.ok ? resolve(data) : reject(data)
      })
    })
  })
}

function mdsmAddPage (data) {
  return mdsmApiWrapper('wp/v2/pages', {
    method: "POST",
    headers: {
      'Content-Type': 'application/json'  // send json
    },
    body: JSON.stringify({
      title: 'Your Desired Page Title',
      content: 'Some content',
      type: 'page',
      status: 'draft',
      ...data
    }),
  })
}

function mdsmUpdatePage (pageId, data) {
  return mdsmApiWrapper(`wp/v2/pages/${pageId}`, {
    method: "POST",
    headers: {
      'Content-Type': 'application/json'  // send json
    },
    body: JSON.stringify(data),
  })
}

function mdsmDeletePage (pageId) {
  return mdsmApiWrapper(`wp/v2/pages/${pageId}`, {
    method: "DELETE"
  })
}

// function getOptions2() {
//   const item = {
//     action: 'my_action',
//     whatever: '1234'
//   }
//   return fetch(ajaxurl, {
//     method: 'POST',
//     headers: {
//       'Accept': 'application/json',
//       'Content-type': 'application/json',
//     },
//     body: JSON.stringify(item)
//   }).then(result => {
//     return new Promise((resolve, reject) => {
//       return result.json().then(data => {
//         return result.ok ? resolve(data) : reject(data)
//       })
//     })
//   })
// }
// getOptions2().then(console.log).catch(console.error)

function mdsm_apiPostWrapper (params) {
  return new Promise((resolve, reject) => {
    jQuery.post(ajaxurl, params, (response, d, request) => {
      return request.status === 200 ? resolve(JSON.parse(response)) : reject(response)
    })
  })
}

function mdsm_getOptions () {
  return mdsm_apiPostWrapper({action: 'get_plugin_settings'})
}

function mdsmUpdatePages (data) {
  return mdsm_apiPostWrapper({action: 'update_pages', data})
}

function mdsmUpdatePageMeta (pageId, changes) {
  return mdsm_apiPostWrapper({action: 'update_page', pageId, changes})
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

mdsm_getOptions().then(options => {
  const nodesList = pages.map((page, i) => {
    const id = page.ID
    const nodeOptions = (options && options.pages && options.pages[id]) || {}
    if (page.post_parent === 0) {
      colorOrder++
    }

    return {
      id,
      // width: +nodeOptions.width,
      width: 0,
      height: TREE_NODE_HEIGHT,
      parent: page.post_parent || ROOT_ID,
      color: nodeOptions.color || NODE_DEFAULTS_COLORS[colorOrder],
      name: mdsmUnescape(page.post_title),
      link: page.link,
      priority: id,
      status: page.post_status
    }
  })

  const extended = [{
    id: ROOT_ID,
    width: TREE_NODE_WIDTH,
    height: TREE_NODE_HEIGHT,
    parent: 0,
    name: 'Website'
  }, ...nodesList]

  const mdsmUpdateTrashCount = (removed = 1) => {
    ([...document.getElementsByClassName(`trashedCount`)]).forEach(element => {
      const count = parseInt(element.dataset.count) + removed
      element.innerHTML = `Trash (${count})`
      element.dataset.count = count
    })
  }

  var buttons = [
    {
      title: 'Add Page',
      icon: 'mdwp-add',
      color: 'rgb(62, 130, 48)',
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

          this.hideMenu();
          this.showMenu(d.id);

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
      icon: 'mdwp-view',
      color: 'rgb(26, 92, 134)',
      visible: node => !node.isRoot,
      action: function action(d) {
        mdsmGetPage(d.id).then(link => {
          window.open(link, '_self');
        })
      }
    }, {
      title: 'Edit Page',
      icon: 'mdwp-edit',
      color: 'rgb(26, 92, 134)',
      visible: node => !node.isRoot,
      action: function action(d) {
        if (!d.isRoot) {
          window.open(window.location.origin + '/wp-admin/post.php?post=' + d.id + '&action=edit', '_self');
        }
      }
    }, {
      title: 'Delete Page',
      icon: 'mdwp-delete2',
      color: 'rgb(193, 40, 27)',
      visible: node => !node.isRoot,
      action: function action(d) {
        var _this2 = this;

        if (!d.isRoot) {
          var confirmation = confirm('Are you sure you want to remove \'' + d.name + '\'?');

          if (confirmation) {
            // var withChildren = d.children.length && confirm('Are you sure you want to remove children pages as well?');
            var children = getChildren(this.state.nodes, d.id);
            // var list = withChildren ? [d.id].concat(_toConsumableArray(children)) : [d.id];
            var list =[d.id].concat(_toConsumableArray(children));

            // if (!withChildren) {
            //   children.forEach(function (id) {
            //     _this2.props.onParentChange.bind(_this2)(id, d.parent);
            //     // this.changeParent(id, d.parent)
            //   });
            // }

            var reverselist = list.reverse()
            mdsmDeletePage(d.id).then(function (e) {
              list.forEach(function (id) {
                _this2.removeNode(d.id);
              })
              mdsmUpdateTrashCount();
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
        icon: 'mdwp-tick',
        color: 'rgb(26, 92, 134)',
        visible: node => !node.isRoot,
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
      icon: 'mdwp-tick',
      color: 'rgb(26, 92, 134)',
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
      icon: 'mdwp-tick',
      color: 'rgb(26, 92, 134)',
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
    }
  )

  const documentHeight = document.documentElement.clientHeight
  const offsetY = document.getElementById('mdsm_sitemap').offsetTop
  const initSitemapHeight = documentHeight - offsetY - 200

  const sitemap = React.createElement(SitemapUI, {
    height: initSitemapHeight,
    buttons: buttons,

    onParentChange (nodeId, parentId) {
      const {parent: prevParent} = this.state.nodes[nodeId]

      const newColor = NODE_DEFAULTS_COLORS[Math.floor(Math.random()*NODE_DEFAULTS_COLORS.length)]

      let data = {
        parent: parentId === ROOT_ID ? 0 : parentId
      }
      if (parentId === ROOT_ID) {
        mdsmUpdatePageMeta(nodeId, { color: newColor });
      }
      mdsmUpdatePage(nodeId, data).then(function (e) {
        // mdsmUpdatePageMeta(nodeId, { color: newColor });
      }).catch(e => {
        console.error(e)
        this.changeParent(nodeId, prevParent)
      })

      // return this.changeParent(nodeId, parentId)
    },

    onNameChange (nodeId, value) {
      const prevName = this.state.nodes[nodeId].name
      this.changeName(nodeId, value)

      const {width, color} = this.state.nodes[nodeId]

      mdsmUpdatePage(nodeId, {title: value, slug: string_to_slug(value)}).then(e => {
        mdsmUpdatePageMeta(nodeId, {color, width})
      }).catch(e => {
        console.error(e)
        this.changeName(nodeId, prevName)
      })
    },
    nodesList: extended
  }, null)

  // const localData = nodesList.reduce((res, node) => {
  //   return {...res, [node.id]: {color: node.color, width: node.width}}
  // }, {})

  ReactDOM.render(sitemap, root)
}).catch(console.error)
