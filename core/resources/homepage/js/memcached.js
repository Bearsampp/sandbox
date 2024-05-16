/*$(document).ready(function() {
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
});*/

async function getMemCachedStatus() {
  let url = '/b30a9b2155cf4012e52675f2d0559415/ajax.php';
  let data = new URLSearchParams();
  let proc = 'memcached';
  data.append(`proc`, proc);
  const options = {
    method: 'POST',
    body: data
  }
  let response = await fetch(url, options);
  if (!response.ok) {
    console.log('Error receiving from ajax.php');
  } else {
    let myajaxresponse = await response.text();
    let data = JSON.parse(myajaxresponse);

    let q = document.querySelector('.memcached-checkport');
    let ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.checkport);

    q = document.querySelector('.memcached-version-list');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.versions);
  }
}

document.addEventListener("DOMContentLoaded", function () {
  if (document.querySelector('a[name=memcached]').name = 'memcached') {
    getMemCachedStatus();
  }
})
