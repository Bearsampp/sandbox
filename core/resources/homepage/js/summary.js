/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * @constant {string} AJAX_URL - The URL endpoint for AJAX requests.
 */
const AJAX_URL = "1fd5bfc5c72323f1d019208088a6de21/ajax.php";

/**
 * Fetches the summary status from the server and updates the DOM with the received data.
 *
 * @async
 * @function getSummaryStatus
 * @returns {Promise<void>}
 */
async function getSummaryStatus() {
  const url = AJAX_URL;
  const proc = 'summary';
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

    /**
     * Updates a specific summary section in the DOM.
     *
     * @param {string} selector - The CSS selector for the summary section.
     * @param {string} content - The content to insert into the summary section.
     */
    function updateSummarySection(selector, content) {
      let q = document.querySelector(selector);
      let ql = q.querySelector('.loader');
      ql.remove();
      q.insertAdjacentHTML('beforeend', content);
    }

    updateSummarySection('.summary-binapache', data.binapache);
    updateSummarySection('.summary-binfilezilla', data.binfilezilla);
    updateSummarySection('.summary-binmailhog', data.binmailhog);
    updateSummarySection('.summary-binmariadb', data.binmariadb);
    updateSummarySection('.summary-binmysql', data.binmysql);
    updateSummarySection('.summary-binpostgresql', data.binpostgresql);
    updateSummarySection('.summary-binmemcached', data.binmemcached);
    updateSummarySection('.summary-binnodejs', data.binnodejs);
    updateSummarySection('.summary-binphp', data.binphp);
  }
}

/**
 * Event listener for the DOMContentLoaded event.
 * Executes the getSummaryStatus function if the summary element is present.
 */
document.addEventListener("DOMContentLoaded", function () {
  if (document.querySelector('.summary').className === 'row summary') {
    getSummaryStatus();
  }
});
