/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Fetches the status of the PostgreSQL service and updates the DOM with the received data.
 *
 * @async
 * @function getPostgresStatus
 * @returns {Promise<void>} A promise that resolves when the status has been fetched and the DOM updated.
 */
async function getPostgresStatus() {
  const url = AJAX_URL;
  const proc = 'postgresql';
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

    let q = document.querySelector('.postgresql-checkport');
    let ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.checkport);

    q = document.querySelector('.postgresql-version-list');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.versions);
  }
}

/**
 * Event listener for the DOMContentLoaded event.
 * Checks if the PostgreSQL section is present and calls getPostgresStatus if it is.
 *
 * @function
 */
document.addEventListener("DOMContentLoaded", function () {
  if (document.querySelector('a[name=postgresql]').name === 'postgresql') {
    getPostgresStatus();
  }
});
