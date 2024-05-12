/*
 * Copyright (c) 2-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: @author@
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

$(document).ready(function() {
  if ($('a[name=postgresql]').length) {
    $.ajax({
      data: {
        proc: 'postgresql'
      },
      success: function(data) {
        $('.postgresql-checkport').append(data.checkport);
        $('.postgresql-checkport').find('.loader').remove();

        $('.postgresql-version-list').append(data.versions);
        $('.postgresql-version-list').find('.loader').remove();
      }
    });
  }
});
