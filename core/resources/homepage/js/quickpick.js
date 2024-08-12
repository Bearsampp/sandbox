/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * This script handles the dynamic behavior of the QuickPick module selection on the homepage.
 * It includes the following functionalities:
 * - Toggles the visibility of the module selection dropdown.
 * - Handles click and key events for module headers and options.
 * - Installs the selected module version via an AJAX request.
 * - Displays a modal during the installation process and handles the response.
 * - Closes the modal and reloads the page after the installation process.
 *
 * Functions:
 * - scrolltoview(): Scrolls the module selection dropdown into view.
 * - showModule(modName): Displays the options for the selected module.
 * - hideall(): Hides all module options.
 * - installModule(moduleName, version): Sends an AJAX request to install the selected module version.
 * - closeModalAndReload(): Closes the modal and reloads the page.
 *
 * Event Listeners:
 * - DOMContentLoaded: Initializes the event listeners for the module selection dropdown and options.
 * - click: Toggles the visibility of the module selection dropdown and handles module option selection.
 * - keyup: Handles key events for module headers.
 */
document.addEventListener("DOMContentLoaded", function () {
    let selectedHeader = null; // Store which module has been selected to allow open/close of versions
    let progressValue = 0; // Initialize progressValue as a number

    const customSelect = document.querySelector(".custom-select"); // parent div of quickpick select
    const selectBtn = document.querySelector(".select-button"); // trigger button to pop down ul
    if (selectBtn !== null) {
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
            select.addEventListener('click', function (e) {
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
    const url = AJAX_URL;
    const senddata = new URLSearchParams();
    const progress = document.getElementById('progress');
    const progressbar = document.getElementById('progress-bar');

    const downloadmodule = document.getElementById('download-module');
    const downloadversion = document.getElementById('download-version');

    progressbar.innerText = "Downloading ".concat(moduleName).concat(' ').concat(version);
    progress.style.display = "block";
    downloadmodule.innerText = moduleName;
    downloadversion.innerText = version;
    senddata.append('module', moduleName);
    senddata.append('version', version);
    senddata.append('proc', 'quickpick');

    const options = {
        method: 'POST',
        body: senddata,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    };

    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let responseText = '';

        while (true) {
            const {done, value} = await reader.read();
            if (done) break;
            responseText += decoder.decode(value, {stream: true});

            // Process each JSON object separately
            const parts = responseText.split('}{').map((part, index, arr) => {
                if (index === 0) return part + '}';
                if (index === arr.length - 1) return '{' + part;
                return '{' + part + '}';
            });

            for (const part of parts) {
                try {
                    const data = JSON.parse(part);
                    if (data.progress) {
                        console.log('Progress:', data.progress);
                        // Update progress bar or other UI elements here
                        const progressValue = data.progress;
                        progressbar.style.width = '100%';
                        progressbar.setAttribute('aria-valuenow', progressValue);
                        progressbar.innerText = 'Extracting please be patient...';
                    } else if (data.success) {
                        console.log(data);
                        window.alert(data.message);
                    } else if (data.error) {
                        console.error('Error:', data.error);
                        window.alert(`Error: ${data.error}`);
                    }
                } catch (error) {
                    // Ignore JSON parse errors for incomplete parts
                }
            }
        }
    } catch (error) {
        console.error('Failed to install module:', error);
        window.alert('Failed to install module: ' + error.message);
    } finally {
        location.reload();
    }
}
