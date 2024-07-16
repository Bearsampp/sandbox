/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

document.addEventListener("DOMContentLoaded", function () {
    var selects = document.querySelectorAll('select');
    selects.forEach(function (select) {
        select.addEventListener('change', function () {
            var selectedOption = select.options[select.selectedIndex];
            var target = selectedOption.getAttribute('data-target');
            var id = select.id;
            var divs = document.querySelectorAll("div[id^='" + id + "']");
            divs.forEach(function (div) {
                div.style.display = 'none';
            });
            var targetDiv = document.getElementById(id + "-" + target);
            if (targetDiv) {
                targetDiv.style.display = 'block';
            }

            // New code to handle module installation
            var module = select.getAttribute('data-module');
            var version = selectedOption.value;
            if (module && version) {
                installModule(module, version);
            }
        });
    });

    async function installModule(module, version) {
        const url = AJAX_URL; // Ensure this variable is defined and points to your server-side script handling the AJAX requests.
        const senddata = new URLSearchParams();
        senddata.append('module', module);
        senddata.append('version', version);
        senddata.append('proc', 'quickpick'); // Setting 'proc' to 'quickpick'

        const options = {
            method: 'POST',
            body: senddata,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        };

        try {
            let response = await fetch(url, options);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            let responseText = await response.text();
            console.log('Response Text:', responseText); // Log the response text
            try {
                let data = JSON.parse(responseText);
                if (data.error && data.error === 'Invalid proc parameter') {
                    console.error('Invalid proc parameter:', data.error);
                } else {
                    console.log(data);
                    // Handle the response if needed
                }
            } catch (error) {
                console.error('Failed to parse response:', error);
            }
        } catch (error) {
            console.error('Failed to install module:', error);
        }
    }
});
