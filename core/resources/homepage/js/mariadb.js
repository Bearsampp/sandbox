/*
 * Copyright (c) 2-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: @author@
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

$(document).ready(function() {
  if ($('a[name=mariadb]').length) {
    $.ajax({
      data: {
        proc: 'mariadb'
      },
      success: function(data) {
        $('.mariadb-checkport').append(data.checkport);
        $('.mariadb-checkport').find('.loader').remove();

        $('.mariadb-version-list').append(data.versions);
        $('.mariadb-version-list').find('.loader').remove();
      }
    });
  }
});
