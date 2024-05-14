/*
 * Copyright (c) 2021-2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

$(document).ready(function() {
  if ($('a[name=mysql]').length) {
    $.ajax({
      data: {
        proc: 'mysql'
      },
      success: function(data) {
        $('.mysql-checkport').append(data.checkport);
        $('.mysql-checkport').find('.loader').remove();

        $('.mysql-version-list').append(data.versions);
        $('.mysql-version-list').find('.loader').remove();
      }
    });
  }
});
