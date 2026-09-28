// ----- CONFIG -----
window.APP_IS_OFFLINE_MODE = false;
// Default auto-sync interval (minutes)
window.ADMIN_SYNC_INTERVAL_MINUTES = window.ADMIN_SYNC_INTERVAL_MINUTES || 5;

$.ajax({
  url: "/business-location/settings/ajax",
  method: "GET",
  data: (function(){
    var loc = null;
    var el = document.getElementById('location_id') || document.getElementById('location_id_hidden');
    if (el && el.value) { loc = el.value; }
    return loc ? { location_id: loc } : {};
  })(),
  success: function (res) {
    var interval = res && res.auto_synchronization_intervals;
    if (interval && !isNaN(interval) && parseInt(interval) > 0) {
        window.ADMIN_SYNC_INTERVAL_MINUTES = parseInt(interval);
    }
    if (window.OfflineQueue && typeof OfflineQueue.startAutoSync === 'function') {
        OfflineQueue.startAutoSync(window.ADMIN_SYNC_INTERVAL_MINUTES);
    }
    if (typeof window.updateOfflineBadge === 'function') {
        window.updateOfflineBadge();
    }
  },
  error: function(){
    if (typeof window.updateOfflineBadge === 'function') {
        window.updateOfflineBadge();
    }
  }
});

// If you already have OfflineQueue in another file, skip re-defining it.
// Otherwise ensure OfflineQueue exists. For safety, minimal check:
if (!window.OfflineQueue) {
    console.warn("OfflineQueue not found. Ensure offline-queue.js is loaded before this file.");
}

