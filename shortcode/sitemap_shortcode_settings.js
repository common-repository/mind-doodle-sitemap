var params = top.tinymce.activeEditor.windowManager.getParams()
var $ = params.jquery
var {editor} = params

const context = document

$(function() {
  $('.tabgroup > div', context).hide();
  $('.tabgroup > div:first-of-type', context).show();
  $('.tabs a', context).click(function(e){
    e.preventDefault();
    var $this = $(this),
        tabgroup = '#'+$this.parents('.tabs').data('tabgroup'),
        others = $this.closest('li').siblings().children('a'),
        target = $this.attr('href');
    others.removeClass('active');
    $this.addClass('active');
    $(tabgroup, context).children('div').hide();
    $(target, context).show();
  })

  const advancedCheckbox = document.getElementById('advanced_size')
  const basicSizeSettings = document.getElementById('basic_settings')
  const advancedSizeSettings = document.getElementById('advanced_settings')

  

  advancedCheckbox.addEventListener('change', function(e) {
    if (e.target.checked) {
      basicSizeSettings.style.display = 'none'
      advancedSizeSettings.style.display = 'initial'
    } else {
      basicSizeSettings.style.display = 'initial'
      advancedSizeSettings.style.display = 'none'
    }
  })
})
