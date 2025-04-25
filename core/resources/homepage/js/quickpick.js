/*
 *
 *  * Copyright (c) 2022-2025 Bearsampp
 *  * License: GNU General Public License version 3 or later; see LICENSE.txt
 *  * Website: https://bearsampp.com
 *  * Github: https://github.com/Bearsampp
 *
 */

/**
 * QuickPick functionality
 * 
 * This script provides an implementation of the QuickPick menu
 * that focuses on reliable dropdown visibility.
 */

// Define the initQuickPick function in the global scope
window.initQuickPick = function() {
    console.log('Initializing QuickPick...');
    
    // Get the QuickPick container
    const container = document.getElementById('quickPickContainer');
    if (!container) {
        console.error('QuickPick container not found');
        return;
    }
    
    // Show loading indicator
    container.innerHTML = `
        <div class="text-center mt-3 pe-3">
            <div class="spinner-border spinner-border-sm text-light" role="status"></div>
            <span class="text-light ms-2">Loading QuickPick...</span>
        </div>
    `;
    
    // Load the QuickPick menu via AJAX
    loadQuickPickMenu(container);
};

/**
 * Loads the QuickPick menu via AJAX
 * @param {HTMLElement} container - The container element
 */
function loadQuickPickMenu(container) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', AJAX_URL, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    console.log('Raw response:', xhr.responseText);
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success && response.html) {
                        // Use the HTML returned by the server
                        container.innerHTML = response.html;
                        
                        // Initialize the dropdown functionality
                        initDropdown();
                    } else {
                        showError(container, response.error || 'Invalid response format');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    console.error('Raw response:', xhr.responseText);
                    showError(container, 'Error parsing response: ' + e.message);
                }
            } else {
                showError(container, 'Failed to load QuickPick menu');
            }
        }
    };
    
    xhr.onerror = function() {
        showError(container, 'Network error occurred');
    };
    
    // Use the existing AJAX handler for loading the QuickPick menu
    xhr.send('proc=load_quickpick');
}

/**
 * Shows an error message in the container
 * @param {HTMLElement} container - The container element
 * @param {string} message - The error message
 */
function showError(container, message) {
    console.error('QuickPick loading error:', message);
    container.innerHTML = `
        <div class="text-center mt-3 pe-3 text-danger">
            <i class="fas fa-exclamation-triangle"></i> ${message}
        </div>
    `;
}

/**
 * Initializes the dropdown functionality
 */
function initDropdown() {
    console.log('Initializing dropdown functionality...');
    
    // Get the custom select element
    const customSelect = document.querySelector('.custom-select');
    if (!customSelect) {
        console.error('Custom select element not found');
        return;
    }
    
    // Get the select button
    const selectBtn = document.querySelector('.select-button');
    if (!selectBtn) {
        console.error('Select button not found');
        return;
    }
    
    // Get the select dropdown
    const selectDropdown = document.querySelector('.select-dropdown');
    if (!selectDropdown) {
        console.error('Select dropdown not found');
        return;
    }
    
    console.log('All required elements found, initializing dropdown');
    
    // Add click event to select button
    selectBtn.addEventListener('click', function(event) {
        console.log('Select button clicked');
        event.stopPropagation();
        
        // Toggle the active class
        if (customSelect.classList.contains('active')) {
            console.log('Removing active class');
            customSelect.classList.remove('active');
            selectBtn.setAttribute('aria-expanded', 'false');
            
            // Manually hide the dropdown
            selectDropdown.style.visibility = 'visible';
            selectDropdown.style.opacity = '0';
            selectDropdown.style.transform = 'scaleY(0)';
        } else {
            console.log('Adding active class');
            customSelect.classList.add('active');
            selectBtn.setAttribute('aria-expanded', 'true');
            
            // Manually show the dropdown
            selectDropdown.style.visibility = 'visible';
            selectDropdown.style.opacity = '1';
            selectDropdown.style.transform = 'scaleY(1)';
            
            // If opening the dropdown, focus it
            setTimeout(() => {
                selectDropdown.focus();
            }, 0);
        }
        
        // Log the state after toggle
        console.log('Active class after toggle:', customSelect.classList.contains('active'));
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (customSelect && !customSelect.contains(event.target) && event.target !== selectBtn) {
            customSelect.classList.remove('active');
            selectBtn.setAttribute('aria-expanded', 'false');
            
            // Manually hide the dropdown
            selectDropdown.style.visibility = 'visible';
            selectDropdown.style.opacity = '0';
            selectDropdown.style.transform = 'scaleY(0)';
        }
    });
    
    // Add click events to module headers
    const moduleHeaders = document.querySelectorAll('.select-dropdown li.moduleheader');
    moduleHeaders.forEach(header => {
        header.addEventListener('click', function(event) {
            console.log('Module header clicked:', event.target.innerText);
            event.stopPropagation();
            
            // Toggle the visibility of module options
            const moduleName = event.target.innerText.trim();
            toggleModuleOptions(moduleName);
        });
    });
    
    // Add click events to module options
    const moduleOptions = document.querySelectorAll('.select-dropdown li.moduleoption');
    moduleOptions.forEach(option => {
        option.addEventListener('click', function(event) {
            console.log('Module option clicked');
            event.stopPropagation();
            
            // Get the module and version
            const input = option.querySelector('input[type="radio"]');
            if (input) {
                const module = input.getAttribute('data-module');
                const version = input.getAttribute('data-value');
                
                if (module && version) {
                    console.log(`Installing ${module} version ${version}`);
                    installModule(module, version);
                }
            }
            
            // Close the dropdown
            customSelect.classList.remove('active');
            selectBtn.setAttribute('aria-expanded', 'false');
            
            // Manually hide the dropdown
            selectDropdown.style.visibility = 'visible';
            selectDropdown.style.opacity = '0';
            selectDropdown.style.transform = 'scaleY(0)';
        });
    });
    
    // Hide all module options initially
    hideAllModuleOptions();
    
    console.log('Dropdown initialization complete');
}

