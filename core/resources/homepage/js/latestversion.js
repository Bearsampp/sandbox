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
 * Fetches the latest version status from the server and updates the DOM accordingly.
 *
 * @async
 * @function getLatestVersionStatus
 * @returns {Promise<void>}
 */
async function getLatestVersionStatus() {
    const url = AJAX_URL; // Ensure this variable is defined and points to your server-side script handling the AJAX requests.
    let data = new URLSearchParams();
    data.append('proc', 'latestversion'); // Setting 'proc' to 'latestversion'

    const options = {
        method: 'POST',
        body: data
    };

    try {
        let response = await fetch(url, options);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        let responseData = await response.json(); // Assuming the server responds with JSON data

        if (responseData.display) {
            document.querySelector('.latestversion-download').insertAdjacentHTML('beforeend', responseData.download);
            document.querySelector('.latestversion-changelog').insertAdjacentHTML('beforeend', responseData.changelog);
            document.getElementById("latestversionnotify").style.display = 'block';
        }
    } catch (error) {
        console.error('Failed to fetch latest version status:', error);
    }
}

/**
 * Initializes the fetching of the latest version status when the DOM content is fully loaded.
 *
 * @event DOMContentLoaded
 */
document.addEventListener("DOMContentLoaded", function() {
    getLatestVersionStatus();
});
