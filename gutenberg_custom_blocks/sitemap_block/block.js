const { __ } = wp.i18n; // Import __() from wp.i18n
const { BlockControls, AlignmentToolbar, InspectorControls } = wp.editor;
const { Fragment } = wp.element;
const { ToggleControl, Panel, PanelBody, PanelRow } = wp.components
var el = wp.element.createElement, withSelect = wp.data.withSelect;

const UnitSelection = ((props) => {
  const field = props['data-field']
  const dimension = props['data-dimension']
  const size = props['data-size']
  return (
    <select defaultValue={props.value} data-size={size} data-dimension={dimension} data-field={field} className="md_input" onChange={props.onChange}>
      {props.options.map(i => (
        <option value={i}>{i}</option>)
      )}
    </select>
  )
})


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


  edit: wp.data.withSelect( function( select ) {
    return { pages: select( 'core' ).getEntityRecords( 'postType', 'page', {
      orderby: 'menu_order',
      order: 'asc',
      status: 'publish'
    } )};
  })(function(props) {
    function getPagesTree (pages, parent=0) {
      return pages.reduce((res, k) => {
        if (k.parent === parent) {
          k.children = getPagesTree(pages, k.id)
          res.push(k)
        }
        return res
      }, [])
    }

    function renderTree (pagesTree) {
      return pagesTree.map(page => {
        const checked = props.attributes.excluded && props.attributes.excluded.indexOf(page.id) !== -1 ? false : true
        return (
          <li>
            <label>
              <input type="checkbox" value={page.id} onChange={toggleExcludePage} defaultChecked={checked}/>
              {page.title.rendered}
            </label>
            {page.children.length > 0 
              ? (
                <ul className="nested_list">
                  {renderTree(page.children)}
                </ul>
              ) : ''
            }
          </li>
        )
      })
    }

    function renderAdvancedSettings () {
      return (
        <Fragment>
          {Object.keys(props.attributes.advancedSizes).map(size => {
            return (
              <Fragment>
                <div className="advancedSettings__label">
                  <label>{size}</label>
                </div>
                <PanelRow className="advancedSettings__panelRow">
                  <div className="table">
                    {Object.keys(props.attributes.advancedSizes[size]).map(field => {
                      const label = props.attributes.advancedSizes[size][field].label
                      return !(size === 'desktop' && field === 'response') ? (
                        <div className="row">
                          <div className="cell sitemap_size_options__label">{label.charAt(0).toUpperCase() + label.slice(1)}</div>
                          <div className="cell sitemap_size_options__value">
                            <input className="md_input" type="number" min="0" step="10" data-size={size} data-field='value' data-dimension={field} onChange={updateAdvancedSizes} value={props.attributes.advancedSizes[size][field].value}/>
                          </div>
                          { field !== 'response'
                              ? (<div className="cell">
                                  <UnitSelection options={props.attributes.units} data-size={size} data-field='unit' data-dimension={field} value={props.attributes.advancedSizes[size][field].unit} onChange={updateAdvancedSizes}/>
                                </div>
                              )
                              : (<div className="cell colspan">(px)</div>)
                          }
                        </div>
                      ) : ''
                    })}
                  </div>
                </PanelRow>
              </Fragment>
            )
          })}
          <div className="advanced_note">
            NOTE: the sitemap width and height will be applied when the screen width is less than the value entered for the responsive width.
          </div>
        </Fragment>
      )
    }

    function updateBasicSizes (event) {
      const value = event.target.value
      const dimension = event.target.dataset['dimension']
      const field = event.target.dataset['field']
      let basic = {...props.attributes.basicSizes}
      basic[dimension][field] = value
      props.setAttributes({basicSizes: basic})
    }

    function updateAdvancedSizes (event) {
      const value = event.target.value
      const size = event.target.dataset['size']
      const dimension = event.target.dataset['dimension']
      const field = event.target.dataset['field']

      // console.log(size, dimension, field)
      let advanced = {...props.attributes.advancedSizes}
      advanced[size][dimension][field] = value
      // if (dimension !== 'response') {
      // } else {
      //   advanced[size][dimension] = value
      // }
      // advanced[dimension][field] = value
      props.setAttributes({advancedSizes: advanced})
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

    function toggleBorder (event) {
      props.setAttributes({border: event.target.checked})
    }

    function toggleAdvanced (value) {
      props.setAttributes({useAdvanced: value})
    }

    function toggleExcludePage(event) {
      const excluded = [...props.attributes.excluded]
      const value = event.target.value * 1
      const index = excluded.indexOf(value);

      if (index === -1) {
        excluded.push(value);
      } else {
        excluded.splice(index, 1);
      }

      props.setAttributes({excluded})
    }

    let pagesList
    if ( !props.pages ) {
      pagesList = __("Loading pages...");
    }

    if ( props.pages && props.pages.length > 0 ) {
      const {pages} = props
      const pagesTree = getPagesTree(pages) 
      pagesList = (
        <ul>
          {renderTree(pagesTree)}
        </ul>
      )
    }

    return (
      <Fragment>
        <InspectorControls>
		      <PanelBody title='Size settings' initialOpen={true}>
            <PanelRow>
              <div className="table">
                <div className="row">
                  <div className="cell sitemap_size_options__label">Sitemap Width</div>
                  <div className="cell sitemap_size_options__value">
                    <input className="md_input" type="number" min="0" step="10" data-field='value' data-dimension='width' onChange={updateBasicSizes} value={props.attributes.basicSizes.width.value}/>
                  </div>
                  <div className="cell">
                    <UnitSelection options={props.attributes.units} data-field='unit' data-dimension='width' value={props.attributes.basicSizes.width.unit} onChange={updateBasicSizes}/>
                  </div>
                </div>
                <div className="row">
                  <div className="cell sitemap_size_options__label">Sitemap Height</div>
                  <div className="cell sitemap_size_options__value">
                    <input className="md_input" type="number" min="0" step="10" data-field='value' data-dimension='height' onChange={updateBasicSizes} value={props.attributes.basicSizes.height.value}/>
                  </div>
                  <div className="cell">
                    <UnitSelection options={props.attributes.units} data-field='unit' data-dimension='height' value={props.attributes.basicSizes.height.unit} onChange={updateBasicSizes}/>
                  </div>
                </div>
              </div>
            </PanelRow>
            <PanelRow>
              <ToggleControl label="Advanced Configuration Options" checked={props.attributes.useAdvanced} onChange={toggleAdvanced}/>
            </PanelRow>
            {
              props.attributes.useAdvanced
                ? renderAdvancedSettings()
                : ''
            }
          </PanelBody>
        </InspectorControls>
        <div>
          <h3>Embed Sitemap block</h3>
          <div class="setting_param_label">Pages to include in Sitemap</div>
          <div className="pages_list md_input">
            {pagesList}
          </div>
          <div class="setting_param_label">
          <label>
            <input type="checkbox" defaultChecked={props.attributes.border} onChange={toggleBorder}/>
            Draw border around sitemap
          </label>
          </div>
        </div>
      </Fragment>
    )
  }),

  save: function(props) {
    const height = props.attributes.height
      ? props.attributes.height.match(/px|%/g)
        ? props.attributes.height
        : props.attributes.height + 'px'
      : 'auto'
    const width = props.attributes.width
      ? props.attributes.width.match(/px|%/g)
        ? props.attributes.width
        : props.attributes.width + 'px'
      : 'auto'
    return (
      <Fragment>
        <div 
          className={`sitemap_view_block`}
          data-width={`${props.attributes.basicSizes.width.value}${props.attributes.basicSizes.width.unit}`}
          data-height={`${props.attributes.basicSizes.height.value}${props.attributes.basicSizes.height.unit}`}
          data-border={props.attributes.border}
          data-advanced={props.attributes.useAdvanced ? btoa(JSON.stringify(props.attributes.advancedSizes)): ''}
          data-excluded={props.attributes.excluded.join()}
        />
      </Fragment>
    )
  }
})