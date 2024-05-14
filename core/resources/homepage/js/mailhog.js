/*
 * Copyright (c) 2021-2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

$(document).ready(function() {
  if ($('a[name=mailhog]').length) {
    $.ajax({
      data: {
        proc: 'mailhog'
      },
      success: function(data) {
        $('.mailhog-checkport').append(data.checkport);
        $('.mailhog-checkport').find('.loader').remove();

        $('.mailhog-version-list').append(data.versions);
        $('.mailhog-version-list').find('.loader').remove();
      }
    });
  }
});
