/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Fetches the MailHog status from the server and updates the DOM with the received data.
 *
 * @async
 * @function getMailHogStatus
 * @returns {Promise<void>} A promise that resolves when the MailHog status has been fetched and the DOM updated.
 */
async function getMailHogStatus() {
  const url = AJAX_URL;
  let data = new URLSearchParams();
  const proc = 'mailhog';
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
    let data;
    try {
      data = JSON.parse(myajaxresponse);
    } catch (error) {
      console.error('Failed to parse response:', error);
    }

    let q = document.querySelector('.mailhog-checkport');
    let ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.checkport);

    q = document.querySelector('.mailhog-version-list');
    ql = q.querySelector('.loader');
    ql.remove();
    q.insertAdjacentHTML('beforeend', data.versions);
  }
}

/**
 * Event listener for the DOMContentLoaded event.
 * Checks if the MailHog anchor element is present and triggers the MailHog status fetch.
 *
 * @event DOMContentLoaded
 */
document.addEventListener("DOMContentLoaded", function() {
  if (document.querySelector('a[name=mailhog]').name === 'mailhog') {
    getMailHogStatus();
  }
});
