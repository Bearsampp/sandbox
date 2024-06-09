/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Fetches the status of Memcached from the server and updates the DOM with the received data.
 *
 * @async
 * @function getMemCachedStatus
 * @returns {Promise<void>} A promise that resolves when the status has been fetched and the DOM updated.
 */
async function getMemCachedStatus() {
  const url = AJAX_URL;
  const proc = 'memcached';
  const senddata = new URLSearchParams();
  senddata.append(`proc`, proc);
  const options = {
    method: 'POST',
    body: senddata
  }
  let response = await fetch(url, options);
  if (!response.ok) {
    console.log('Error receiving from ajax.php');
  } else {
    let myajaxresponse = await response.text();
    let data;
    try {
      data = JSON.parse(myajaxresponse);
    } catch (error) {
      console.error('Failed to parse response:', error);
    }

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

/**
 * Event listener for the DOMContentLoaded event.
 * Checks if the 'memcached' anchor tag is present and calls getMemCachedStatus if it is.
 *
 * @function
 */
document.addEventListener("DOMContentLoaded", function () {
  if (document.querySelector('a[name=memcached]').name === 'memcached') {
    getMemCachedStatus();
  }
});