// ---- IndexedDB small product cache helper (store: 'products') ----
(function () {
  const P_DB_NAME = "POS_OfflineDB";
  const P_DB_VER = 1;

  function openDB() {
    return new Promise((resolve, reject) => {
      const req = indexedDB.open(P_DB_NAME, P_DB_VER);
      req.onupgradeneeded = (ev) => {
        const db = ev.target.result;
        // Products
        if (!db.objectStoreNames.contains("products")) {
          const store = db.createObjectStore("products", { keyPath: "id" });
          store.createIndex("sku", "sku", { unique: false });
        }

        // Payment types
        if (!db.objectStoreNames.contains("payment_types")) {
          db.createObjectStore("payment_types", { keyPath: "key" });
        }

        // Accounts by payment type
        if (!db.objectStoreNames.contains("accounts_by_payment_type")) {
          db.createObjectStore("accounts_by_payment_type", { keyPath: "key" });
        }

        // Customer Details
        if (!db.objectStoreNames.contains("customer_details")) {
          db.createObjectStore("customer_details", { keyPath: "key" });
        }
      };
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
  }

  async function saveProducts(products) {
    if (!Array.isArray(products)) return;
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction('products', "readwrite");
      const store = tx.objectStore('products');
      products.forEach((p) => {
        p.id = p.variation_id || p.product_id;
        store.put(p);
      });
      tx.oncomplete = () => resolve(true);
      tx.onerror = () => reject(tx.error);
    });
  }

  async function getProductById(id) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction('products', "readonly");
      const store = tx.objectStore('products');
      const req = store.get(id);
      req.onsuccess = () => {
        resolve(req.result || null);
      };
      req.onerror = () => reject(req.error);
    });
  }

  async function getFilteredProducts({
    category_id,
    brand_id,
    location_id,
  } = {}) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction("products", "readonly");
      const store = tx.objectStore("products");
      const request = store.getAll();
      request.onsuccess = () => {
        let products = request.result || [];
        if (category_id && category_id != "all") {
          products = products.filter((p) => p.category_id == category_id);
        }
        if (brand_id && brand_id != "all") {
          products = products.filter((p) => p.brand_id == brand_id);
        }
        if (location_id && location_id != "all") {
          products = products.filter((p) => p.location_id == location_id);
        }
        resolve(products);
      };
      request.onerror = () => reject(request.error);
    });
  }

  async function getFilteredProductsByTerm({
    term = "",
    category_id = null,
    brand_id = null,
    location_id = null,
  } = {}) {
    const db = await openDB();
    term = (term || "").toLowerCase().trim();
    return new Promise((resolve, reject) => {
      const tx = db.transaction("products", "readonly");
      const store = tx.objectStore("products");
      const request = store.getAll();
      request.onsuccess = () => {
        let products = request.result || [];
        if (term.length > 0) {
          if (category_id && category_id !== "all") {
            products = products.filter((p) => p.category_id == category_id);
          }

          // filter brand
          if (brand_id && brand_id !== "all") {
            products = products.filter((p) => p.brand_id == brand_id);
          }

          // filter location
          if (location_id && location_id !== "all") {
            products = products.filter((p) => p.location_id == location_id);
          }
          console.log(products);

          products = products.filter((p) => {
            const name = (p.product_name || "").toLowerCase();
            const sku = (p.sku || "").toLowerCase();
            const sub = (p.sub_sku || "").toLowerCase();

            return (
              name.includes(term) || sku.includes(term) || sub.includes(term)
            );
          });
        }
        resolve(products);
      };
      request.onerror = () => reject(request.error);
    });
  }

  async function savePaymentTypes(data) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction("payment_types", "readwrite");
      const store = tx.objectStore("payment_types");
      for (const key in data) {
        store.put({ key, value: data[key] });
      }
      tx.oncomplete = () => resolve(true);
      tx.onerror = () => reject(tx.error);
    });
  }

  async function getPaymentTypes() {
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction("payment_types", "readonly");
      const store = tx.objectStore("payment_types");
      const req = store.getAll();
      req.onsuccess = () => {
        const obj = {};
        (req.result || []).forEach((r) => (obj[r.key] = r.value));
        resolve(obj);
      };
      req.onerror = () => reject(req.error);
    });
  }

  async function saveAccountsByPaymentType(data) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction("accounts_by_payment_type", "readwrite");
      const store = tx.objectStore("accounts_by_payment_type");
      for (const key in data) {
        store.put({ key, value: data[key] });
      }
      tx.oncomplete = () => resolve(true);
      tx.onerror = () => reject(tx.error);
    });
  }

  async function getAccountByPaymentType(key) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction("accounts_by_payment_type", "readonly");
      const store = tx.objectStore("accounts_by_payment_type");
      const req = store.get(key);
      req.onsuccess = () => {
        if (req.result) {
          resolve(req.result.value);
        } else {
          resolve(null);
        }
      };
      req.onerror = () => reject(req.error);
    });
  }

  async function saveCustomerDetails(data) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction("customer_details", "readwrite");
      const store = tx.objectStore("customer_details");
      for (const key in data) {
        store.put({ key, value: data[key] });
      }
      tx.oncomplete = () => resolve(true);
      tx.onerror = () => reject(tx.error);
    });
  }

  async function getCustomerDetailByKey(key) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction("customer_details", "readonly");
      const store = tx.objectStore("customer_details");
      const req = store.get(key);
      req.onsuccess = () => {
        if (req.result) {
          resolve(req.result.value);
        } else {
          resolve(null);
        }
      };
      req.onerror = () => reject(req.error);
    });
  }

  window.OfflineCache = {
    saveProducts,
    getProductById,
    openDB,
    getFilteredProducts,
    getFilteredProductsByTerm,
    savePaymentTypes,
    getPaymentTypes,
    saveAccountsByPaymentType,
    getAccountByPaymentType,
    saveCustomerDetails,
    getCustomerDetailByKey,
  };
})();


// ----- helper: safe JSON parse ----
function tryParseJSON(json) {
    try {
        return JSON.parse(json);
    } catch (e) {
        return null;
    }
}

// ----- fallback generators ----
function generateOfflineInvoiceNo() {
    return 'OFF-' + Date.now();
}

