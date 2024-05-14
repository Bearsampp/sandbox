/*
 * Copyright (c) 2021-2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

$(document).ready(function() {
  if ($('.latestversion').length) {
    $.ajax({
      data: {
        proc: 'latestversion'
      },
      success: function(data) {
        if (data.display) {
          $('.latestversion-download').append(data.download);
          $('.latestversion-changelog').append(data.changelog);
          $('.latestversion').show();
        }
      }
    });
  }
});
