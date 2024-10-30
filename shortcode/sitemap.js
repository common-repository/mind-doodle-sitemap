(function() {
  tinymce.PluginManager.add( 'sitemap', function( editor, url ) {
    var sh_tag = 'sitemap'
    // Add Button to Visual Editor Toolbar
    editor.addButton('sitemap', {
        title: 'Embed Sitemap',
        cmd: 'sitemap',
        // image: url + '/icon-brain.svg',
        icon: 'ab-icon dashicons-before dashicons dashicons-networking'
    });
    editor.addCommand('sitemap', function() {
      editor.windowManager.open(
        {
          title: 'Embed Sitemap Options',
          url: ajaxurl + '?action=sitemap_shortcode_settings',
          height: 486,
          inline: 1,
          buttons: [{ 
              text: 'Apply',
              subtype: 'primary',
              onclick: function(e) {
                var frame = $(e.currentTarget).find("iframe").get(0)
                var content = frame.contentDocument


                var excluded = $(content).find("ul#pages_list .menu-item-checkbox:not(:checked)").map((index, element) => ($(element).val())).get()
                var border = $(content).find("#show_border").prop('checked')
                var advanced = $(content).find("#advanced_size").prop('checked')

                var heightValue = parseFloat($(content).find("#sitemap_height").val())
                var heightUnit = $(content).find("#height_unit").val()
                var widthValue = parseFloat($(content).find("#sitemap_width").val())
                var widthUnit = $(content).find("#width_unit").val()

                var shortcode_str = '[' + sh_tag;
                
                shortcode_str += widthValue ? ' width="' + widthValue + widthUnit + '"' : ''
                shortcode_str += heightValue ? ' height="' + heightValue + heightUnit + '"' : ''
                
                if (advanced) {
                  var advanced = {mobile: {}, tablet: {}, desktop: {}}

                  const mobileRWidth = $(content).find('#mobile_tab #mobile_rwidth').val()
                  const mobileWidth = $(content).find('#mobile_tab #mobile_width').val()
                  const mobileWidthUnit = $(content).find('#mobile_tab #mobile_width_unit').val()
                  const mobileHeight = $(content).find('#mobile_tab #mobile_height').val()
                  const mobileHeightUnit = $(content).find('#mobile_tab #mobile_height_unit').val()
                 
                  const tabletRWidth = $(content).find('#tablet_tab #tablet_rwidth').val()
                  const tabletWidth = $(content).find('#tablet_tab #tablet_width').val()
                  const tabletWidthUnit = $(content).find('#tablet_tab #tablet_width_unit').val()
                  const tabletHeight = $(content).find('#tablet_tab #tablet_height').val()
                  const tabletHeightUnit = $(content).find('#tablet_tab #tablet_height_unit').val()

                  const desktopRWidth = $(content).find('#desktop_tab #desktop_rwidth').val()
                  const desktopWidth = $(content).find('#desktop_tab #desktop_width').val()
                  const desktopWidthUnit = $(content).find('#desktop_tab #desktop_width_unit').val()
                  const desktopHeight = $(content).find('#desktop_tab #desktop_height').val()
                  const desktopHeightUnit = $(content).find('#desktop_tab #desktop_height_unit').val()

                  advanced.mobile = {
                    ...advanced.mobile, 
                    ...(mobileRWidth ? {response: {value: mobileRWidth}} : {}),
                    ...(mobileWidth ? {width: {value: mobileWidth, unit: mobileWidthUnit}} : {}),
                    ...(mobileHeight ? {height: {value: mobileHeight, unit: mobileHeightUnit}} : {})
                  }
                  advanced.tablet = {
                    ...advanced.tablet, 
                    ...(tabletRWidth ? {response: {value: tabletRWidth}} : {}),
                    ...(tabletWidth ? {width: {value: tabletWidth, unit: tabletWidthUnit}} : {}),
                    ...(tabletHeight ? {height: {value: tabletHeight, unit: tabletHeightUnit}} : {})
                  }
                  advanced.desktop = {
                    ...advanced.desktop, 
                    ...(desktopRWidth ? {response: {value: desktopRWidth}} : {}),
                    ...(desktopWidth ? {width: {value: desktopWidth, unit: desktopWidthUnit}} : {}),
                    ...(desktopHeight ? {height: {value: desktopHeight, unit: desktopHeightUnit}} : {})
                  }

                  shortcode_str += ' advanced=\'' + JSON.stringify(advanced) + '\''
                }
                
                shortcode_str += excluded.length ? ' excluded="'+excluded.join()+'"' : ''
                shortcode_str += !border ? ' border=0' : ' border=1'
                shortcode_str += ']'

                editor.insertContent( shortcode_str);
                top.tinymce.activeEditor.windowManager.close();
              }
            },
            {
              text: 'Cancel',
              onclick: 'close'
            }
          ]
        }, {
          editor: editor,
          jquery: $
        }
      )
    });
  })
})();