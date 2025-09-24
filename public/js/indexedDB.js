const DB_NAME = 'pos-db';
let _dbPromise;

function dbReady() {
    if (_dbPromise) return _dbPromise;
    _dbPromise = new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, 1);
        req.onupgradeneeded = (e) => {
            const db = req.result;
            if (!db.objectStoreNames.contains('products')) {
                const prods = db.createObjectStore('products', {
                    keyPath: 'id'
                });

                prods.createIndex('by_name', 'name', { unique: false });
                prods.createIndex('by_category', 'category_id', {
                    unique: false
                });
            }
            if (!db.objectStoreNames.contains('categories')) {
                const cat = db.createObjectStore('categories', {
                    keyPath: 'id'
                }); // 1 item unique per produk
                cat.createIndex('by_name', 'name', { unique: false });
            }

            if (!db.objectStoreNames.contains('modifiers')) {
                const mod = db.createObjectStore('modifiers', {
                    keyPath: 'id'
                });
                mod.createIndex('by_name', 'name', { unique: false });
            }

            if (!db.objectStoreNames.contains('discounts')) {
                const disc = db.createObjectStore('discounts', {
                    keyPath: 'id'
                });
                disc.createIndex('by_name', 'name', { unique: false });
            }

            if (!db.objectStoreNames.contains('sales_types')) {
                const st = db.createObjectStore('sales_types', {
                    keyPath: 'id'
                });
                st.createIndex('by_name', 'name', { unique: false });
            }

            if (!db.objectStoreNames.contains('pilihans')) {
                const pil = db.createObjectStore('pilihans', {
                    keyPath: 'id'
                });
                pil.createIndex('by_name', 'name', { unique: false });
            }

            if (!db.objectStoreNames.contains('pending_transactions')) {
                db.createObjectStore('pending_transactions', {
                    keyPath: 'client_uuid'
                });
            }

            if (!db.objectStoreNames.contains('user')) {
                db.createObjectStore('user', {
                    keyPath: 'id'
                });
            }

            if(!db.objectStoreNames.contains('list_payment')){
                db.createObjectStore('list_payment', {
                    keyPath: 'id'
                });
            }

            if(!db.objectStoreNames.contains('pending_transactions')){
                db.createObjectStore('pending_transactions', {
                    keyPath:'client_uuid'
                });
            }

        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
    return _dbPromise;
}
async function idbPut(store, val) {
    const db = await dbReady();
    return new Promise((res, rej) => {
        const tx = db.transaction(store, 'readwrite');
        tx.objectStore(store).put(val);
        tx.oncomplete = () => res();
        tx.onerror = () => rej(tx.error);
    });
}
async function idbDel(store, key) {
    const db = await dbReady();
    return new Promise((res, rej) => {
        const tx = db.transaction(store, 'readwrite');
        tx.objectStore(store).delete(key);
        tx.oncomplete = () => res();
        tx.onerror = () => rej(tx.error);
    });
}
async function idbGet(store, key) {
    const db = await dbReady();
    return new Promise((res, rej) => {
        const tx = db.transaction(store, 'readonly');
        const r = tx.objectStore(store).get(key);
        r.onsuccess = () => res(r.result);
        r.onerror = () => rej(r.error);
    });
}
async function idbAll(store) {
    const db = await dbReady();
    return new Promise((res, rej) => {
        const tx = db.transaction(store, 'readonly');
        const r = tx.objectStore(store).getAll();
        r.onsuccess = () => res(r.result || []);
        r.onerror = () => rej(r.error);
    });
}
async function idbCount(store) {
    const db = await dbReady();
    return new Promise((res, rej) => {
        const tx = db.transaction(store, 'readonly');
        const r = tx.objectStore(store).count();
        r.onsuccess = () => res(r.result || 0);
        r.onerror = () => rej(r.error);
    });
}

async function idbClear(store) {
    const db = await dbReady();
    return new Promise((res, rej) => {
        const tx = db.transaction(store, 'readwrite');
        const r = tx.objectStore(store).clear();
        r.onsuccess = () => res(r.result);
        r.onerror = () => rej(r.error);
    });
}