// ----- override $.ajax with offline-aware wrapper -----
// Keep original
if (typeof jQuery === 'undefined') {
    console.error('jQuery not found. offline-wrapper requires jQuery.');
} else {
    (function ($) {
        const originalAjax = $.ajax;

        $.ajax = function (options) {
            // Normalize opts into object
            let opts = options;
            if (typeof options === 'string') {
                opts = { url: options };
            }

            // ensure defaults
            opts.type = (opts.type || opts.method || 'GET').toUpperCase();
            opts.data = opts.data || {};
            opts.dataType = opts.dataType || undefined;

            // Use jQuery Deferred for compatibility
            const deferred = $.Deferred();

            // Helper to call success/error/callbacks consistent with jQuery ajax
            function callSuccess(response) {
                if (typeof opts.success === 'function') {
                    try { opts.success(response); } catch (e) { console.error(e); }
                }
                deferred.resolve(response);
            }
            function callError(err) {
                if (typeof opts.error === 'function') {
                    try { opts.error(err); } catch (e) { console.error(e); }
                }
                deferred.reject(err);
            }
            function callComplete(resp) {
                if (typeof opts.complete === 'function') {
                    try { opts.complete(resp); } catch (e) { console.error(e); }
                }
            }

            // If forced offline mode or navigator says offline -> route to queue/fallback
            if (window.APP_IS_OFFLINE_MODE || (typeof navigator !== 'undefined' && !navigator.onLine)) {
                // For non-GET requests, save to queue and simulate success (if desired)
                if (opts.type !== 'GET') {
                    // Save request into OfflineQueue for later sync
                    if (window.OfflineQueue && typeof OfflineQueue.saveRequest === 'function') {
                        OfflineQueue.saveRequest(opts.url, opts.type, opts.data)
                            .then(() => {
                                let fake = { success: 1, offline: true, msg: 'Saved offline. Will sync when online.' };
                                if (opts.url && (opts.url.indexOf('/sales/store') !== -1 || opts.url.indexOf('/pos') !== -1 || opts.url.indexOf('store') !== -1)) {
                                    fake.receipt = { is_enabled: true, html_content: opts.data && opts.data.receipt_html ? opts.data.receipt_html : '' };
                                }

                                callSuccess(fake);
                                callComplete(fake);
                            })
                            .catch(err => {
                                console.error('Failed to save offline request', err);
                                callError({ statusText: 'Failed to save offline' });
                                callComplete({ statusText: 'Failed to save offline' });
                            });
                    } else {
                        console.warn('OfflineQueue.saveRequest not available');
                        callError({ statusText: 'Offline and no queue available' });
                        callComplete({ statusText: 'Offline and no queue available' });
                    }

                    return deferred.promise();
                }
            }

            // OTHERWISE: online — forward to real ajax, but intercept failures to save to queue if necessary
            const jqXhr = originalAjax.call($, opts);

            // attach handlers to capture network errors
            jqXhr.fail(function (jqXHR, textStatus, errorThrown) {
                console.warn('AJAX failed (online attempt):', opts.url, textStatus, errorThrown);

                // if failure looks like network (timeout / parsererror / error) and non-GET, save to queue
                const isNetworkError = (textStatus === 'error' || textStatus === 'timeout' || textStatus === 'parsererror');
                if (isNetworkError && opts.type !== 'GET' && window.OfflineQueue && typeof OfflineQueue.saveRequest === 'function') {
                    OfflineQueue.saveRequest(opts.url, opts.type, opts.data)
                        .then(() => {
                            console.log('Saved failed request to offline queue:', opts.url);
                        })
                        .catch(e => console.error('Failed to save failed request to queue', e));
                }
            });

            // propagate success/error to original callers (they already have callbacks registered)
            jqXhr.done(function (resp) {
            });

            return jqXhr;
        };

    })(jQuery);
}

