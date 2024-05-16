/*$(document).ready(function() {
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
});*/

async function getMariaDBStatus() {
  let url = '/b30a9b2155cf4012e52675f2d0559415/ajax.php';
  let data = new URLSearchParams();
  let proc = 'mariadb';
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

    let q = document.querySelector('.mariadb-checkport');
    let ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend',data.checkport);

    q = document.querySelector('.mariadb-version-list');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend',data.versions);
  }
}
document.addEventListener("DOMContentLoaded", function() {
  if (document.querySelector('a[name=mariadb]').name = 'mariadb') {
    getMariaDBStatus();
  }
})