/**
 * Toggles the visibility of module options
 * @param {string} moduleName - The name of the module
 */
function toggleModuleOptions(moduleName) {
    console.log('Toggling module options for:', moduleName);
    
    // Hide all module options first
    hideAllModuleOptions();
    
    // Show options for the selected module
    const options = document.querySelectorAll('.select-dropdown li.moduleoption');
    options.forEach(option => {
        const optionId = option.id;
        if (optionId && optionId.startsWith(moduleName + '-version-')) {
            option.style.display = 'block';
        }
    });
}

/**
 * Hides all module options
 */
function hideAllModuleOptions() {
    console.log('Hiding all module options');
    
    const options = document.querySelectorAll('.select-dropdown li.moduleoption');
    options.forEach(option => {
        option.style.display = 'none';
    });
}

/**
 * Installs a module with the specified version
 * @param {string} moduleName - The name of the module to install
 * @param {string} version - The version of the module to install
 */
async function installModule(moduleName, version) {
    console.log(`Installing module: ${moduleName}, version: ${version}`);
    
    const url = AJAX_URL;
    const senddata = new URLSearchParams();
    const progress = document.getElementById('progress');
    const progressbar = document.getElementById('progress-bar');

    const downloadmodule = document.getElementById('download-module');
    const downloadversion = document.getElementById('download-version');
    let isCompleted = false;
    let messageData = '';
    
    // Check if progress elements exist
    if (!progress || !progressbar || !downloadmodule || !downloadversion) {
        console.error('Progress elements not found, creating them');
        
        // Create progress elements if they don't exist
        const progressHtml = `
            <div id="progress" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 9999;">
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: white; padding: 20px; border-radius: 5px; text-align: center;">
                    <h3>Installing <span id="download-module">${moduleName}</span> <span id="download-version">${version}</span></h3>
                    <div class="progress">
                        <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                </div>
            </div>
        `;
        
        // Append progress elements to the body
        document.body.insertAdjacentHTML('beforeend', progressHtml);
        
        // Get the newly created elements
        const newProgress = document.getElementById('progress');
        const newProgressbar = document.getElementById('progress-bar');
        const newDownloadmodule = document.getElementById('download-module');
        const newDownloadversion = document.getElementById('download-version');
        
        // Show the progress
        newProgress.style.display = 'block';
        newProgressbar.innerText = `Downloading ${moduleName} ${version}`;
        newDownloadmodule.innerText = moduleName;
        newDownloadversion.innerText = version;
    } else {
        // Show the progress
        progressbar.innerText = `Downloading ${moduleName} ${version}`;
        progress.style.display = 'block';
        downloadmodule.innerText = moduleName;
        downloadversion.innerText = version;
    }
    
    // Use the correct parameters for the AJAX request
    senddata.append('proc', 'quickpick');
    senddata.append('module', moduleName);
    senddata.append('version', version);
    
    console.log('AJAX URL:', url);
    console.log('AJAX Parameters:', senddata.toString());

    const options = {
        method: 'POST',
        body: senddata,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    };

    try {
        console.log('Sending AJAX request...');
        const response = await fetch(url, options);
        console.log('AJAX response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let responseText = '';
        let isDownloading = true;

        while (true) {
            const {done, value} = await reader.read();
            if (done) break;
            responseText += decoder.decode(value, {stream: true});
            console.log('Received chunk:', responseText);

            const parts = responseText.split('}{').map((part, index, arr) => {
                if (index === 0) return part + '}';
                if (index === arr.length - 1) return '{' + part;
                return '{' + part + '}';
            });

            for (const part of parts) {
                try {
                    const data = JSON.parse(part);
                    console.log('Parsed data:', data);
                    
                    if (data.progress) {
                        console.log('Progress:', data.progress);
                        const progressValue = data.progress;
                        progressbar.style.width = '100%';
                        if (isDownloading) {
                            progressbar.innerText = `${progressValue} KBytes Downloaded`;
                        } else {
                            progressbar.innerText = `${progressValue} Extracted`;
                        }
                    } else if (data.success) {
                        console.log('Success:', data.success);
                        isCompleted = true;
                        messageData = data.success || data.message || 'Installation completed successfully';
                    } else if (data.error) {
                        console.error('Error:', data.error);
                        window.alert(`Error: ${data.error}`);
                    } else if (data.phase === 'extracting') {
                        console.log('Phase changed to extracting');
                        isDownloading = false;
                    }
                } catch (error) {
                    console.warn('JSON parse error (expected for incomplete parts):', error);
                    // Ignore JSON parse errors for incomplete parts
                }
            }

            // Clear responseText to keep only the unprocessed part
            responseText = parts[parts.length - 1].startsWith('{') ? parts[parts.length - 1] : '';
        }
    } catch (error) {
        console.error('Failed to install module:', error);
        window.alert(`Failed to install module: ${error.message}`);
    } finally {
        // Hide the progress dialog
        if (progress) {
            progress.style.display = 'none';
        }
        
        // Show success message if completed
        if (isCompleted) {
            console.log('Installation completed successfully');
            if (messageData) {
                // Show the success message with an OK button
                alert(messageData);
            } else {
                // Show a default success message if none was provided
                alert('Module installed successfully.');
            }
            
            // Always reload the page after the user clicks OK
            console.log('Reloading page...');
            location.reload();
        }
    }
}
