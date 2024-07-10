/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

document.getElementById('moduleDropdown').addEventListener('change', async function () {
    const selectedModule = this.value;

    // Hide all module versions
    document.querySelectorAll('.moduleVersions').forEach(function (element) {
        element.style.display = 'none';
    });

    // Show the selected module's versions
    const moduleVersionsDiv = document.getElementById('moduleVersions-' + selectedModule);
    moduleVersionsDiv.style.display = 'block';

    // Fetch and display the module versions
    const response = await fetch(AJAX_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ module: selectedModule })
    });

    const data = await response.json();
    if (data.versions && data.versions[selectedModule]) {
        moduleVersionsDiv.innerHTML = data.versions[selectedModule].map(version => `<div>${version}</div>`).join('');
    } else {
        moduleVersionsDiv.innerHTML = '<div>Error fetching versions</div>';
    }
});
