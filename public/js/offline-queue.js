/* offline-queue.js
 *
 * Responsibilities:
 *  - Provide OfflineQueue object with saveRequest, getPendingRequests, syncAllRequests, startAutoSync
 *  - Store queued requests in IndexedDB (store: 'offline_requests')
 *  - Auto-sync when online
 */

(function () {
    const DB_NAME = 'OfflineQueueDB';
    const DB_VER = 1;
    const STORE = 'offline_requests';

    const originalAjax = $.ajax.bind($);

    function openQueueDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, DB_VER);
            req.onupgradeneeded = (ev) => {
                const db = ev.target.result;
                if (!db.objectStoreNames.contains(STORE)) {
                    db.createObjectStore(STORE, { keyPath: 'id', autoIncrement: true });
                }
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    async function saveRequest(url, method, data) {
        const db = await openQueueDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readwrite');
            const store = tx.objectStore(STORE);
            const req = store.add({
                url,
                method,
                data,
                timestamp: Date.now(),
                synced: false
            });
            req.onsuccess = () => resolve(true);
            req.onerror = () => reject(req.error);
        });
    }

    async function getPendingRequests() {
        const db = await openQueueDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readonly');
            const store = tx.objectStore(STORE);
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result.filter(r => !r.synced));
            req.onerror = () => reject(req.error);
        });
    }

    async function markAsSynced(id) {
        const db = await openQueueDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readwrite');
            const store = tx.objectStore(STORE);
            const getReq = store.get(id);
            getReq.onsuccess = () => {
                const record = getReq.result;
                if (record) {
                    record.synced = true;
                    const putReq = store.put(record);
                    putReq.onsuccess = () => resolve(true);
                    putReq.onerror = () => reject(putReq.error);
                } else {
                    resolve(false);
                }
            };
            getReq.onerror = () => reject(getReq.error);
        });
    }

    async function syncAllRequests() {
        const pending = await getPendingRequests();
        for (const req of pending) {
            try {
                await originalAjax({
                    url: req.url,
                    type: req.method,
                    data: req.data,
                    dataType: 'json'
                });
                await markAsSynced(req.id);
                console.log('Synced offline request:', req.url);
            } catch (e) {
                console.warn('Failed to sync offline request:', req.url, e);
            }
        }
    }

    let autoSyncInterval = null;

    function startAutoSync(minutes = 1) {
        if (autoSyncInterval) clearInterval(autoSyncInterval);
        autoSyncInterval = setInterval(() => {
            if (navigator.onLine) {
                syncAllRequests();
            }
        }, minutes * 60 * 1000);
        console.log('OfflineQueue auto-sync started, interval (min):', minutes);
    }

    // Expose globally
    window.OfflineQueue = {
        saveRequest,
        getPendingRequests,
        syncAllRequests,
        startAutoSync
    };
})();
