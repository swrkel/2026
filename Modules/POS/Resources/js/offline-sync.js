(function () {
    'use strict';

    var dbName = 'syzygy_pos_offline_s381';
    var dbVersion = 2;
    var logBox = document.getElementById('pos-sync-console');
    var statusBox = document.getElementById('pos-online-status');

    function log(message) {
        if (!logBox) return;
        var time = new Date().toLocaleTimeString();
        logBox.textContent = '[' + time + '] ' + message + '\n' + logBox.textContent;
    }

    function setStatus() {
        if (!statusBox) return;
        statusBox.textContent = navigator.onLine ? 'Online' : 'Offline';
    }

    function csrf() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    function ensureStore(db, name, keyPath) {
        if (!db.objectStoreNames.contains(name)) {
            db.createObjectStore(name, { keyPath: keyPath || 'id' });
        }
    }

    function openDb() {
        return new Promise(function (resolve, reject) {
            var request = indexedDB.open(dbName, dbVersion);
            request.onupgradeneeded = function (event) {
                var db = event.target.result;
                if (!db.objectStoreNames.contains('queue')) {
                    var store = db.createObjectStore('queue', { keyPath: 'client_token' });
                    store.createIndex('status', 'status', { unique: false });
                    store.createIndex('created_at', 'created_at', { unique: false });
                    store.createIndex('offline_invoice_no', 'offline_invoice_no', { unique: false });
                }
                ensureStore(db, 'products', 'id');
                ensureStore(db, 'customers', 'id');
                ensureStore(db, 'settings', 'key');
                ensureStore(db, 'stock', 'id');
            };
            request.onsuccess = function () { resolve(request.result); };
            request.onerror = function () { reject(request.error); };
        });
    }

    function localInvoiceNo() {
        var date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        var device = (window.POS_DEVICE_UUID || 'WEB').toString().slice(0, 8).toUpperCase();
        return 'OFF-' + device + '-' + date + '-' + Date.now();
    }

    function putMany(storeName, rows) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(storeName, 'readwrite');
                var store = tx.objectStore(storeName);
                (rows || []).forEach(function (row) { store.put(row); });
                tx.oncomplete = function () { resolve(rows ? rows.length : 0); };
                tx.onerror = function () { reject(tx.error); };
            });
        });
    }

    function refreshEndpoint(label, url, storeName) {
        return fetch(url).then(function (r) { return r.json(); }).then(function (json) {
            if (!json.ok) { log(label + ' cache failed: ' + (json.message || 'unknown')); return 0; }
            return putMany(storeName, json.items || []).then(function (count) {
                log(label + ' cache refreshed: ' + count + ' records. Version: ' + (json.version_hash || 'n/a'));
                return count;
            });
        });
    }

    function refreshOfflineCache() {
        if (!navigator.onLine) { log('Cannot refresh cache. Browser is offline.'); return; }
        Promise.all([
            refreshEndpoint('Products', '/pos-module/offline-sync/cache/products', 'products'),
            refreshEndpoint('Customers', '/pos-module/offline-sync/cache/customers', 'customers'),
            refreshEndpoint('Stock', '/pos-module/offline-sync/cache/stock', 'stock'),
            fetch('/pos-module/offline-sync/cache/settings').then(function (r) { return r.json(); }).then(function (json) {
                return putMany('settings', [{key: 'offline_settings', value: json.settings || {}, cached_at: json.cached_at}]).then(function () { log('Settings cache refreshed.'); });
            })
        ]).then(function () { log('Offline cache refresh completed.'); }).catch(function (e) { log('Cache refresh failed: ' + e); });
    }

    function addTestItem() {
        openDb().then(function (db) {
            var token = 'OFFSALE-' + Date.now() + '-' + Math.random().toString(36).slice(2);
            var invoice = localInvoiceNo();
            var tx = db.transaction('queue', 'readwrite');
            tx.objectStore('queue').put({
                client_token: token,
                device_uuid: window.POS_DEVICE_UUID || null,
                offline_invoice_no: invoice,
                transaction_type: 'sale',
                customer_id: null,
                customer_name: 'Walk-in Customer',
                credit_sale: false,
                subtotal: 0,
                discount_amount: 0,
                tax_amount: 0,
                total_amount: 0,
                paid_amount: 0,
                payment_method: 'cash',
                payment_status: 'paid',
                payments: [{ payment_method: 'cash', amount: 0 }],
                items: [{ product_id: 0, product_name: 'Sync Test Item - replace with real offline sale payload from workspace', quantity: 1, unit_price: 0, line_total: 0 }],
                created_offline_at: new Date().toISOString(),
                status: 'pending'
            });
            tx.oncomplete = function () { log('Local offline sale queue item created: ' + invoice); };
            tx.onerror = function () { log('Failed to create local queue item.'); };
        }).catch(function (e) { log('IndexedDB error: ' + e); });
    }

    function preflight(item) {
        return fetch('/pos-module/offline-sync/preflight', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf()},
            body: JSON.stringify(item)
        }).then(function (r) { return r.json().then(function (j) { j.http_ok = r.ok; return j; }); });
    }

    function sendPending() {
        if (!navigator.onLine) { log('Cannot send. Browser is offline.'); return; }
        openDb().then(function (db) {
            var tx = db.transaction('queue', 'readonly');
            var store = tx.objectStore('queue');
            var request = store.getAll();
            request.onsuccess = function () {
                var items = request.result || [];
                if (!items.length) { log('No local pending items.'); return; }
                items.forEach(function (item) {
                    preflight(item).then(function (pf) {
                        if (!pf.ok) {
                            log('Preflight warning/conflict for ' + item.offline_invoice_no + ': ' + JSON.stringify(pf));
                            if (pf.conflicts && pf.conflicts.length) return;
                        }
                        fetch('/pos-module/offline-sync/queue', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf()},
                            body: JSON.stringify(item)
                        }).then(function (r) { return r.json(); }).then(function (json) {
                            log('Accepted by server queue for ' + item.offline_invoice_no + ': ' + JSON.stringify(json));
                            var delTx = db.transaction('queue', 'readwrite');
                            if (json.ok) delTx.objectStore('queue').delete(item.client_token);
                        }).catch(function (e) { log('Send failed: ' + e); });
                    }).catch(function (e) { log('Preflight failed: ' + e); });
                });
            };
        });
    }

    function processServerPending() {
        if (!navigator.onLine) { log('Cannot process. Browser is offline.'); return; }
        fetch('/pos-module/offline-sync/sync-now', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf()},
            body: JSON.stringify({limit: 25})
        }).then(function (r) { return r.json(); }).then(function (json) {
            log('Server processing result: ' + JSON.stringify(json));
        }).catch(function (e) { log('Server processing failed: ' + e); });
    }

    function refreshConflicts() {
        fetch('/pos-module/offline-sync/conflicts').then(function (r) { return r.json(); }).then(function (json) {
            log('Open conflicts: ' + (json.count || 0) + '. ' + JSON.stringify(json.items || []));
        }).catch(function (e) { log('Conflict refresh failed: ' + e); });
    }

    window.POSOfflineSync = {
        queueSale: function (payload) {
            return openDb().then(function (db) {
                payload.client_token = payload.client_token || ('OFFSALE-' + Date.now() + '-' + Math.random().toString(36).slice(2));
                payload.offline_invoice_no = payload.offline_invoice_no || localInvoiceNo();
                payload.transaction_type = payload.transaction_type || 'sale';
                payload.created_offline_at = payload.created_offline_at || new Date().toISOString();
                payload.status = 'pending';
                return new Promise(function (resolve, reject) {
                    var tx = db.transaction('queue', 'readwrite');
                    tx.objectStore('queue').put(payload);
                    tx.oncomplete = function () { resolve(payload); };
                    tx.onerror = function () { reject(tx.error); };
                });
            });
        },
        sendPending: sendPending,
        processServerPending: processServerPending,
        refreshOfflineCache: refreshOfflineCache,
        refreshConflicts: refreshConflicts
    };

    window.addEventListener('online', function () { setStatus(); log('Browser came online. Ready to refresh cache and send pending queue.'); });
    window.addEventListener('offline', function () { setStatus(); log('Browser went offline. POS will use local cache and queue sales locally.'); });
    setStatus();

    var testBtn = document.getElementById('pos-sync-test-btn');
    var sendBtn = document.getElementById('pos-sync-send-btn');
    var serverBtn = document.getElementById('pos-sync-server-btn');
    var cacheBtn = document.getElementById('pos-sync-cache-btn');
    var conflictBtn = document.getElementById('pos-sync-conflict-btn');
    if (testBtn) testBtn.addEventListener('click', addTestItem);
    if (sendBtn) sendBtn.addEventListener('click', sendPending);
    if (serverBtn) serverBtn.addEventListener('click', processServerPending);
    if (cacheBtn) cacheBtn.addEventListener('click', refreshOfflineCache);
    if (conflictBtn) conflictBtn.addEventListener('click', refreshConflicts);
    log('Offline cache and conflict validation S381 loaded.');
})();
