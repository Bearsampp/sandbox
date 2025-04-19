/*
 *
 *  * Copyright (c) 2022-2025 Bearsampp
 *  * License: GNU General Public License version 3 or later; see LICENSE.txt
 *  * Website: https://bearsampp.com
 *  * Github: https://github.com/Bearsampp
 *
 */

/**
 * Reload Bearsampp configuration
 * Sends an AJAX request to trigger the reload action on the server
 */
function reloadBearsampp() {
    // Show loading indicator
    const reloadButton = document.querySelector('.reload i');
    if (reloadButton) {
        reloadButton.classList.add('fa-spin');
    }
    
    // Create AJAX request
    const xhr = new XMLHttpRequest();
    // Use the correct path to ajax.quickpick.php
    xhr.open('POST', 'ajax/ajax.quickpick.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        // Stop the spinning animation
        if (reloadButton) {
            reloadButton.classList.remove('fa-spin');
        }
        
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Show success message
                    showNotification('success', 'Bearsampp has been reloaded successfully.');
                } else if (response.error) {
                    // Show error message
                    showNotification('error', 'Failed to reload Bearsampp: ' + response.error);
                    console.error('Reload error:', response.error);
                }
            } catch (e) {
                showNotification('error', 'Invalid response from server.');
                console.error('Parse error:', e, xhr.responseText);
            }
        } else {
            showNotification('error', 'Request failed. Status: ' + xhr.status);
            console.error('HTTP error:', xhr.status);
        }
    };
    
    xhr.onerror = function() {
        // Stop the spinning animation
        if (reloadButton) {
            reloadButton.classList.remove('fa-spin');
        }
        showNotification('error', 'Request failed. Network error.');
        console.error('Network error');
    };
    
    // Send the request
    xhr.send('action=reload');
}

/**
 * Display a notification message
 * @param {string} type - Type of notification (success, error)
 * @param {string} message - Message to display
 */
function showNotification(type, message) {
    // Check if notification container exists, create if not
    let notificationContainer = document.getElementById('notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        notificationContainer.style.position = 'fixed';
        notificationContainer.style.top = '20px';
        notificationContainer.style.right = '20px';
        notificationContainer.style.zIndex = '9999';
        document.body.appendChild(notificationContainer);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'notification ' + type;
    notification.style.padding = '10px 15px';
    notification.style.marginBottom = '10px';
    notification.style.borderRadius = '4px';
    notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
    notification.style.backgroundColor = type === 'success' ? '#4CAF50' : '#F44336';
    notification.style.color = 'white';
    notification.style.transition = 'opacity 0.3s ease-in-out';
    notification.textContent = message;
    
    // Add to container
    notificationContainer.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notificationContainer.removeChild(notification);
        }, 300);
    }, 5000);
}
