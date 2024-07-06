/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

document.addEventListener("DOMContentLoaded", function() {
    const moduleDropdown = document.getElementById('moduleDropdown');
    const moduleVersions = document.getElementById('moduleVersions');

    moduleDropdown.addEventListener('change', async function() {
        const selectedModule = this.value;
        if (!selectedModule) return;

        const url = 'path/to/your/ajax/handler'; // Update this to your actual AJAX handler URL
        const senddata = new URLSearchParams();
        senddata.append('module', selectedModule);

        const options = {
            method: 'POST',
            body: senddata
        };

        try {
            let response = await fetch(url, options);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            let responseData = await response.json(); // Assuming the server responds with JSON data

            // Clear previous versions
            moduleVersions.innerHTML = '';

            if (responseData.versions && responseData.versions.length > 0) {
                const ul = document.createElement('ul');
                ul.classList.add('list-group');
                responseData.versions.forEach(version => {
                    const li = document.createElement('li');
                    li.classList.add('list-group-item');
                    li.textContent = version;
                    ul.appendChild(li);
                });
                moduleVersions.appendChild(ul);
            } else {
                moduleVersions.textContent = 'No versions available';
            }
        } catch (error) {
            console.error('Failed to fetch module versions:', error);
            moduleVersions.textContent = 'Error fetching versions';
        }
    });
});
