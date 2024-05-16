/*$(document).ready(function() {
  if ($('.summary').length) {
    $.ajax({
      data: {
        proc: 'summary'
      },
      success: function(data) {
        $('.summary-binapache').append(data.binapache);
        $('.summary-binapache').find('.loader').remove();

        $('.summary-binfilezilla').append(data.binfilezilla);
        $('.summary-binfilezilla').find('.loader').remove();

        $('.summary-binmailhog').append(data.binmailhog);
        $('.summary-binmailhog').find('.loader').remove();

        $('.summary-binmariadb').append(data.binmariadb);
        $('.summary-binmariadb').find('.loader').remove();

        $('.summary-binmysql').append(data.binmysql);
        $('.summary-binmysql').find('.loader').remove();

        $('.summary-binpostgresql').append(data.binpostgresql);
        $('.summary-binpostgresql').find('.loader').remove();

        $('.summary-binmemcached').append(data.binmemcached);
        $('.summary-binmemcached').find('.loader').remove();

        $('.summary-binnodejs').append(data.binnodejs);
        $('.summary-binnodejs').find('.loader').remove();

        $('.summary-binphp').append(data.binphp);
        $('.summary-binphp').find('.loader').remove();
      }
    });
  }
});*/

async function getSummaryStatus() {
  let url = '/b30a9b2155cf4012e52675f2d0559415/ajax.php';
  let data = new URLSearchParams();
  let proc='summary';
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
    let q = document.querySelector('.summary-binapache');
    let ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.binapache);

    q = document.querySelector('.summary-binfilezilla');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.binfilezilla);

    q = document.querySelector('.summary-binmailhog');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.binmailhog);

    q = document.querySelector('.summary-binmariadb');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.binmariadb);

    q = document.querySelector('.summary-binmysql');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.binmysql);

    q = document.querySelector('.summary-binpostgresql');
    q.insertAdjacentHTML('beforeend', data.binpostgresql);

    q = document.querySelector('.summary-binmemcached');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.binmemcached);

    q = document.querySelector('.summary-binnodejs');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.binnodejs);

    q = document.querySelector('.summary-binphp');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.binphp);
  }
}
document.addEventListener("DOMContentLoaded", function() {
  if (document.querySelector('a[name=summary]').name = 'summary') {
    getSummaryStatus();
  }
})
