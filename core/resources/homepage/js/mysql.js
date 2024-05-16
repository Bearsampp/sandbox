/*$(document).ready(function() {
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
});*/

async function getMySQLStatus() {
  let url = '/b30a9b2155cf4012e52675f2d0559415/ajax.php';
  let data = new URLSearchParams();
  let proc='mysql';
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

    let q = document.querySelector('.mysql-checkport');
    let ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend',data.checkport);

    q = document.querySelector('.mysql-version-list');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend',data.versions);
  }
}
document.addEventListener("DOMContentLoaded", function() {
  if (document.querySelector('a[name=mysql]').name = 'mysql') {
    getMySQLStatus();
  }
})
