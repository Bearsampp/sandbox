/*
 *
 *  * Copyright (c) 2022-2025 Bearsampp
 *  * License: GNU General Public License version 3 or later; see LICENSE.txt
 *  * Website: https://bearsampp.com
 *  * Github: https://github.com/Bearsampp
 *
 */

const AJAX_URL = "/1fd5bfc5c72323f1d019208088a6de21/ajax.php"

/**
 * StatusFetcher - Unified utility for fetching and displaying service status
 *
 * This class eliminates code duplication across service-specific JavaScript files
 * by providing a common interface for AJAX status fetching and DOM updates.
 */
class StatusFetcher {
  /**
   * Create a StatusFetcher instance
   *
   * @param {string} serviceName - The service name (e.g., 'mysql', 'apache', 'php')
   * @param {Array<string|Object>} fields - Array of field names or field mapping objects
   *   - String format: 'checkport' (uses same name for data key and selector)
   *   - Object format: { data: 'versions', selector: 'version-list' }
   * @param {Object} options - Optional configuration
   * @param {Function} options.errorHandler - Custom error handler for specific services
   * @param {Function} options.responseValidator - Custom response validator
   * @param {Function} options.customUpdater - Custom DOM updater function
   */
  constructor(serviceName, fields = ['checkport', 'versions'], options = {}) {
    this.serviceName = serviceName;
    this.fields = this.normalizeFields(fields);
    this.options = options;
  }

  /**
   * Normalize field definitions to consistent format
   *
   * @param {Array<string|Object>} fields - Field definitions
   * @returns {Array<Object>} Normalized field objects
   */
  normalizeFields(fields) {
    return fields.map(field => {
      if (typeof field === 'string') {
        return { data: field, selector: field };
      }
      return field;
    });
  }

  /**
   * Fetch status from the server
   *
   * @returns {Promise<void>}
   */
  async fetchStatus() {
    const senddata = new URLSearchParams();
    senddata.append('proc', this.serviceName);

    // Add CSRF token using the helper function
    if (typeof addCsrfToken === 'function') {
      addCsrfToken(senddata);
    }

    try {
      const url = AJAX_URL + (AJAX_URL.includes('?') ? '&' : '?') + 't=' + new Date().getTime();
      
      const fetchOptions = {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: senddata,
        cache: 'no-cache'
      };

      // Add credentials for HTTPS if needed
      // Use 'include' for HTTPS to be more permissive with session cookies across same-site if needed, 
      // but 'same-origin' is generally better. Re-evaluating.
      if (window.location.protocol === 'https:') {
          fetchOptions.credentials = 'same-origin';
      }

      console.log(`[${this.serviceName}] Fetching status from: ${url}`);
      let response;
      try {
          response = await fetch(url, fetchOptions);
      } catch (fetchError) {
          console.error(`[${this.serviceName}] Fetch failed:`, fetchError);
          // Visual feedback for network error
          this.showErrorFeedback();
          return;
      }
      console.log(`[${this.serviceName}] Fetch response:`, response.status, response.statusText);

      if (!response.ok) {
        console.error(`[${this.serviceName}] Error receiving from ajax.php: ${response.status} ${response.statusText}`);
        this.showErrorFeedback();
        return;
      }

      if (response.status === 403) {
        console.error(`[${this.serviceName}] CSRF validation failed (403 Forbidden)`);
        this.showErrorFeedback();
        return;
      }

      const responseText = await response.text();
      // console.log(`Response for ${this.serviceName}:`, responseText);

      let data;
      try {
        // Handle potential MySQL errors before parsing
        if (this.serviceName === 'mysql' && responseText.includes("Uncaught mysqli_sql_exception")) {
            console.warn(`[${this.serviceName}] MySQL error detected in response`);
            return;
        }

        if (responseText.trim() === "") {
            console.error(`[${this.serviceName}] Empty response`);
            return;
        }

        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error(`[${this.serviceName}] Failed to parse response:`, parseError);
        console.log(`[${this.serviceName}] Raw response:`, responseText);
        // Add visual feedback for errors
        this.showErrorFeedback();
        return;
      }

      // Use custom updater if provided, otherwise use default
      if (this.options.customUpdater) {
        this.options.customUpdater(data);
      } else {
        this.updateDOM(data);
      }
    } catch (error) {
      console.error(`Error during fetch for ${this.serviceName}:`, error);
      if (this.options.errorHandler) {
        this.options.errorHandler(error);
      }
    }
  }

  /**
   * Provide visual feedback when an update fails
   */
  showErrorFeedback() {
    const selector = `.summary-${this.serviceName}`;
    const element = document.querySelector(selector) || document.getElementById(this.serviceName);
    if (element) {
      const contentEl = element.querySelector('.status-content') || element;
      const loaders = contentEl.querySelectorAll('.loader');
      if (loaders.length > 0) {
        loaders.forEach(loader => {
          loader.classList.remove('fa-spin');
          loader.classList.add('text-danger');
          loader.title = 'Error loading status';
          // If it's an image loader, we might want to replace it or style it
          const img = loader.querySelector('img');
          if (img) {
            img.style.filter = 'hue-rotate(300deg) saturate(5)'; // Make it look "reddish"
          }
        });
      } else {
        // If no loader and content is empty or only whitespace, show error icon
        if (!contentEl.innerText.trim()) {
           contentEl.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i></span>';
        }
      }
    }
  }

  /**
   * Update DOM elements with fetched data
   *
   * @param {Object} data - The parsed JSON data from the server
   */
  updateDOM(data) {
    this.fields.forEach(field => {
      const selector = `.${this.serviceName}-${field.selector}`;
      const elements = document.querySelectorAll(selector);

      if (elements.length > 0) {
        elements.forEach(element => {
          const contentEl = element.querySelector('.status-content') || element;
          
          if (data[field.data] !== undefined && data[field.data] !== null) {
            const loader = contentEl.querySelector('.loader');
            if (loader) {
              // Replace loader specifically to preserve other content (like labels/icons)
              loader.outerHTML = data[field.data];
            } else {
              // If no loader, replace content of target container
              contentEl.innerHTML = data[field.data];
            }
          }
        });
      } else {
        console.warn(`[${this.serviceName}] Element(s) not found: ${selector}`);
      }
    });
  }

  /**
   * Initialize status fetching when DOM is ready
   *
   * @param {string} elementId - The element ID to check for (defaults to serviceName)
   * @returns {void}
   */
  initOnReady(elementId = null) {
    const checkId = elementId || this.serviceName;

    const run = () => {
      if (document.getElementById(checkId) || document.querySelector(`.summary-${this.serviceName}`)) {
        this.fetchStatus();
      }
    };

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", run);
    } else {
      run();
    }
  }
}

/**
 * Helper function to create and initialize a StatusFetcher
 *
 * @param {string} serviceName - The service name
 * @param {Array<string|Object>} fields - Array of field names or mapping objects
 * @param {Object} options - Optional configuration
 * @returns {StatusFetcher} The created StatusFetcher instance
 */
function createStatusFetcher(serviceName, fields, options = {}) {
  const fetcher = new StatusFetcher(serviceName, fields, options);
  fetcher.initOnReady();
  return fetcher;
}
