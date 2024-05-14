/*
 * Copyright (c) 2021-2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

$(document).ready(function() {
  if ($('a[name=memcached]').length) {
    $.ajax({
      data: {
        proc: 'memcached'
      },
      success: function(data) {
        $('.memcached-checkport').append(data.checkport);
        $('.memcached-checkport').find('.loader').remove();

        $('.memcached-version-list').append(data.versions);
        $('.memcached-version-list').find('.loader').remove();
      }
    });
  }
});
