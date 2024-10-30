/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

var __ = wp.i18n.__; // Import __() from wp.i18n

var _wp$editor = wp.editor,
    BlockControls = _wp$editor.BlockControls,
    AlignmentToolbar = _wp$editor.AlignmentToolbar,
    InspectorControls = _wp$editor.InspectorControls;
var Fragment = wp.element.Fragment;
var _wp$components = wp.components,
    ToggleControl = _wp$components.ToggleControl,
    Panel = _wp$components.Panel,
    PanelBody = _wp$components.PanelBody,
    PanelRow = _wp$components.PanelRow;

var el = wp.element.createElement,
    withSelect = wp.data.withSelect;

var UnitSelection = function UnitSelection(props) {
  var field = props['data-field'];
  var dimension = props['data-dimension'];
  var size = props['data-size'];
  return wp.element.createElement(
    'select',
    { defaultValue: props.value, 'data-size': size, 'data-dimension': dimension, 'data-field': field, className: 'md_input', onChange: props.onChange },
    props.options.map(function (i) {
      return wp.element.createElement(
        'option',
        { value: i },
        i
      );
    })
  );
};

wp.blocks.registerBlockType('sitemap/block', {
  title: 'Embed Sitemap',
  icon: 'networking',
  category: 'common',
  attributes: {
    border: {
      type: 'boolean',
      default: true
    },
    excluded: {
      type: 'array',
      default: []
    },
    units: {
      type: 'array',
      default: ['px', '%']
    },
    useAdvanced: {
      type: 'boolean',
      default: false
    },
    basicSizes: {
      type: 'object',
      default: {
        height: {
          value: '400',
          unit: 'px'
        },
        width: {
          value: '400',
          unit: 'px'
        }
      }
    },
    advancedSizes: {
      type: 'object',
      default: {
        desktop: {
          width: {
            value: '1000',
            unit: 'px',
            label: 'Sitemap Width'
          },
          height: {
            value: '1000',
            unit: 'px',
            label: 'Sitemap Height'
          },
          response: {
            value: '10000',
            label: 'Responsive Width'
          }
        },
        tablet: {
          width: {
            value: '600',
            unit: 'px',
            label: 'Sitemap Width'
          },
          height: {
            value: '600',
            unit: 'px',
            label: 'Sitemap Height'
          },
          response: {
            value: '800',
            label: 'Responsive Width'
          }
        },
        mobile: {
          width: {
            value: '250',
            unit: 'px',
            label: 'Sitemap Width'
          },
          height: {
            value: '250',
            unit: 'px',
            label: 'Sitemap Height'
          },
          response: {
            value: '400',
            label: 'Responsive Width'
          }
        }
      }
    }
  },

  edit: wp.data.withSelect(function (select) {
    return { pages: select('core').getEntityRecords('postType', 'page', {
        orderby: 'menu_order',
        order: 'asc',
        status: 'publish'
      }) };
  })(function (props) {
    function getPagesTree(pages) {
      var parent = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;

      return pages.reduce(function (res, k) {
        if (k.parent === parent) {
          k.children = getPagesTree(pages, k.id);
          res.push(k);
        }
        return res;
      }, []);
    }

    function renderTree(pagesTree) {
      return pagesTree.map(function (page) {
        var checked = props.attributes.excluded && props.attributes.excluded.indexOf(page.id) !== -1 ? false : true;
        return wp.element.createElement(
          'li',
          null,
          wp.element.createElement(
            'label',
            null,
            wp.element.createElement('input', { type: 'checkbox', value: page.id, onChange: toggleExcludePage, defaultChecked: checked }),
            page.title.rendered
          ),
          page.children.length > 0 ? wp.element.createElement(
            'ul',
            { className: 'nested_list' },
            renderTree(page.children)
          ) : ''
        );
      });
    }

    function renderAdvancedSettings() {
      return wp.element.createElement(
        Fragment,
        null,
        Object.keys(props.attributes.advancedSizes).map(function (size) {
          return wp.element.createElement(
            Fragment,
            null,
            wp.element.createElement(
              'div',
              { className: 'advancedSettings__label' },
              wp.element.createElement(
                'label',
                null,
                size
              )
            ),
            wp.element.createElement(
              PanelRow,
              { className: 'advancedSettings__panelRow' },
              wp.element.createElement(
                'div',
                { className: 'table' },
                Object.keys(props.attributes.advancedSizes[size]).map(function (field) {
                  var label = props.attributes.advancedSizes[size][field].label;
                  return !(size === 'desktop' && field === 'response') ? wp.element.createElement(
                    'div',
                    { className: 'row' },
                    wp.element.createElement(
                      'div',
                      { className: 'cell sitemap_size_options__label' },
                      label.charAt(0).toUpperCase() + label.slice(1)
                    ),
                    wp.element.createElement(
                      'div',
                      { className: 'cell sitemap_size_options__value' },
                      wp.element.createElement('input', { className: 'md_input', type: 'number', min: '0', step: '10', 'data-size': size, 'data-field': 'value', 'data-dimension': field, onChange: updateAdvancedSizes, value: props.attributes.advancedSizes[size][field].value })
                    ),
                    field !== 'response' ? wp.element.createElement(
                      'div',
                      { className: 'cell' },
                      wp.element.createElement(UnitSelection, { options: props.attributes.units, 'data-size': size, 'data-field': 'unit', 'data-dimension': field, value: props.attributes.advancedSizes[size][field].unit, onChange: updateAdvancedSizes })
                    ) : wp.element.createElement(
                      'div',
                      { className: 'cell colspan' },
                      '(px)'
                    )
                  ) : '';
                })
              )
            )
          );
        }),
        wp.element.createElement(
          'div',
          { className: 'advanced_note' },
          'NOTE: the sitemap width and height will be applied when the screen width is less than the value entered for the responsive width.'
        )
      );
    }

    function updateBasicSizes(event) {
      var value = event.target.value;
      var dimension = event.target.dataset['dimension'];
      var field = event.target.dataset['field'];
      var basic = _extends({}, props.attributes.basicSizes);
      basic[dimension][field] = value;
      props.setAttributes({ basicSizes: basic });
    }

    function updateAdvancedSizes(event) {
      var value = event.target.value;
      var size = event.target.dataset['size'];
      var dimension = event.target.dataset['dimension'];
      var field = event.target.dataset['field'];

      // console.log(size, dimension, field)
      var advanced = _extends({}, props.attributes.advancedSizes);
      advanced[size][dimension][field] = value;
      // if (dimension !== 'response') {
      // } else {
      //   advanced[size][dimension] = value
      // }
      // advanced[dimension][field] = value
      props.setAttributes({ advancedSizes: advanced });
    }

    // function updateHeight (event) {
    //   const value = event.target.value
    //   // if (value.match(/^([0-9]+)?(p|px|%)?$/i)) {
    //     props.setAttributes({height: value})
    //   // }
    // }
    // function updateWidth (event) {
    //   const value = event.target.value
    //   // if (value.match(/^([0-9]+)?(p|px|%)?$/i)) {
    //     props.setAttributes({width: value})
    //   // }
    // }

    // function changeUnitSelect (event) {
    //   const value = event.target.value
    //   console.log(event, value)
    // }

    function toggleBorder(event) {
      props.setAttributes({ border: event.target.checked });
    }

    function toggleAdvanced(value) {
      props.setAttributes({ useAdvanced: value });
    }

    function toggleExcludePage(event) {
      var excluded = [].concat(_toConsumableArray(props.attributes.excluded));
      var value = event.target.value * 1;
      var index = excluded.indexOf(value);

      if (index === -1) {
        excluded.push(value);
      } else {
        excluded.splice(index, 1);
      }

      props.setAttributes({ excluded: excluded });
    }

    var pagesList = void 0;
    if (!props.pages) {
      pagesList = __("Loading pages...");
    }

    if (props.pages && props.pages.length > 0) {
      var pages = props.pages;

      var pagesTree = getPagesTree(pages);
      pagesList = wp.element.createElement(
        'ul',
        null,
        renderTree(pagesTree)
      );
    }

    return wp.element.createElement(
      Fragment,
      null,
      wp.element.createElement(
        InspectorControls,
        null,
        wp.element.createElement(
          PanelBody,
          { title: 'Size settings', initialOpen: true },
          wp.element.createElement(
            PanelRow,
            null,
            wp.element.createElement(
              'div',
              { className: 'table' },
              wp.element.createElement(
                'div',
                { className: 'row' },
                wp.element.createElement(
                  'div',
                  { className: 'cell sitemap_size_options__label' },
                  'Sitemap Width'
                ),
                wp.element.createElement(
                  'div',
                  { className: 'cell sitemap_size_options__value' },
                  wp.element.createElement('input', { className: 'md_input', type: 'number', min: '0', step: '10', 'data-field': 'value', 'data-dimension': 'width', onChange: updateBasicSizes, value: props.attributes.basicSizes.width.value })
                ),
                wp.element.createElement(
                  'div',
                  { className: 'cell' },
                  wp.element.createElement(UnitSelection, { options: props.attributes.units, 'data-field': 'unit', 'data-dimension': 'width', value: props.attributes.basicSizes.width.unit, onChange: updateBasicSizes })
                )
              ),
              wp.element.createElement(
                'div',
                { className: 'row' },
                wp.element.createElement(
                  'div',
                  { className: 'cell sitemap_size_options__label' },
                  'Sitemap Height'
                ),
                wp.element.createElement(
                  'div',
                  { className: 'cell sitemap_size_options__value' },
                  wp.element.createElement('input', { className: 'md_input', type: 'number', min: '0', step: '10', 'data-field': 'value', 'data-dimension': 'height', onChange: updateBasicSizes, value: props.attributes.basicSizes.height.value })
                ),
                wp.element.createElement(
                  'div',
                  { className: 'cell' },
                  wp.element.createElement(UnitSelection, { options: props.attributes.units, 'data-field': 'unit', 'data-dimension': 'height', value: props.attributes.basicSizes.height.unit, onChange: updateBasicSizes })
                )
              )
            )
          ),
          wp.element.createElement(
            PanelRow,
            null,
            wp.element.createElement(ToggleControl, { label: 'Advanced Configuration Options', checked: props.attributes.useAdvanced, onChange: toggleAdvanced })
          ),
          props.attributes.useAdvanced ? renderAdvancedSettings() : ''
        )
      ),
      wp.element.createElement(
        'div',
        null,
        wp.element.createElement(
          'h3',
          null,
          'Embed Sitemap block'
        ),
        wp.element.createElement(
          'div',
          { 'class': 'setting_param_label' },
          'Pages to include in Sitemap'
        ),
        wp.element.createElement(
          'div',
          { className: 'pages_list md_input' },
          pagesList
        ),
        wp.element.createElement(
          'div',
          { 'class': 'setting_param_label' },
          wp.element.createElement(
            'label',
            null,
            wp.element.createElement('input', { type: 'checkbox', defaultChecked: props.attributes.border, onChange: toggleBorder }),
            'Draw border around sitemap'
          )
        )
      )
    );
  }),

  save: function save(props) {
    var height = props.attributes.height ? props.attributes.height.match(/px|%/g) ? props.attributes.height : props.attributes.height + 'px' : 'auto';
    var width = props.attributes.width ? props.attributes.width.match(/px|%/g) ? props.attributes.width : props.attributes.width + 'px' : 'auto';
    return wp.element.createElement(
      Fragment,
      null,
      wp.element.createElement('div', {
        className: 'sitemap_view_block',
        'data-width': '' + props.attributes.basicSizes.width.value + props.attributes.basicSizes.width.unit,
        'data-height': '' + props.attributes.basicSizes.height.value + props.attributes.basicSizes.height.unit,
        'data-border': props.attributes.border,
        'data-advanced': props.attributes.useAdvanced ? btoa(JSON.stringify(props.attributes.advancedSizes)) : '',
        'data-excluded': props.attributes.excluded.join()
      })
    );
  }
});

/***/ })
/******/ ]);