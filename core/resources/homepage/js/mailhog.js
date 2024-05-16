/*$(document).ready(function() {
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
});*/

async function getMailHogStatus() {
  let url = '/b30a9b2155cf4012e52675f2d0559415/ajax.php';
  let data = new URLSearchParams();
  let proc = 'mailhog';
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

    let q = document.querySelector('.mailhog-checkport');
    let ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend',data.checkport);

    q = document.querySelector('.mailhog-version-list');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend',data.versions);
  }
}
document.addEventListener("DOMContentLoaded", function() {
  if (document.querySelector('a[name=mailhog]').name = 'mailhog') {
    getMailHogStatus();
  }
})

