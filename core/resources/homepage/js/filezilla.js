/*$(document).ready(function() {
  if ($('a[name=filezilla]').length) {
    $.ajax({
      data: {
        proc: 'filezilla'
      },
      success: function(data) {
        $('.filezilla-checkport').append(data.checkport);
        $('.filezilla-checkport').find('.loader').remove();

        $('.filezilla-version-list').append(data.versions);
        $('.filezilla-version-list').find('.loader').remove();
      }
    });
  }
});*/

async function getFileZillaStatus() {
  let url = '/b30a9b2155cf4012e52675f2d0559415/ajax.php';
  let data = new URLSearchParams();
  let proc='filezilla';
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

    let q = document.querySelector('.filezilla-checkport');
    let ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend',data.checkport);

    q = document.querySelector('.filezilla-version-list');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend',data.versions);
  }
}
document.addEventListener("DOMContentLoaded", function() {
  if (document.querySelector('a[name=filezilla]').name = 'filezilla') {
    getFileZillaStatus();
  }
})
