/*
 * Copyright (c) 2021-2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

$(document).ready(function() {
  if ($('a[name=nodejs]').length) {
    $.ajax({
      data: {
        proc: 'nodejs'
      },
      success: function(data) {
        $('.nodejs-status').append(data.status);
        $('.nodejs-status').find('.loader').remove();

        $('.nodejs-version-list').append(data.versions);
        $('.nodejs-version-list').find('.loader').remove();
      }
    });
  }
});
