/*$(document).ready(function() {
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
});*/

async function getPostgresStatus() {
  let url = '/b30a9b2155cf4012e52675f2d0559415/ajax.php';
  let data = new URLSearchParams();
  let proc='postgresql';
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

    let q = document.querySelector('.postgresql-checkport');
    let ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend',data.checkport);

    q = document.querySelector('.postgresql-version-list');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend',data.versions);
  }
}
document.addEventListener("DOMContentLoaded", function() {
  if (document.querySelector('a[name=postgresql]').name = 'postgresql') {
    getPostgresStatus();
  }
})
