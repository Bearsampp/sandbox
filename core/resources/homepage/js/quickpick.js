/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

document.addEventListener("DOMContentLoaded", function () {

    let selectedHeader = null; // Store which module has been selected to allow open/close of versions

    const customSelect = document.querySelector(".custom-select"); // parent div of quickpick select
    const selectBtn = document.querySelector(".select-button"); // trigger button to pop down ul
    if(selectBtn !== null ) {
        // add a click event to select button
        selectBtn.addEventListener("click", () => {
            // add/remove active class on the container element to show/hide
            customSelect.classList.toggle("active");
            // update the aria-expanded attribute based on the current state
            selectBtn.setAttribute(
                "aria-expanded",
                selectBtn.getAttribute("aria-expanded") === "true" ? "false" : "true"
            );
            scrolltoview();
        });

        const optionsList = document.querySelectorAll(".select-dropdown li.moduleheader");

        optionsList.forEach((option) => {
            function handler(e) {
                // Click Events
                if (e.type === "click" && e.clientX !== 0 && e.clientY !== 0) {

                    if (selectedHeader !== e.target.innerText) {
                        showModule(e.target.innerText);
                        selectedHeader = e.target.innerText;

                    } else {
                        hideall();
                        selectedHeader = null;

                    }

                }
                // Key Events
                if (e.key === "Enter") {
                    if (selectedHeader !== e.target.innerText) {
                        showModule(e.target.innerText);
                        selectedHeader = e.target.innerText;

                    } else {
                        hideall();
                        selectedHeader = null;
                    }

                }
            }

            option.addEventListener("keyup", handler);
            option.addEventListener("click", handler);
        });

        hideall();

        let selects = document.querySelectorAll('.select-dropdown li.moduleoption');
        selects.forEach(function (select) {
            select.addEventListener('change', function (e) {
                console.log(e);
                let selectedOption = e.target;

                let moduleName = selectedOption.getAttribute('data-module');
                let version = selectedOption.getAttribute('data-value');
                if (moduleName && version) {
                    installModule(moduleName, version);
                }
                hideall()
                customSelect.classList.toggle("active", false);
            });

        });
        scrolltoview();
    }
});

function scrolltoview() {
    let e = document.getElementById('select-dropdown');
    e.scrollIntoView(true);
}

function showModule(modName) {
    hideall();
    let options = document.querySelectorAll('li[id^='.concat(modName).concat(']'));
    options.forEach(function (option) {
        option.hidden = false;
        option.removeAttribute('hidden');
    });
}

function hideall() {
    let options = document.querySelectorAll('.moduleoption');
    options.forEach(function (option) {
        option.hidden = true;
    });

}

async function installModule(moduleName, version) {
    const url = AJAX_URL; // Ensure this variable is defined and points to your server-side script handling the AJAX requests.
    const senddata = new URLSearchParams();

    senddata.append('module', moduleName);
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
            if (data.error) {
                console.error('Error:', data.error);
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
    hideall();
}
