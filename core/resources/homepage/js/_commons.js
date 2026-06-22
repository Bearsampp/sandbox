/*
 *
 *  * Copyright (c) 2022-2025 Bearsampp
 *  * License: GNU General Public License version 3 or later; see LICENSE.txt
 *  * Website: https://bearsampp.com
 *  * Github: https://github.com/Bearsampp
 *
 */

/**
 * StatusFetcher - Unified utility for fetching and displaying service status
 */
class StatusFetcher {
  constructor(serviceName, fields = ['checkport', 'versions'], options = {}) {
    this.serviceName = serviceName;
    this.fields = this.normalizeFields(fields);
    this.options = Object.assign({
      interval: 10000,
      proc: serviceName
    }, options);
    this.timer = null;
  }

  normalizeFields(fields) {
    return fields.map(field => {
      if (typeof field === 'string') {
        return { data: field, selector: field };
      }
      return field;
    });
  }

  init() {
    this.fetchStatus();
    if (this.options.interval > 0) {
      this.timer = setInterval(() => this.fetchStatus(), this.options.interval);
    }
  }

  async fetchStatus() {
    const senddata = new URLSearchParams();
    senddata.append('proc', this.options.proc);
    if (typeof CSRF_TOKEN !== 'undefined') {
      senddata.append('csrf_token', CSRF_TOKEN);
    }

    let url = AJAX_URL;

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: senddata
      });

      if (!response.ok) throw new Error('Network response was not ok');
      const data = await response.json();
      
      if (this.options.customUpdater) {
        this.options.customUpdater(data);
      } else {
        this.updateDOM(data);
      }
    } catch (error) {
      console.error(`[${this.serviceName}] Fetch error:`, error);
    }
  }

  updateDOM(data) {
    if (!data) return;
    this.fields.forEach(field => {
      const selector = `.${this.serviceName}-${field.selector}`;
      const elements = document.querySelectorAll(selector);

      elements.forEach(element => {
        const contentEl = element.querySelector('.status-content') || element;
        if (data[field.data] !== undefined && data[field.data] !== null) {
          const content = data[field.data];
          if (typeof content === 'string' && content.includes('<')) {
            contentEl.innerHTML = content;
          } else {
            contentEl.innerText = content;
          }
        }
      });
    });
  }

  initOnReady() {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.init());
    } else {
      this.init();
    }
  }
}

function createStatusFetcher(serviceName, fields, options = {}) {
  const fetcher = new StatusFetcher(serviceName, fields, options);
  fetcher.initOnReady();
  return fetcher;
}