// ----- UI: Add toggle handler if #toggle_offline exists -----
(function () {
    $(document).ready(function () {
        // create toggle button if not present
        if ($('#toggle_offline').length === 0) {
            // optional: comment out if you don't want auto-insert
            $('body').prepend('<button id="toggle_offline" style="position:fixed;right:10px;top:60px;z-index:9999;" class="btn btn-warning">Offline: OFF</button>');
        }

    if (!document.getElementById('offline_status_badge')) {
      $('body').prepend('<div id="offline_status_badge" style="position:fixed;right:10px;top:30px;z-index:9999;background:#5cb85c;color:#fff;padding:6px 10px;border-radius:4px;font-size:12px;">Online | Auto-sync: -- min</div>');
    }

    function setOfflineModeField() {
      var el = document.getElementById('offline_mode');
      if (el) {
        el.value = (window.APP_IS_OFFLINE_MODE || (typeof navigator !== 'undefined' && !navigator.onLine)) ? 1 : 0;
      }
    }

    window.updateOfflineBadge = function () {
      var modeText = 'Mode: ' + (window.APP_IS_OFFLINE_MODE ? 'Offline' : 'Online');
      var interval = window.ADMIN_SYNC_INTERVAL_MINUTES;
      var syncText = 'Auto-sync: ' + (interval ? interval : '--') + ' min';
      var badge = document.getElementById('offline_status_badge');
      if (!badge) return;
      badge.innerText = modeText + ' | ' + syncText;
      badge.style.background = window.APP_IS_OFFLINE_MODE ? '#d9534f' : '#5cb85c';
      setOfflineModeField();
    };

        $(document).on('click', '#toggle_offline', function () {
            APP_IS_OFFLINE_MODE = !APP_IS_OFFLINE_MODE;
            $(this).text('Offline: ' + (APP_IS_OFFLINE_MODE ? 'ON' : 'OFF'));
            toastr.info('Offline Mode: ' + (APP_IS_OFFLINE_MODE ? 'ON' : 'OFF'));
      if (typeof window.updateOfflineBadge === 'function') {
        window.updateOfflineBadge();
      }
      setOfflineModeField();
        });

        // Listen to navigator online/offline and show toast (optional)
        window.addEventListener('offline', function () {
            console.warn('Browser event: offline');
            toastr.warning('You are offline. App will save data locally.');
          if (typeof window.updateOfflineBadge === 'function') {
            window.updateOfflineBadge();
          }
          setOfflineModeField();
        });

        window.addEventListener('online', function () {
            console.info('Browser event: online');
            toastr.success('You are online. Syncing queued data...');
            if (window.OfflineQueue && typeof OfflineQueue.syncAllRequests === 'function') {
                OfflineQueue.syncAllRequests();
            }
          if (typeof window.updateOfflineBadge === 'function') {
            window.updateOfflineBadge();
          }
          setOfflineModeField();
        });

        if (typeof window.updateOfflineBadge === 'function') {
          window.updateOfflineBadge();
        }
        setOfflineModeField();
    });
})();

// ----- Utility: preload products JSON into local cache (call when online) -----
// Usage: call preloadProducts('/sells/pos/get-product-suggestion-json') once when page is ready and online
window.preloadProductsToCache = async function (jsonUrl, params = {}) {
    if (!jsonUrl) {
        console.error('Provide jsonUrl to preloadProductsToCache');
        return;
    }
    if (!navigator.onLine) {
        console.warn('Not online, skipping preloadProductsToCache');
        return;
    }
    const query = new URLSearchParams(params).toString();
    const finalUrl = jsonUrl + "?" + query;
    try {
      const resp = await fetch(finalUrl, {
        method: "GET",
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
        },
      });
      const response = await resp.json();
      const products = response.products || [];
      if (Array.isArray(products)) {
        await window.OfflineCache.saveProducts(products);
      }
    } catch (e) {
      console.error("preloadProductsToCache error", e);
    }
};

window.preloadPaymentData = async function (jsonUrl, params = {}) {
    if (!jsonUrl) {
        console.error('Provide jsonUrl to preloadPaymentData');
        return;
    }
    if (!navigator.onLine) {
        console.warn('Not online, skipping preloadPaymentData');
        return;
    }
    const query = new URLSearchParams(params).toString();
    const finalUrl = jsonUrl + "?" + query;
    try {
      const resp = await fetch(finalUrl, {
        method: "GET",
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
        },
      });
      const data = await resp.json();
      if (data.payment_types) {
          await window.OfflineCache.savePaymentTypes(data.payment_types);
      }

      if (data.accounts_by_payment_type) {
          await window.OfflineCache.saveAccountsByPaymentType(data.accounts_by_payment_type);
      }
    } catch (e) {
      console.error("preloadProductsToCache error", e);
    }
};

window.preloadCustomers = async function (jsonUrl, params = {}) {
    if (!jsonUrl) {
        console.error('Provide jsonUrl to preloadCustomers');
        return;
    }
    if (!navigator.onLine) {
        console.warn('Not online, skipping preloadCustomers');
        return;
    }
    try {
      const resp = await fetch(jsonUrl, {
        method: "GET",
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
        },
      });
      const data = await resp.json();

      if (data.all_contacts) {
          await window.OfflineCache.saveCustomerDetails(data.all_contacts);
      }
    } catch (e) {
      console.error("preloadProductsToCache error", e);
    }
};
