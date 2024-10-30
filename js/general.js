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

mdsm_teamChange = function(el) {
  mdsm_getOptions().then(options => {
    const currentTeam = options.selected_team * 1
    const selectedTeam = el.options[el.selectedIndex].value * 1
    if (currentTeam) {
      if (currentTeam !== selectedTeam) {
        if (confirm('Warning. Current doodle will be removed. Are you sure you want to change team?')) {
          el.form.submit()
        }
      }
    } else {
      el.form.submit()
    }
  })
}

mdsm_save_task = function(el) {
  if (!el.classList.contains('disabled')) {
    el.classList.add("running", "disabled");
    el.closest('form').submit()
  }
  return false
}

function string_to_slug (str) {
  str = str || 'no-title';
  str = str.replace(/^\s+|\s+$/g, ''); // trim
  str = str.toLowerCase();

  // remove accents, swap ñ for n, etc
  var from = "àáäâèéëêìíïîòóöôùúüûñç·/_,:;";
  var to   = "aaaaeeeeiiiioooouuuunc------";

  for (var i=0, l=from.length ; i<l ; i++)
  {
    str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
  }

  str = str.replace('.', '-') // replace a dot by a dash 
    .replace(/[^a-z0-9 -]/g, '') // remove invalid chars
    .replace(/\s+/g, '-') // collapse whitespace and replace by a dash
    .replace(/-+/g, '-'); // collapse dashes

  return str;
}

function logoutConfirm (tasksExist) {
  var logoutFormId = 'md_logout'
  var isFirefox = typeof InstallTrigger !== 'undefined';
  var msg = 'WARNING: If you disconect, you will not be able to continue syncing your website'
  msg += isFirefox ? '\n' : ''
  msg += 'with Mind Doodle and you will lose the tasks you added.'
  msg += '\n\nAre you sure you want to disconnect and delete your tasks?'
  if (tasksExist) {
    if (confirm(msg)) {
      document.getElementById(logoutFormId).submit()
    }
  } else {
    document.getElementById(logoutFormId).submit()
  }
}
