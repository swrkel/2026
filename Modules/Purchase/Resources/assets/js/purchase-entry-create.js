(function () {
    'use strict';

    const config = window.PurchaseEntryConfig || {};
    const form = document.getElementById('purchase_entry_form');
    if (!form) return;

    const unloadCard = document.getElementById('purchase_unload_card');
    const unloadRows = document.getElementById('purchase_unload_rows');
    const saveBar = document.getElementById('purchase_save_bar');
    const saveActions = document.getElementById('purchase_save_actions');
    const initialTankAllocations = config.initialTanks && typeof config.initialTanks === 'object'
        ? config.initialTanks
        : {};

    const $ = (selector, root) => (root || document).querySelector(selector);
    const $$ = (selector, root) => Array.from((root || document).querySelectorAll(selector));
    const precision = Math.max(0, Number(config.currencyPrecision || 2));
    // IS8040: match ProductsNew List Product price display (4 decimals) without
    // changing the business currency precision used by totals/payments.
    const productPricePrecision = Math.max(0, Math.min(6, Number(config.productPricePrecision == null ? 4 : config.productPricePrecision)));
    // CH1 IS2115: only Purchase Entry enables this. The visible product-cost
    // fields remain at the requested four decimals, while hidden values retain
    // the full precision used to calculate and persist the purchase total.
    const preserveCalculationPrecision = Boolean(config.preserveCalculationPrecision);
    const qtyPrecision = Math.max(0, Number(config.quantityPrecision || 3));
    const currencySymbol = String(config.currencySymbol || '');
    let lineIndex = 0;
    let paymentIndex = 0;
    let supplierTimer = null;
    let supplierRequest = 0;
    let supplierAbortController = null;
    let supplierLoading = false;
    let supplierActiveTerm = '';
    let supplierNextPage = 2;
    let supplierHasMore = Boolean(config.initialSuppliersMore);
    const supplierPageSize = Math.max(10, Math.min(100, Number(config.supplierPageSize || 50)));
    const supplierItems = new Map();
    let purchaseOrderRequest = 0;
    let purchaseOrderDetailRequest = 0;
    let loadedPurchaseOrderId = '';
    let initialPurchaseOrderApplied = false;
    const pendingPurchaseOrders = new Map();
    let productTimer = null;
    let referenceTimer = null;
    let lastProductResults = [];
    let submitting = false;
    const unloadRequests = {};
    const quantityColumnBaseWidth = 72;
    const quantityColumnMaxWidth = 260;
    let quantityColumnResizeFrame = 0;

    function inputTextWidth(input) {
        const value = String(input.value == null || input.value === '' ? '0' : input.value);
        const styles = window.getComputedStyle(input);
        const canvas = inputTextWidth.canvas || (inputTextWidth.canvas = document.createElement('canvas'));
        const context = canvas.getContext('2d');
        if (!context) return input.scrollWidth || quantityColumnBaseWidth;

        context.font = [
            styles.fontStyle,
            styles.fontVariant,
            styles.fontWeight,
            styles.fontSize,
            styles.fontFamily,
        ].filter(Boolean).join(' ');

        const horizontalPadding = (Number.parseFloat(styles.paddingLeft) || 0) + (Number.parseFloat(styles.paddingRight) || 0);
        const horizontalBorders = (Number.parseFloat(styles.borderLeftWidth) || 0) + (Number.parseFloat(styles.borderRightWidth) || 0);
        const numberControlSpace = input.type === 'number' ? 30 : 0;
        return Math.ceil(context.measureText(value).width + horizontalPadding + horizontalBorders + numberControlSpace + 6);
    }

    function updateQuantityColumnWidth() {
        const table = document.getElementById('purchase_entry_lines_table');
        if (!table) return;

        let requiredWidth = quantityColumnBaseWidth;
        $$('.line-quantity', table).forEach(function (input) {
            requiredWidth = Math.max(requiredWidth, inputTextWidth(input));
        });

        const width = Math.min(quantityColumnMaxWidth, Math.ceil(requiredWidth));
        table.style.setProperty('--purchase-qty-width', width + 'px');
        table.style.setProperty('--purchase-qty-extra-width', Math.max(0, width - quantityColumnBaseWidth) + 'px');
    }

    function scheduleQuantityColumnResize() {
        if (quantityColumnResizeFrame) window.cancelAnimationFrame(quantityColumnResizeFrame);
        quantityColumnResizeFrame = window.requestAnimationFrame(function () {
            quantityColumnResizeFrame = 0;
            updateQuantityColumnWidth();
        });
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function numeric(value) {
        const parsed = Number(String(value == null ? '' : value).replace(/,/g, '').trim());
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function money(value) {
        const number = numeric(value);
        return (currencySymbol ? currencySymbol + ' ' : '') + number.toLocaleString(undefined, {
            minimumFractionDigits: precision,
            maximumFractionDigits: precision,
        });
    }

    function priceInput(value) {
        return numeric(value).toFixed(productPricePrecision);
    }

    function currencyPrecisionValue(value) {
        return numeric(value).toFixed(precision);
    }

    function qty(value) {
        return numeric(value).toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: qtyPrecision,
        });
    }

    function dateTimeNow() {
        const date = new Date();
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        return date.toISOString().slice(0, 16);
    }

    function routeWithId(template, id) {
        return String(template || '').replace('__ID__', encodeURIComponent(id));
    }

    async function fetchJson(url, options) {
        const settings = Object.assign({
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }, options || {});
        settings.headers = Object.assign({
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }, (options && options.headers) || {});

        const response = await fetch(url, settings);
        const text = await response.text();
        let data = {};
        try {
            data = text ? JSON.parse(text) : {};
        } catch (error) {
            const plain = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 700);
            throw new Error(plain || ('HTTP ' + response.status));
        }

        if (!response.ok) {
            const validation = data.errors ? Object.values(data.errors).flat().join(' ') : '';
            throw new Error(data.message || data.msg || validation || ('HTTP ' + response.status));
        }
        return data;
    }

    function showAlert(message, type) {
        const alert = $('#purchase_entry_alert');
        if (!alert) return;
        alert.hidden = false;
        alert.className = 'purchase-entry-alert ' + (type === 'success' ? 'is-success' : 'is-error');
        alert.textContent = String(message || 'Unknown error.');
        alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function hideAlert() {
        const alert = $('#purchase_entry_alert');
        if (!alert) return;
        alert.hidden = true;
        alert.textContent = '';
    }

    function openModal(modal) {
        if (!modal) return;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.hidden = true;
        if (!$$('.purchase-modal:not([hidden])').length) document.body.style.overflow = '';
    }

    function renderResults(container, results, clickHandler) {
        if (!container) return;
        container.innerHTML = '';
        if (!results.length) {
            container.innerHTML = '<div class="purchase-search-result"><small>No matching records found.</small></div>';
            container.hidden = false;
            return;
        }
        results.forEach(function (item) {
            const row = document.createElement('div');
            row.className = 'purchase-search-result';
            const details = [];
            if (item.sku) details.push(item.sku);
            if (item.current_stock != null) details.push('Stock: ' + qty(item.current_stock));
            row.innerHTML = '<strong>' + escapeHtml(item.text || item.name || '') + '</strong>' +
                (details.length ? '<small>' + escapeHtml(details.join(' · ')) + '</small>' : '');
            row.addEventListener('click', function () { clickHandler(item); });
            container.appendChild(row);
        });
        container.hidden = false;
    }

    function closeSearchResults() {
        ['supplier_results', 'product_results'].forEach(function (id) {
            const node = document.getElementById(id);
            if (node) node.hidden = true;
        });
    }

    // Supplier search, selection and quick creation. The first page is
    // embedded in the form response so opening the dropdown never waits for a
    // second HTTP request. Later pages and typed searches remain server-side.
    const supplierSearch = $('#supplier_search');
    const supplierId = $('#contact_id');
    const supplierResults = $('#supplier_results');
    const purchaseOrderSelect = $('#linked_purchase_order_id');
    const purchaseOrderNo = $('#order_no');
    const purchaseOrderStatus = $('#purchase_order_status');
    const initialSupplierResults = Array.isArray(config.initialSuppliers) ? config.initialSuppliers : [];

    function setPurchaseOrderStatus(message, state) {
        if (!purchaseOrderStatus) return;
        purchaseOrderStatus.textContent = String(message || '');
        purchaseOrderStatus.className = 'purchase-help' + (state ? ' is-' + state : '');
    }

    function clearPurchaseOrderProductsAndTotals() {
        const body = document.getElementById('purchase_lines_body');
        if (body) body.innerHTML = '';
        lineIndex = 0;
        if (unloadRows) unloadRows.innerHTML = '';
        ensureEmptyProductRow();

        const defaults = {
            discount_type: 'fixed',
            discount_amount: '0',
            tax_id: '',
            shipping_details: '',
            shipping_charges: '0',
            price_adjustment: '0',
            additional_notes: '',
            exchange_rate: '1',
        };
        Object.keys(defaults).forEach(function (id) {
            const field = document.getElementById(id);
            if (field) field.value = defaults[id];
        });
        const vat = document.querySelector('[name="is_vat"]');
        if (vat) vat.checked = false;

        reindexLines();
        refreshUnloadCardVisibility();
        recalculateTotals();
        updateSaveActionVisibility();
    }

    function resetPurchaseOrderSelector(message, clearLoadedData) {
        if (!purchaseOrderSelect) return;
        purchaseOrderRequest++;
        purchaseOrderDetailRequest++;
        pendingPurchaseOrders.clear();

        if (clearLoadedData && loadedPurchaseOrderId) {
            clearPurchaseOrderProductsAndTotals();
        }
        loadedPurchaseOrderId = '';
        if (purchaseOrderNo) purchaseOrderNo.value = '';

        purchaseOrderSelect.disabled = true;
        purchaseOrderSelect.innerHTML = '<option value="">Select a supplier first</option>';
        setPurchaseOrderStatus(message || 'Pending purchase orders will appear after selecting the supplier.');
        updateSaveActionVisibility();
    }

    function purchaseOrderOptionText(order) {
        const parts = [order.invoice_no || ('PO #' + order.id)];
        if (order.ref_no) parts.push('Ref: ' + order.ref_no);
        if (order.invoice_date) parts.push('Expected: ' + String(order.invoice_date).slice(0, 10));
        if (order.location_name) parts.push(order.location_name);
        if (order.store_name) parts.push(order.store_name);
        if (order.final_total != null) parts.push(money(order.final_total));
        return parts.join(' · ');
    }

    async function loadPendingPurchaseOrders(selectedSupplierId) {
        if (!purchaseOrderSelect || config.isEdit || !selectedSupplierId || !config.routes.purchase_orders) return;
        const requestId = ++purchaseOrderRequest;
        purchaseOrderDetailRequest++;
        pendingPurchaseOrders.clear();
        loadedPurchaseOrderId = '';
        if (purchaseOrderNo) purchaseOrderNo.value = '';

        purchaseOrderSelect.disabled = true;
        purchaseOrderSelect.innerHTML = '<option value="">Loading pending purchase orders...</option>';
        setPurchaseOrderStatus('Checking pending purchase orders for the selected supplier...');

        try {
            const url = config.routes.purchase_orders + '?supplier_id=' + encodeURIComponent(selectedSupplierId);
            const data = await fetchJson(url);
            if (requestId !== purchaseOrderRequest || String(supplierId.value) !== String(selectedSupplierId)) return;

            const orders = Array.isArray(data.results) ? data.results : [];
            orders.forEach(function (order) {
                if (order && order.id != null) pendingPurchaseOrders.set(String(order.id), order);
            });

            if (!orders.length) {
                purchaseOrderSelect.innerHTML = '<option value="">No pending purchase orders — proceed without one</option>';
                purchaseOrderSelect.disabled = true;
                setPurchaseOrderStatus('No pending purchase orders are available. You can continue with a direct purchase.', 'success');
                updateSaveActionVisibility();
                return;
            }

            purchaseOrderSelect.innerHTML = '<option value="">Proceed without a purchase order</option>' + orders.map(function (order) {
                return '<option value="' + escapeHtml(order.id) + '">' + escapeHtml(purchaseOrderOptionText(order)) + '</option>';
            }).join('');
            purchaseOrderSelect.disabled = false;
            setPurchaseOrderStatus(orders.length + (orders.length === 1
                ? ' pending purchase order is available. Select it to load the ordered products.'
                : ' pending purchase orders are available. Select one to load its ordered products.'), 'success');

            const initialId = String(config.initialPurchaseOrderId || '');
            if (!initialPurchaseOrderApplied && initialId && pendingPurchaseOrders.has(initialId)) {
                initialPurchaseOrderApplied = true;
                purchaseOrderSelect.value = initialId;
                await loadSelectedPurchaseOrder(initialId, true);
            }
        } catch (error) {
            if (requestId !== purchaseOrderRequest) return;
            purchaseOrderSelect.innerHTML = '<option value="">Unable to load purchase orders — proceed without one</option>';
            purchaseOrderSelect.disabled = true;
            setPurchaseOrderStatus('Purchase orders could not be loaded. You can still continue without selecting one.', 'error');
            console.error('Purchase order lookup failed', error);
        } finally {
            updateSaveActionVisibility();
        }
    }

    async function setPurchaseOrderLocation(order) {
        if (!order || !locationSelect || !storeSelect) return;
        const orderLocationId = String(order.location_id || '');
        const orderStoreId = String(order.store_id || '');

        if (orderLocationId && String(locationSelect.value || '') !== orderLocationId) {
            const locationOption = Array.from(locationSelect.options).some(function (option) {
                return String(option.value) === orderLocationId;
            });
            if (!locationOption) throw new Error('The purchase order business location is not available to this user.');
            locationSelect.value = orderLocationId;
            await loadStores();
        }

        if (orderStoreId) {
            let storeOption = Array.from(storeSelect.options).find(function (option) {
                return String(option.value) === orderStoreId;
            });
            if (!storeOption && order.store_name) {
                storeOption = document.createElement('option');
                storeOption.value = orderStoreId;
                storeOption.textContent = order.store_name;
                storeSelect.appendChild(storeOption);
            }
            if (!storeOption) throw new Error('The purchase order store is not available for the selected location.');
            storeSelect.value = orderStoreId;
        }
        setQuickProductScope();
    }

    function applyPurchaseOrderHeader(order) {
        const values = {
            pay_term_number: order.pay_term_number == null ? '' : order.pay_term_number,
            pay_term_type: order.pay_term_type || 'days',
            exchange_rate: order.exchange_rate || 1,
            discount_type: order.discount_type || 'fixed',
            discount_amount: order.discount_amount || 0,
            tax_id: order.tax_id || '',
            shipping_details: order.shipping_details || '',
            shipping_charges: order.shipping_charges || 0,
            price_adjustment: order.price_adjustment || 0,
            additional_notes: order.additional_notes || '',
        };
        Object.keys(values).forEach(function (id) {
            const field = document.getElementById(id);
            if (field) field.value = values[id];
        });
        const vat = document.querySelector('[name="is_vat"]');
        if (vat) vat.checked = Boolean(order.is_vat);
    }

    async function loadSelectedPurchaseOrder(orderId, skipConfirmation) {
        if (!purchaseOrderSelect || !orderId || !supplierId.value || !config.routes.purchase_order) return;
        const detailRequestId = ++purchaseOrderDetailRequest;
        const existingRows = $$('.purchase-line-row', document.getElementById('purchase_lines_body'));

        if (!skipConfirmation && existingRows.length && String(loadedPurchaseOrderId) !== String(orderId)) {
            const proceed = window.confirm('Selecting this purchase order will replace the current product rows. Continue?');
            if (!proceed) {
                purchaseOrderSelect.value = loadedPurchaseOrderId || '';
                return;
            }
        }

        purchaseOrderSelect.disabled = true;
        setPurchaseOrderStatus('Loading purchase order details and products...');

        try {
            const url = routeWithId(config.routes.purchase_order, orderId) +
                '?supplier_id=' + encodeURIComponent(supplierId.value);
            const data = await fetchJson(url);
            if (detailRequestId !== purchaseOrderDetailRequest) return;
            const order = data.purchase_order || {};
            if (!order.id || !Array.isArray(order.lines) || !order.lines.length) {
                throw new Error('The selected purchase order has no product rows available to receive.');
            }

            await setPurchaseOrderLocation(order);
            if (detailRequestId !== purchaseOrderDetailRequest) return;

            clearPurchaseOrderProductsAndTotals();
            applyPurchaseOrderHeader(order);
            order.lines.forEach(function (line) { addProductLine(line, false); });

            loadedPurchaseOrderId = String(order.id);
            purchaseOrderSelect.value = loadedPurchaseOrderId;
            if (purchaseOrderNo) purchaseOrderNo.value = order.invoice_no || '';
            recalculateTotals();
            refreshUnloadCardVisibility();
            updateSaveActionVisibility();
            setPurchaseOrderStatus('Purchase order ' + (order.invoice_no || order.id) +
                ' loaded with ' + order.lines.length + (order.lines.length === 1 ? ' product.' : ' products.'), 'success');
        } catch (error) {
            if (detailRequestId !== purchaseOrderDetailRequest) return;
            loadedPurchaseOrderId = '';
            if (purchaseOrderNo) purchaseOrderNo.value = '';
            purchaseOrderSelect.value = '';
            setPurchaseOrderStatus(error.message || 'The purchase order could not be loaded.', 'error');
            showAlert(error.message, 'error');
        } finally {
            if (detailRequestId === purchaseOrderDetailRequest && pendingPurchaseOrders.size) {
                purchaseOrderSelect.disabled = false;
            }
            updateSaveActionVisibility();
        }
    }

    purchaseOrderSelect?.addEventListener('change', function () {
        const orderId = String(purchaseOrderSelect.value || '');
        if (!orderId) {
            purchaseOrderDetailRequest++;
            if (loadedPurchaseOrderId) clearPurchaseOrderProductsAndTotals();
            loadedPurchaseOrderId = '';
            if (purchaseOrderNo) purchaseOrderNo.value = '';
            setPurchaseOrderStatus(pendingPurchaseOrders.size
                ? 'Proceeding without a purchase order.'
                : 'No pending purchase orders are available.');
            updateSaveActionVisibility();
            return;
        }
        loadSelectedPurchaseOrder(orderId, false);
    });

    function supplierText(item) {
        return [item && item.name, item && item.text, item && item.contact_id, item && item.mobile, item && item.email]
            .filter(Boolean)
            .join(' ')
            .toLocaleLowerCase();
    }

    function rememberSupplierResults(results) {
        (results || []).forEach(function (item) {
            if (!item || item.id == null) return;
            supplierItems.set(String(item.id), item);
        });
    }

    function cachedSupplierMatches(term) {
        const normalized = String(term || '').trim().toLocaleLowerCase();
        const rows = Array.from(supplierItems.values());
        if (!normalized) return rows.slice(0, supplierPageSize);
        return rows.filter(function (item) {
            return supplierText(item).includes(normalized);
        }).slice(0, supplierPageSize);
    }

    function removeSupplierFooter() {
        supplierResults?.querySelectorAll('.purchase-search-more').forEach(function (node) { node.remove(); });
    }

    function updateSupplierFooter(isLoading) {
        if (!supplierResults) return;
        removeSupplierFooter();
        if (!isLoading && !supplierHasMore) return;

        const footer = document.createElement('div');
        footer.className = 'purchase-search-result purchase-search-more';
        footer.innerHTML = '<small>' + (isLoading ? 'Loading more suppliers...' : 'Scroll to load more suppliers') + '</small>';
        supplierResults.appendChild(footer);
    }

    function renderSupplierResults(results, append) {
        if (!supplierResults) return;
        removeSupplierFooter();
        if (!append) supplierResults.innerHTML = '';

        const existing = new Set(Array.from(supplierResults.querySelectorAll('[data-supplier-id]')).map(function (node) {
            return String(node.dataset.supplierId || '');
        }));

        (results || []).forEach(function (item) {
            const id = String(item.id || '');
            if (!id || existing.has(id)) return;
            existing.add(id);

            const row = document.createElement('div');
            row.className = 'purchase-search-result';
            row.dataset.supplierId = id;
            row.innerHTML = '<strong>' + escapeHtml(item.text || item.name || '') + '</strong>';
            row.addEventListener('click', function () { setSupplier(item); });
            supplierResults.appendChild(row);
        });

        if (!supplierResults.querySelector('[data-supplier-id]')) {
            supplierResults.innerHTML = '<div class="purchase-search-result"><small>No matching suppliers found.</small></div>';
        }

        supplierResults.hidden = false;
        updateSupplierFooter(false);
    }

    function showCachedSuppliers(term) {
        supplierActiveTerm = String(term || '').trim();
        supplierNextPage = 2;
        supplierHasMore = supplierActiveTerm === '' ? Boolean(config.initialSuppliersMore) : false;
        renderSupplierResults(cachedSupplierMatches(supplierActiveTerm), false);
    }

    async function loadSupplierResults(term, page, append) {
        if (!supplierResults || supplierLoading && append) return;

        const normalizedTerm = String(term || '').trim();
        const requestedPage = Math.max(1, Number(page || 1));
        const shouldAppend = Boolean(append && requestedPage > 1);
        const requestId = ++supplierRequest;

        if (!shouldAppend && supplierAbortController) supplierAbortController.abort();
        if (!shouldAppend) supplierAbortController = typeof AbortController === 'function' ? new AbortController() : null;

        supplierLoading = true;
        supplierActiveTerm = normalizedTerm;
        updateSupplierFooter(true);

        const query = new URLSearchParams({
            term: normalizedTerm,
            page: String(requestedPage),
            per_page: String(supplierPageSize),
        });

        try {
            const data = await fetchJson(config.routes.supplier_search + '?' + query.toString(), {
                signal: supplierAbortController ? supplierAbortController.signal : undefined,
            });
            if (requestId !== supplierRequest || normalizedTerm !== supplierActiveTerm) return;

            const results = Array.isArray(data.results) ? data.results : [];
            rememberSupplierResults(results);
            supplierHasMore = Boolean(data.pagination && data.pagination.more);
            supplierNextPage = requestedPage + 1;
            renderSupplierResults(results, shouldAppend);
        } catch (error) {
            if (error && error.name === 'AbortError') return;
            if (requestId !== supplierRequest) return;
            removeSupplierFooter();
            if (!supplierResults.querySelector('[data-supplier-id]')) {
                supplierResults.innerHTML = '<div class="purchase-search-result"><small>Unable to load suppliers. Please type again.</small></div>';
            }
            supplierResults.hidden = false;
            console.error('Supplier lookup failed', error);
        } finally {
            if (requestId === supplierRequest) {
                supplierLoading = false;
                updateSupplierFooter(false);
            }
        }
    }

    rememberSupplierResults(initialSupplierResults);

    if (supplierSearch) {
        supplierSearch.addEventListener('input', function () {
            supplierId.value = '';
            $('#selected_supplier_text').textContent = 'Select a supplier from the filtered results.';
            resetPurchaseOrderSelector('Select a supplier to check its pending purchase orders.', true);
            updateSaveActionVisibility();
            clearTimeout(supplierTimer);

            const term = supplierSearch.value.trim();
            showCachedSuppliers(term);
            supplierTimer = setTimeout(function () {
                loadSupplierResults(term, 1, false);
            }, 90);
        });

        supplierSearch.addEventListener('focus', function () {
            clearTimeout(supplierTimer);
            const term = supplierId.value ? '' : supplierSearch.value.trim();
            showCachedSuppliers(term);

            // Old cached forms or pages opened before the first supplier page
            // was embedded still recover automatically through the endpoint.
            if (supplierItems.size === 0 || term !== '') {
                supplierTimer = setTimeout(function () {
                    loadSupplierResults(term, 1, false);
                }, term === '' ? 0 : 90);
            }
        });
    }

    supplierResults?.addEventListener('scroll', function () {
        if (supplierLoading || !supplierHasMore || supplierResults.hidden) return;
        const nearBottom = supplierResults.scrollTop + supplierResults.clientHeight >= supplierResults.scrollHeight - 48;
        if (nearBottom) loadSupplierResults(supplierActiveTerm, supplierNextPage, true);
    });

    async function setSupplier(item) {
        rememberSupplierResults([item]);
        const supplierChanged = supplierId.value && String(supplierId.value) !== String(item.id);
        if (supplierChanged) {
            resetPurchaseOrderSelector('Checking pending purchase orders for the new supplier...', true);
        }
        supplierId.value = item.id;
        supplierSearch.value = item.name || item.text || '';
        supplierResults.hidden = true;
        const label = $('#selected_supplier_text');
        label.textContent = item.text || item.name || '';
        if (item.pay_term_number != null) $('#pay_term_number').value = item.pay_term_number;
        if (item.pay_term_type) $('#pay_term_type').value = item.pay_term_type;
        checkReference();
        updateSaveActionVisibility();
        loadPendingPurchaseOrders(item.id);

        try {
            const data = await fetchJson(routeWithId(config.routes.supplier_terms, item.id));
            const supplier = data.supplier || {};
            if (supplier.pay_term_number != null) $('#pay_term_number').value = supplier.pay_term_number;
            if (supplier.pay_term_type) $('#pay_term_type').value = supplier.pay_term_type;
            const parts = [supplier.name || item.name || item.text || ''];
            if (supplier.mobile) parts.push('Mobile: ' + supplier.mobile);
            parts.push('Current outstanding: ' + money(supplier.outstanding || 0));
            label.textContent = parts.join(' · ');
        } catch (error) {
            console.error('Supplier detail could not be loaded', error);
        }
    }

    const supplierModal = $('#quick_supplier_modal');
    const quickSupplierForm = $('#quick_supplier_form');
    $('#open_quick_supplier')?.addEventListener('click', function () {
        openModal(supplierModal);
        const nameInput = $('[name="name"]', quickSupplierForm);
        nameInput.value = supplierSearch.value.trim();
        setTimeout(function () { nameInput.focus(); }, 20);
    });
    $$('[data-close-supplier-modal]').forEach(function (button) {
        button.addEventListener('click', function () { closeModal(supplierModal); });
    });
    quickSupplierForm?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const submit = $('button[type="submit"]', quickSupplierForm);
        submit.disabled = true;
        try {
            const data = await fetchJson(config.routes.supplier_store, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': config.csrf },
                body: new FormData(quickSupplierForm),
            });
            const createdSupplier = Object.assign({ text: data.supplier.name }, data.supplier);
            rememberSupplierResults([createdSupplier]);
            await setSupplier(createdSupplier);
            quickSupplierForm.reset();
            closeModal(supplierModal);
            showAlert('Supplier added successfully.', 'success');
        } catch (error) {
            showAlert(error.message, 'error');
        } finally {
            submit.disabled = false;
        }
    });

    function clearPurchaseOrderWhenScopeChanges(scope) {
        if (!loadedPurchaseOrderId) return;
        const order = pendingPurchaseOrders.get(String(loadedPurchaseOrderId));
        if (!order) return;

        const locationMismatch = scope === 'location' && String(order.location_id || '') !== String(locationSelect.value || '');
        const storeMismatch = scope === 'store' && String(order.store_id || '') !== String(storeSelect.value || '');
        if (!locationMismatch && !storeMismatch) return;

        clearPurchaseOrderProductsAndTotals();
        loadedPurchaseOrderId = '';
        purchaseOrderDetailRequest++;
        if (purchaseOrderSelect) purchaseOrderSelect.value = '';
        if (purchaseOrderNo) purchaseOrderNo.value = '';
        setPurchaseOrderStatus('The purchase order selection was cleared because its business location or store was changed.', 'error');
    }

    // Location, store and account scoping.
    const locationSelect = $('#location_id');
    const storeSelect = $('#store_id');
    locationSelect?.addEventListener('change', async function () {
        clearPurchaseOrderWhenScopeChanges('location');
        await loadStores();
        await refreshLineStocks();
        refreshPaymentMethodsForLocation();
        refreshPaymentAccounts();
        setQuickProductScope();
        await reloadUnloadRows();
        updateSaveActionVisibility();
    });
    storeSelect?.addEventListener('change', async function () {
        clearPurchaseOrderWhenScopeChanges('store');
        await refreshLineStocks();
        setQuickProductScope();
        updateSaveActionVisibility();
    });

    async function loadStores() {
        const locationId = locationSelect.value;
        storeSelect.innerHTML = '<option value="">Loading...</option>';
        if (!locationId) {
            storeSelect.innerHTML = '<option value="">Please select</option>';
            return;
        }
        try {
            const data = await fetchJson(config.routes.stores + '?location_id=' + encodeURIComponent(locationId));
            const results = data.results || [];
            storeSelect.innerHTML = '<option value="">Please select</option>' + results.map(function (item) {
                return '<option value="' + escapeHtml(item.id) + '">' + escapeHtml(item.text) + '</option>';
            }).join('');
            if (results.length === 1) storeSelect.value = results[0].id;
        } catch (error) {
            storeSelect.innerHTML = '<option value="">Unable to load stores</option>';
            showAlert(error.message, 'error');
        }
    }

    async function refreshLineStocks() {
        const locationId = locationSelect.value;
        const storeId = storeSelect.value;
        if (!locationId) return;
        await Promise.all($$('.purchase-line-row', $('#purchase_lines_body')).map(async function (row) {
            try {
                const url = routeWithId(config.routes.product, row.dataset.variationId) +
                    '?location_id=' + encodeURIComponent(locationId) + '&store_id=' + encodeURIComponent(storeId || '');
                const data = await fetchJson(url);
                const product = data.product || {};
                row.dataset.baseStock = String(product.current_stock || 0);
                updateDisplayedStock(row);
            } catch (error) {
                console.error(error);
            }
        }));
    }

    function setQuickProductScope() {
        const loc = $('#quick_product_location_id');
        const store = $('#quick_product_store_id');
        if (loc) loc.value = locationSelect.value || '';
        if (store) store.value = storeSelect.value || '';
    }

    // Duplicate supplier invoice/reference validation.
    $('#ref_no')?.addEventListener('input', function () {
        clearTimeout(referenceTimer);
        const status = $('#reference_status');
        if (status) {
            status.textContent = this.value.trim() ? 'Checking reference number...' : '';
            status.className = this.value.trim() ? 'purchase-help is-pending' : 'purchase-help';
        }
        updateSaveActionVisibility();
        referenceTimer = setTimeout(checkReference, 250);
    });

    async function checkReference() {
        const status = $('#reference_status');
        const ref = $('#ref_no').value.trim();
        if (!supplierId.value || !ref) {
            status.textContent = '';
            status.className = 'purchase-help';
            updateSaveActionVisibility();
            return false;
        }

        status.textContent = 'Checking reference number...';
        status.className = 'purchase-help is-pending';
        updateSaveActionVisibility();

        try {
            let referenceUrl = config.routes.reference_check + '?supplier_id=' + encodeURIComponent(supplierId.value) + '&ref_no=' + encodeURIComponent(ref);
            if (config.referenceExcludeId) referenceUrl += '&exclude_id=' + encodeURIComponent(config.referenceExcludeId);
            const data = await fetchJson(referenceUrl);
            status.textContent = data.exists
                ? 'This reference number already exists for the selected supplier.'
                : 'Reference number is available.';
            status.className = 'purchase-help ' + (data.exists ? 'is-error' : 'is-success');
            updateSaveActionVisibility();
            return Boolean(data.exists);
        } catch (error) {
            // Do not leave the form permanently blocked by a lookup/network
            // failure. The server performs the same duplicate-reference check
            // during Save and remains authoritative.
            status.textContent = '';
            status.className = 'purchase-help';
            updateSaveActionVisibility();
            return false;
        }
    }

    // Product search and quick creation.
    const productSearch = $('#purchase_product_search');
    const productResults = $('#product_results');

    productSearch?.addEventListener('input', function () {
        clearTimeout(productTimer);
        const term = productSearch.value.trim();
        if (!term) {
            lastProductResults = [];
            productResults.hidden = true;
            return;
        }
        if (!locationSelect.value || !storeSelect.value) {
            productResults.innerHTML = '<div class="purchase-search-result"><small>Select a business location and store first.</small></div>';
            productResults.hidden = false;
            return;
        }
        productTimer = setTimeout(async function () {
            try {
                const url = config.routes.products + '?term=' + encodeURIComponent(term) +
                    '&location_id=' + encodeURIComponent(locationSelect.value) +
                    '&store_id=' + encodeURIComponent(storeSelect.value);
                const data = await fetchJson(url);
                lastProductResults = Array.isArray(data.results) ? data.results : [];
                renderResults(productResults, lastProductResults, selectProductSearchResult);
            } catch (error) {
                lastProductResults = [];
                productResults.innerHTML = '<div class="purchase-search-result"><small>Unable to load products. ' + escapeHtml(error.message) + '</small></div>';
                productResults.hidden = false;
                showAlert(error.message, 'error');
            }
        }, 160);
    });
    productSearch?.addEventListener('focus', function () {
        if (productSearch.value.trim()) productSearch.dispatchEvent(new Event('input'));
    });
    productSearch?.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        if (lastProductResults.length === 1) selectProductSearchResult(lastProductResults[0]);
    });

    /**
     * IS2317: never silently ignore a selected search result.
     *
     * The standalone search endpoint normally returns the complete product
     * payload, but some older tenant deployments/cached routes can return the
     * conventional autocomplete shape `{id, text}`.  In that case hydrate the
     * selected variation through this module's own product endpoint before
     * building the purchase row.  This keeps the normal path unchanged while
     * making selection backward-compatible instead of doing nothing because
     * `variation_id` was absent.
     */
    async function selectProductSearchResult(result) {
        if (!result) return;

        try {
            let product = result;
            const variationId = Number(product.variation_id || product.id || 0);
            if (!variationId) {
                throw new Error('The selected product does not have a valid variation.');
            }

            const needsHydration = !product.variation_id || !product.product_id || !product.product_name;
            if (needsHydration) {
                if (!config.routes.product) {
                    throw new Error('The selected product details could not be loaded.');
                }
                const detailUrl = routeWithId(config.routes.product, variationId) +
                    '?location_id=' + encodeURIComponent(locationSelect.value) +
                    '&store_id=' + encodeURIComponent(storeSelect.value || '');
                const detail = await fetchJson(detailUrl);
                product = detail.product || product;
            }

            // Support the conventional autocomplete id as a harmless fallback
            // even when a proxy/cache strips the duplicate variation_id field.
            if (!product.variation_id) product.variation_id = variationId;
            addProductLine(product);
        } catch (error) {
            showAlert(error.message || 'The selected product could not be added.', 'error');
        }
    }

    const productModal = $('#quick_product_modal');
    const quickProductForm = $('#quick_product_form');
    $('#open_quick_product')?.addEventListener('click', function () {
        if (!locationSelect.value || !storeSelect.value) {
            showAlert('Select a business location and store before adding a product.', 'error');
            locationSelect.focus();
            return;
        }
        setQuickProductScope();
        openModal(productModal);
        const nameInput = $('[name="name"]', quickProductForm);
        nameInput.value = productSearch.value.trim();
        setTimeout(function () { nameInput.focus(); }, 20);
    });
    $$('[data-close-product-modal]').forEach(function (button) {
        button.addEventListener('click', function () { closeModal(productModal); });
    });
    quickProductForm?.addEventListener('submit', async function (event) {
        event.preventDefault();
        setQuickProductScope();
        const submit = $('button[type="submit"]', quickProductForm);
        submit.disabled = true;
        try {
            const data = await fetchJson(config.routes.product_store, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': config.csrf },
                body: new FormData(quickProductForm),
            });
            addProductLine(data.product);
            quickProductForm.reset();
            setQuickProductScope();
            closeModal(productModal);
            showAlert('Product added successfully.', 'success');
        } catch (error) {
            showAlert(error.message, 'error');
        } finally {
            submit.disabled = false;
        }
    });

    function taxOptions(selectedId) {
        let html = '<option value="" data-rate="0">None</option>';
        (config.taxes || []).forEach(function (tax) {
            const selected = String(tax.id) === String(selectedId || '') ? ' selected' : '';
            html += '<option value="' + escapeHtml(tax.id) + '" data-rate="' + escapeHtml(tax.amount) + '"' + selected + '>' +
                escapeHtml(tax.name) + ' (' + escapeHtml(tax.amount) + '%)</option>';
        });
        return html;
    }

    function unitOptions(product, selectedId) {
        const configured = Array.isArray(product.units) ? product.units.filter(function (unit) {
            return Number(unit && unit.id) > 0;
        }) : [];
        const baseUnitId = Number(product.unit_id || 0);
        const options = configured.length
            ? configured
            : (baseUnitId > 0
                ? [{ id: baseUnitId, name: product.unit_name || 'Unit', multiplier: 1, allow_decimal: product.allow_decimal !== false, is_base: true }]
                : []);

        // Some legacy products were created without a unit_id. Do not submit 0
        // as sub_unit_id because the server correctly treats real IDs as >= 1.
        // An empty value means "use the product base/default unit" and is
        // normalised server-side before validation.
        if (!options.length) {
            return '<option value="" data-multiplier="1" data-decimal="1" selected>' +
                escapeHtml(product.unit_name || 'Unit') + '</option>';
        }

        return options.map(function (unit) {
            const selected = String(unit.id) === String(selectedId || product.unit_id) ? ' selected' : '';
            return '<option value="' + escapeHtml(unit.id) + '" data-multiplier="' + escapeHtml(unit.multiplier || 1) +
                '" data-decimal="' + (unit.allow_decimal ? '1' : '0') + '"' + selected + '>' + escapeHtml(unit.name) + '</option>';
        }).join('');
    }

    function addProductLine(product, shouldFocus) {
        if (!product || !product.variation_id) return;
        const focusRow = shouldFocus !== false;
        const existing = $('.purchase-line-row[data-variation-id="' + String(product.variation_id).replace(/"/g, '') + '"]', $('#purchase_lines_body'));
        if (existing) {
            if (focusRow) {
                const input = $('.line-quantity', existing);
                input.value = numeric(input.value) + 1;
                recalculateRow(existing, input);
                scheduleQuantityColumnResize();
                syncUnloadQuantity(existing.dataset.productId);
                updateSaveActionVisibility();
                input.focus();
            }
            productResults.hidden = true;
            productSearch.value = '';
            lastProductResults = [];
            return;
        }

        const body = $('#purchase_lines_body');
        $('.purchase-empty-row', body)?.remove();
        const row = document.createElement('tr');
        const selectedMultiplier = Math.max(0.000001, numeric(product.unit_multiplier || 1));
        row.className = 'purchase-line-row';
        row.dataset.variationId = product.variation_id;
        row.dataset.productId = product.product_id;
        row.dataset.fuelCategory = 'pending';
        row.dataset.baseStock = String(product.current_stock || 0);
        row.dataset.unitMultiplier = String(selectedMultiplier);
        row.innerHTML = buildProductRow(lineIndex++, product);
        const quantityInput = $('.line-quantity', row);
        if (quantityInput && product.quantity == null) {
            // New product lines start with the system default quantity. Clear that
            // default the first time the user enters the Qty field so typing a
            // quantity replaces it instead of appending to it.
            quantityInput.dataset.clearDefaultOnFocus = '1';
        }
        body.appendChild(row);
        scheduleQuantityColumnResize();
        productSearch.value = '';
        productResults.hidden = true;
        lastProductResults = [];
        reindexLines();
        updateDisplayedStock(row);
        if (preserveCalculationPrecision) row.dataset.precisionInitialising = '1';
        recalculateRow(row, product.quantity == null ? $('.line-unit-cost', row) : $('.line-inc-tax', row));
        if (preserveCalculationPrecision) delete row.dataset.precisionInitialising;
        loadUnloadTankRow(product);
        updateSaveActionVisibility();
        if (focusRow) $('.line-quantity', row).focus();
    }

    function buildProductRow(index, product) {
        const p = 'purchases[' + index + ']';
        const selectedUnitId = product.selected_unit_id || product.unit_id;
        const selectedMultiplier = Math.max(0.000001, numeric(product.unit_multiplier || 1));
        const enteredQuantity = product.quantity == null ? 1 : product.quantity;
        const enteredFreeQty = product.free_qty == null ? 0 : product.free_qty;
        const enteredCost = product.purchase_price == null ? 0 : product.purchase_price;
        const enteredIncTax = product.purchase_price_inc_tax == null ? enteredCost : product.purchase_price_inc_tax;
        const discountType = product.discount_type === 'fixed' ? 'fixed' : 'percentage';
        const discountValue = product.discount_value == null ? 0 : product.discount_value;
        let html = '';
        html += '<td class="line-number purchase-cell-no"></td>';
        html += '<td class="purchase-cell-product"><div class="product-name">' + escapeHtml(product.product_name) + '</div><div class="variation-name">' + escapeHtml(product.variation_name || '') + '</div>' +
            (product.sku ? '<div class="product-sku-under-name">SKU: ' + escapeHtml(product.sku) + '</div>' : '') +
            hidden(p + '[product_id]', product.product_id) + hidden(p + '[variation_id]', product.variation_id) +
            hidden(p + '[product_variation_id]', product.product_variation_id) + hidden(p + '[product_unit_id]', product.unit_id) +
            hidden(p + '[unit_multiplier]', selectedMultiplier, 'line-unit-multiplier') + hidden(p + '[purchase_price]', 0, 'line-purchase-price') +
            hidden(p + '[item_tax]', 0, 'line-item-tax') + hidden(p + '[discount_percent]', 0, 'line-discount-percent') +
            hidden(p + '[discount_amount]', 0, 'line-discount-amount') +
            (preserveCalculationPrecision
                ? hidden(p + '[pp_without_discount_precise]', numeric(enteredCost).toFixed(6), 'line-unit-cost-precise') +
                  hidden(p + '[purchase_price_inc_tax_precise]', numeric(enteredIncTax).toFixed(6), 'line-inc-tax-precise')
                : '') + '</td>';
        html += '<td class="purchase-cell-stock"><span class="stock-value">' + escapeHtml(qty(product.current_stock)) + '</span></td>';
        html += inputCell(p + '[quantity]', enteredQuantity, 'line-quantity purchase-number', 'number', product.allow_decimal === false ? '1' : '0.000001', '0', 'purchase-cell-qty');
        html += '<td class="purchase-cell-unit"><select class="form-control line-unit" name="' + p + '[sub_unit_id]">' + unitOptions(product, selectedUnitId) + '</select></td>';
        if (config.enableFreeQty) html += inputCell(p + '[free_qty]', enteredFreeQty, 'line-free-qty purchase-number', 'number', product.allow_decimal === false ? '1' : '0.000001', '0', 'purchase-cell-free-qty');
        html += inputCell(p + '[pp_without_discount]', priceInput(enteredCost), 'line-unit-cost purchase-number', 'number', '0.000001', '0', 'purchase-cell-unit-cost');
        html += '<td class="purchase-cell-discount-type"><select class="form-control line-discount-type" name="' + p + '[discount_type]"><option value="percentage"' + (discountType === 'percentage' ? ' selected' : '') + '>Percentage</option><option value="fixed"' + (discountType === 'fixed' ? ' selected' : '') + '>Fixed</option></select></td>';
        html += inputCell(p + '[discount_value]', discountValue, 'line-discount-value purchase-number', 'number', '0.000001', '0', 'purchase-cell-discount');
        html += '<td class="purchase-cell-tax"><select class="form-control line-tax" name="' + p + '[purchase_line_tax_id]">' + taxOptions(product.tax_id) + '</select></td>';
        html += inputCell(p + '[purchase_price_inc_tax]', priceInput(enteredIncTax), 'line-inc-tax purchase-number', 'number', '0.000001', '0', 'purchase-cell-cost-inc-tax');
        html += '<td class="purchase-cell-line-total"><span class="line-total-value">' + money(0) + '</span></td>';
        if (config.enableSellingPrice) {
            html += inputCell(p + '[profit_percent]', product.profit_percent || 0, 'line-profit purchase-number', 'number', '0.000001', '', 'purchase-cell-profit');
            // IS2112: the selling price is the product master selling price. A purchase
            // may change purchase cost/tax/profit, but it must never recalculate or
            // overwrite the existing selling price. readonly keeps the value submitted
            // with the line while preventing accidental edits in Add/Edit Purchase.
            html += '<td class="purchase-cell-selling-price"><input type="number" class="form-control line-selling-price purchase-number" name="' +
                escapeHtml(p + '[default_sell_price]') + '" value="' + escapeHtml(priceInput(product.selling_price || 0)) +
                '" step="0.000001" min="0" readonly aria-readonly="true"></td>';
        }
        if (config.enableLotNumber) html += inputCell(p + '[lot_number]', product.lot_number || '', 'line-lot', 'text', '', '', 'purchase-cell-lot');
        if (config.enableProductExpiry) {
            html += inputCell(p + '[mfg_date]', product.mfg_date || '', 'line-mfg-date', 'date', '', '', 'purchase-cell-mfg-date');
            html += inputCell(p + '[exp_date]', product.exp_date || '', 'line-exp-date', 'date', '', '', 'purchase-cell-expiry-date');
        }
        html += '<td class="text-center purchase-cell-action"><button type="button" class="remove-line" title="Remove product" aria-label="Remove product"><i class="fa fa-times-circle"></i></button></td>';
        return html;
    }

    function hidden(name, value, className) {
        return '<input type="hidden" name="' + escapeHtml(name) + '" value="' + escapeHtml(value) + '" class="' + escapeHtml(className || '') + '">';
    }

    function inputCell(name, value, className, type, step, min, cellClass) {
        return '<td class="' + escapeHtml(cellClass || '') + '"><input type="' + type + '" class="form-control ' + className + '" name="' + escapeHtml(name) + '" value="' + escapeHtml(value) + '"' +
            (step ? ' step="' + step + '"' : '') + (min !== '' ? ' min="' + min + '"' : '') + '></td>';
    }

    function productRows(productId) {
        return $$('.purchase-line-row', document.getElementById('purchase_lines_body')).filter(function (row) {
            return String(row.dataset.productId || '') === String(productId || '');
        });
    }

    function aggregateFuelQuantity(productId) {
        return productRows(productId).reduce(function (total, row) {
            return total + Math.max(0, numeric($('.line-quantity', row)?.value)) * selectedUnit(row).multiplier;
        }, 0);
    }

    function refreshUnloadCardVisibility() {
        if (!unloadCard || !unloadRows) return;
        const count = $$('.purchase-unload-row', unloadRows).filter(function (row) {
            return !row.classList.contains('purchase-unload-loading') && !row.classList.contains('purchase-unload-error');
        }).length;
        const hasContent = unloadRows.children.length > 0;
        unloadCard.hidden = !hasContent;
        unloadCard.setAttribute('aria-hidden', hasContent ? 'false' : 'true');
        const counter = $('#purchase_unload_count');
        if (counter) counter.textContent = count + (count === 1 ? ' fuel product' : ' fuel products');
    }

    function applyInitialTankAllocations(productId, row) {
        const productAllocations = initialTankAllocations[productId] || initialTankAllocations[String(productId)] || {};
        $$('.tank-qty', row).forEach(function (input) {
            const match = String(input.name || '').match(/\[(\d+)\]\[qty\]$/);
            if (!match) return;
            const value = productAllocations[match[1]] ?? productAllocations[Number(match[1])];
            if (value != null && numeric(value) > 0) input.value = value;
        });
    }

    function syncUnloadQuantity(productId) {
        if (!unloadRows || !productId) return;
        const row = $('.purchase-unload-row[data-product-id="' + String(productId).replace(/"/g, '') + '"]', unloadRows);
        if (!row || row.classList.contains('purchase-unload-loading') || row.classList.contains('purchase-unload-error')) return;
        const received = aggregateFuelQuantity(productId);
        const input = $('.unload-received-qty', row);
        if (input) input.value = currencyPrecisionValue(received);
        updateUnloadMatch(row);
    }

    function updateUnloadMatch(row) {
        const badge = $('.purchase-unload-match', row);
        if (!badge) return true;
        const received = Math.max(0, numeric($('.unload-received-qty', row)?.value));
        const allocated = $$('.tank-qty', row).reduce(function (sum, input) {
            return sum + Math.max(0, numeric(input.value));
        }, 0);
        const hasTanks = row.dataset.hasTanks === '1';
        const receivedStatus = $('#status')?.value === 'received';

        if (!receivedStatus) {
            badge.className = 'purchase-unload-match is-pending';
            badge.textContent = 'Allocation required when Received';
            return true;
        }
        if (!hasTanks) {
            badge.className = 'purchase-unload-match is-mismatch';
            badge.textContent = 'Tank setup required';
            return false;
        }
        if (Math.abs(received - allocated) <= 0.00001 && received > 0) {
            badge.className = 'purchase-unload-match is-match';
            badge.textContent = 'Matched: ' + qty(allocated);
            return true;
        }
        badge.className = 'purchase-unload-match is-mismatch';
        badge.textContent = 'Allocated ' + qty(allocated) + ' of ' + qty(received);
        return false;
    }

    function unloadAllocationsReady() {
        if ($('#status')?.value !== 'received') return true;
        const fuelRows = $$('.purchase-line-row', document.getElementById('purchase_lines_body')).filter(function (row) {
            return row.dataset.fuelCategory === '1';
        });
        return fuelRows.every(function (lineRow) {
            const unloadRow = unloadRows
                ? $('.purchase-unload-row[data-product-id="' + String(lineRow.dataset.productId).replace(/"/g, '') + '"]', unloadRows)
                : null;
            return Boolean(unloadRow) && updateUnloadMatch(unloadRow);
        });
    }

    function paymentMethodsReady() {
        // Pending/Ordered purchases intentionally carry no actual payment rows;
        // the full value remains supplier due until the purchase is received.
        if ($('#status')?.value !== 'received') return true;

        const rows = $$('.purchase-payment-row', document.getElementById('purchase_payment_rows') || document);
        if (!rows.length) return false;

        return rows.every(function (row) {
            const method = normalisePaymentMethodKey($('.payment-method', row)?.value);
            if (!method) return false;

            const amount = Math.max(0, numeric($('.payment-amount', row)?.value));
            const account = $('.payment-account', row);
            if (amount > 0 && method !== 'credit_purchase' && (!account || !account.value)) {
                return false;
            }

            return true;
        });
    }

    function purchaseDetailsComplete() {
        if (config.isEdit) return true;

        const rows = $$('.purchase-line-row', document.getElementById('purchase_lines_body'));
        const reference = $('#ref_no');
        const referenceStatus = $('#reference_status');

        // IS2304: Add Purchase action buttons remain hidden until the requested
        // entry checkpoints are genuinely ready: Supplier, Supplier Invoice /
        // Reference No., Unload Tanks (when required), and Payment Methods.
        if (!supplierId?.value || !supplierSearch?.value.trim()) return false;
        if (!reference || !reference.value.trim()) return false;
        if (!locationSelect?.value || !storeSelect?.value || !$('#status')?.value) return false;
        if (rows.length === 0) return false;

        /*
         * Do not use form.checkValidity() to decide whether the Save bar exists.
         * That API also fails for step/pattern/range mismatches on optional or
         * product-specific controls. In several businesses a perfectly complete
         * purchase therefore had its buttons hidden merely because one product's
         * legacy unit/price metadata produced a browser validity mismatch.
         *
         * Visibility is based only on genuinely missing required values. Full
         * browser validity is still enforced by form.reportValidity() on submit,
         * so this does not weaken save validation.
         */
        const missingRequiredValue = $$('[required]', form).some(function (element) {
            if (element.disabled || element.type === 'hidden') return false;
            return Boolean(element.validity && element.validity.valueMissing);
        });
        if (missingRequiredValue) return false;

        if (referenceStatus?.classList.contains('is-error') || referenceStatus?.classList.contains('is-pending')) return false;
        // A tank lookup in progress is a temporary blocker. A failed background
        // lookup must never hide the buttons forever; the server performs the
        // authoritative tank validation again when Save is clicked.
        if (rows.some(function (row) { return row.dataset.fuelCategory === 'pending'; })) return false;
        if (rows.some(function (row) {
            const quantity = numeric($('.line-quantity', row)?.value);
            const cost = $('.line-unit-cost', row)?.value;
            const incTax = $('.line-inc-tax', row)?.value;
            // Unit is optional for legacy products that have no configured
            // product.unit_id. A configured unit is still selected by default.
            return quantity <= 0 || cost === '' || incTax === '' || numeric(cost) < 0 || numeric(incTax) < 0;
        })) return false;
        if (!unloadAllocationsReady()) return false;
        return paymentMethodsReady();
    }

    function updateSaveActionVisibility() {
        if (!saveActions) return;

        // IS2304 applies to Add Purchase Entry. Edit remains unchanged so older
        // purchases (including records with no historical supplier reference)
        // can still be corrected without introducing a regression.
        const ready = config.isEdit ? true : purchaseDetailsComplete();

        if (saveBar) {
            saveBar.hidden = !ready;
            saveBar.setAttribute('aria-hidden', ready ? 'false' : 'true');
        }

        saveActions.hidden = !ready;
        saveActions.setAttribute('aria-hidden', ready ? 'false' : 'true');
    }

    function fieldLabel(element, fallback) {
        if (!element) return fallback || 'Required field';
        const field = element.closest('.purchase-field');
        const label = field ? $('label', field) : null;
        const text = label ? String(label.textContent || '').replace(/\s*\*\s*$/, '').trim() : '';
        return text || fallback || element.getAttribute('name') || element.id || 'Required field';
    }

    function clearMandatoryHighlights() {
        $$('.purchase-required-missing', form).forEach(function (element) {
            element.classList.remove('purchase-required-missing');
            element.removeAttribute('aria-invalid');
        });
        $$('.purchase-field.has-required-error', form).forEach(function (field) {
            field.classList.remove('has-required-error');
        });
    }

    function markMandatoryField(element) {
        if (!element) return;
        element.classList.add('purchase-required-missing');
        element.setAttribute('aria-invalid', 'true');
        const field = element.closest('.purchase-field');
        if (field) field.classList.add('has-required-error');
    }

    function mandatoryFieldIssues() {
        clearMandatoryHighlights();

        const labels = [];
        const seen = new Set();
        let firstElement = null;

        function addIssue(label, element) {
            const cleanLabel = String(label || 'Required field').trim();
            if (!seen.has(cleanLabel)) {
                seen.add(cleanLabel);
                labels.push(cleanLabel);
            }
            if (element) {
                markMandatoryField(element);
                if (!firstElement && !element.disabled && element.offsetParent !== null) {
                    firstElement = element;
                }
            }
        }

        // Required header fields: invoice/location/store/status/supplier/date etc.
        $$('[required]', form).forEach(function (element) {
            if (element.disabled || element.type === 'hidden') return;
            if (element.validity && element.validity.valueMissing) {
                addIssue(fieldLabel(element), element);
            }
        });

        // Supplier search text alone is not enough: a supplier must have been
        // chosen from the results so contact_id/supplier_id is actually present.
        if (!supplierId || !supplierId.value) {
            addIssue('Supplier', supplierSearch);
        }

        const rows = $$('.purchase-line-row', lineBody);
        if (!rows.length) {
            addIssue('At least one Product', productSearch);
        } else {
            rows.forEach(function (row, index) {
                const lineNo = index + 1;
                const quantity = $('.line-quantity', row);
                const cost = $('.line-unit-cost', row);
                const incTax = $('.line-inc-tax', row);

                if (!quantity || numeric(quantity.value) <= 0) {
                    addIssue('Product Row ' + lineNo + ' - Quantity', quantity);
                }
                if (!cost || String(cost.value || '').trim() === '' || numeric(cost.value) < 0) {
                    addIssue('Product Row ' + lineNo + ' - Purchase Cost', cost);
                }
                if (!incTax || String(incTax.value || '').trim() === '' || numeric(incTax.value) < 0) {
                    addIssue('Product Row ' + lineNo + ' - Cost Inc. Tax', incTax);
                }
            });
        }

        // A zero payment is intentionally allowed: it remains Supplier Due. An
        // account is mandatory only for a positive, non-credit actual payment.
        $$('.purchase-payment-row', paymentBody || document).forEach(function (row, index) {
            const method = normalisePaymentMethodKey($('.payment-method', row)?.value);
            const amount = Math.max(0, numeric($('.payment-amount', row)?.value));
            const account = $('.payment-account', row);
            if (amount > 0 && method !== 'credit_purchase' && (!account || !account.value)) {
                addIssue('Payment Row ' + (index + 1) + ' - Payment Account', account);
            }
        });

        return { labels: labels, firstElement: firstElement };
    }

    function showMandatoryFieldIssues() {
        const issues = mandatoryFieldIssues();
        if (!issues.labels.length) return false;

        showAlert('Please complete the following mandatory fields before saving: ' + issues.labels.join(', ') + '.', 'error');
        if (issues.firstElement && typeof issues.firstElement.focus === 'function') {
            try {
                issues.firstElement.focus({ preventScroll: true });
            } catch (ignore) {
                issues.firstElement.focus();
            }
            issues.firstElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return true;
    }

    async function loadUnloadTankRow(product, force) {
        const productId = Number(product?.product_id || 0);
        if (!productId || !unloadRows) return;
        const locationId = Number(locationSelect?.value || 0);
        if (!locationId) {
            productRows(productId).forEach(function (row) { row.dataset.fuelCategory = 'pending'; });
            updateSaveActionVisibility();
            return;
        }

        const existingResolved = productRows(productId).every(function (row) {
            return row.dataset.fuelCategory === '0' || row.dataset.fuelCategory === '1';
        });
        if (!force && existingResolved) {
            syncUnloadQuantity(productId);
            updateSaveActionVisibility();
            return;
        }

        const requestId = (unloadRequests[productId] || 0) + 1;
        unloadRequests[productId] = requestId;
        productRows(productId).forEach(function (row) { row.dataset.fuelCategory = 'pending'; });
        const oldRow = $('[data-product-id="' + String(productId) + '"]', unloadRows);
        if (oldRow) oldRow.remove();
        const loading = document.createElement('div');
        loading.className = 'purchase-unload-row purchase-unload-loading';
        loading.dataset.productId = String(productId);
        loading.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Checking unload tanks for ' + escapeHtml(product.product_name || 'the selected product') + '...';
        unloadRows.appendChild(loading);
        refreshUnloadCardVisibility();
        updateSaveActionVisibility();

        try {
            const url = config.routes.unload_tanks + '?product_id=' + encodeURIComponent(productId) + '&location_id=' + encodeURIComponent(locationId);
            const data = await fetchJson(url);
            if (requestId !== unloadRequests[productId]) return;
            loading.remove();

            if (!data.required) {
                productRows(productId).forEach(function (row) { row.dataset.fuelCategory = '0'; });
                refreshUnloadCardVisibility();
                updateSaveActionVisibility();
                return;
            }

            productRows(productId).forEach(function (row) { row.dataset.fuelCategory = '1'; });
            const template = document.createElement('template');
            template.innerHTML = String(data.html || '').trim();
            const row = template.content.firstElementChild;
            if (!row) throw new Error('The unload tank section could not be rendered.');
            unloadRows.appendChild(row);
            applyInitialTankAllocations(productId, row);
            syncUnloadQuantity(productId);
            refreshUnloadCardVisibility();
            updateSaveActionVisibility();
        } catch (error) {
            if (requestId !== unloadRequests[productId]) return;
            productRows(productId).forEach(function (row) { row.dataset.fuelCategory = 'error'; });
            loading.className = 'purchase-unload-row purchase-unload-error';
            loading.innerHTML = '<strong>Unload tanks could not be loaded.</strong><br>' + escapeHtml(error.message);
            refreshUnloadCardVisibility();
            updateSaveActionVisibility();
        }
    }

    async function reloadUnloadRows() {
        if (!unloadRows) return;
        unloadRows.innerHTML = '';
        refreshUnloadCardVisibility();
        const products = {};
        $$('.purchase-line-row', document.getElementById('purchase_lines_body')).forEach(function (row) {
            const productId = Number(row.dataset.productId || 0);
            if (!productId) return;
            products[productId] = {
                product_id: productId,
                product_name: $('.product-name', row)?.textContent || 'Selected product',
            };
        });
        await Promise.all(Object.values(products).map(function (product) {
            return loadUnloadTankRow(product, true);
        }));
    }

    const lineBody = $('#purchase_lines_body');
    lineBody?.addEventListener('focusin', function (event) {
        const input = event.target.closest('.line-quantity');
        if (!input || !lineBody.contains(input)) return;

        const row = input.closest('.purchase-line-row');
        if (input.dataset.clearDefaultOnFocus === '1') {
            input.value = '';
            delete input.dataset.clearDefaultOnFocus;
            scheduleQuantityColumnResize();
            if (row) {
                recalculateRow(row, input);
                syncUnloadQuantity(row.dataset.productId);
                updateSaveActionVisibility();
            }
            return;
        }

        // For an existing or Purchase Order quantity, select the full value after
        // focus so the next typed number replaces it rather than being appended.
        window.setTimeout(function () {
            if (document.activeElement === input && typeof input.select === 'function') {
                input.select();
                input.dataset.keepQuantitySelection = '1';
            }
        }, 0);
    });
    lineBody?.addEventListener('mouseup', function (event) {
        const input = event.target.closest('.line-quantity');
        if (!input || !lineBody.contains(input) || input.dataset.keepQuantitySelection !== '1') return;
        event.preventDefault();
        delete input.dataset.keepQuantitySelection;
    });
    lineBody?.addEventListener('input', function (event) {
        const row = event.target.closest('.purchase-line-row');
        if (row) {
            if (event.target.classList.contains('line-quantity')) {
                delete event.target.dataset.clearDefaultOnFocus;
                scheduleQuantityColumnResize();
            }
            recalculateRow(row, event.target);
            syncUnloadQuantity(row.dataset.productId);
            updateSaveActionVisibility();
        }
    });
    lineBody?.addEventListener('change', function (event) {
        const row = event.target.closest('.purchase-line-row');
        if (!row) return;
        if (event.target.classList.contains('line-unit')) changeLineUnit(row, event.target);
        if (event.target.classList.contains('line-quantity')) scheduleQuantityColumnResize();
        recalculateRow(row, event.target);
        syncUnloadQuantity(row.dataset.productId);
        updateSaveActionVisibility();
    });
    lineBody?.addEventListener('focusout', function (event) {
        if (!event.target.matches('.line-unit-cost, .line-inc-tax, .line-selling-price')) return;
        event.target.value = priceInput(event.target.value);
    });

    lineBody?.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-line');
        if (!button) return;
        const removedRow = button.closest('.purchase-line-row');
        const productId = removedRow.dataset.productId;
        removedRow.remove();
        if (!productRows(productId).length && unloadRows) {
            $('[data-product-id="' + String(productId).replace(/"/g, '') + '"]', unloadRows)?.remove();
        } else {
            syncUnloadQuantity(productId);
        }
        reindexLines();
        scheduleQuantityColumnResize();
        ensureEmptyProductRow();
        refreshUnloadCardVisibility();
        recalculateTotals();
        updateSaveActionVisibility();
    });

    unloadRows?.addEventListener('input', function (event) {
        if (!event.target.classList.contains('tank-qty')) return;
        const row = event.target.closest('.purchase-unload-row');
        if (row) updateUnloadMatch(row);
        updateSaveActionVisibility();
    });

    function selectedUnit(row) {
        const select = $('.line-unit', row);
        const option = select && select.options[select.selectedIndex];
        return {
            multiplier: Math.max(0.000001, numeric(option ? option.dataset.multiplier : 1) || 1),
            allowDecimal: !option || option.dataset.decimal !== '0',
        };
    }

    function changeLineUnit(row) {
        const oldMultiplier = Math.max(0.000001, numeric(row.dataset.unitMultiplier || 1));
        const unit = selectedUnit(row);
        // IS2112: selling price is a base-unit product master value and must not
        // be scaled when the purchase sub-unit changes.
        const priceFields = ['.line-unit-cost', '.line-inc-tax'];
        priceFields.forEach(function (selector) {
            const input = $(selector, row);
            if (!input) return;
            const preciseSelector = selector === '.line-unit-cost' ? '.line-unit-cost-precise' : '.line-inc-tax-precise';
            const preciseInput = preserveCalculationPrecision ? $(preciseSelector, row) : null;
            const sourceValue = preciseInput ? numeric(preciseInput.value) : numeric(input.value);
            const basePrice = sourceValue / oldMultiplier;
            const scaled = basePrice * unit.multiplier;
            input.value = priceInput(scaled);
            if (preciseInput) preciseInput.value = scaled.toFixed(6);
        });
        if ($('.line-discount-type', row).value === 'fixed') {
            const discount = $('.line-discount-value', row);
            discount.value = ((numeric(discount.value) / oldMultiplier) * unit.multiplier).toFixed(precision + 4);
        }
        row.dataset.unitMultiplier = String(unit.multiplier);
        $('.line-unit-multiplier', row).value = unit.multiplier;
        $$('.line-quantity, .line-free-qty', row).forEach(function (input) {
            input.step = unit.allowDecimal ? '0.000001' : '1';
        });
        updateDisplayedStock(row);
    }

    function updateDisplayedStock(row) {
        const baseStock = numeric(row.dataset.baseStock || 0);

        // Keep this number identical to Products New > Stock Centre > Available.
        // Stock Centre reports the location balance in the product's base stock
        // unit, so changing the purchase unit must not silently convert this
        // display into boxes/cartons/etc.
        $('.stock-value', row).textContent = qty(baseStock);
    }

    function recalculateRow(row, source) {
        const quantity = Math.max(0, numeric($('.line-quantity', row).value));
        const freeQty = $('.line-free-qty', row) ? Math.max(0, numeric($('.line-free-qty', row).value)) : 0;
        const unitCostInput = $('.line-unit-cost', row);
        const preciseUnitCostInput = preserveCalculationPrecision ? $('.line-unit-cost-precise', row) : null;
        const unitCostWasEdited = Boolean(source && source.classList && source.classList.contains('line-unit-cost')) && row.dataset.precisionInitialising !== '1';
        let baseCost = Math.max(0, numeric(unitCostInput.value));
        if (preciseUnitCostInput) {
            if (unitCostWasEdited) {
                preciseUnitCostInput.value = baseCost.toFixed(6);
            } else {
                baseCost = Math.max(0, numeric(preciseUnitCostInput.value));
            }
        }
        const discountType = $('.line-discount-type', row).value;
        const discountValue = Math.max(0, numeric($('.line-discount-value', row).value));
        const discountPerUnit = discountType === 'fixed'
            ? Math.min(baseCost, discountValue)
            : Math.min(baseCost, baseCost * Math.min(100, discountValue) / 100);
        const purchasePrice = Math.max(0, baseCost - discountPerUnit);
        const taxSelect = $('.line-tax', row);
        const selectedTax = taxSelect.options[taxSelect.selectedIndex];
        const taxRate = numeric(selectedTax ? selectedTax.dataset.rate : 0);
        const incInput = $('.line-inc-tax', row);
        const preciseIncTaxInput = preserveCalculationPrecision ? $('.line-inc-tax-precise', row) : null;
        const incTaxIsSource = Boolean(source && source.classList && source.classList.contains('line-inc-tax'));
        let incTax;
        if (incTaxIsSource) {
            // During initial Edit/Purchase-Order population the visible value may
            // already be rounded for display; keep the original precise stored
            // value. A real user edit becomes the new precise value.
            if (preciseIncTaxInput && row.dataset.precisionInitialising === '1') {
                incTax = Math.max(purchasePrice, numeric(preciseIncTaxInput.value));
            } else {
                incTax = Math.max(purchasePrice, numeric(incInput.value));
                if (preciseIncTaxInput) preciseIncTaxInput.value = incTax.toFixed(6);
            }
        } else {
            incTax = purchasePrice + purchasePrice * taxRate / 100;
            incInput.value = priceInput(incTax);
            if (preciseIncTaxInput) preciseIncTaxInput.value = incTax.toFixed(6);
        }
        const itemTax = Math.max(0, incTax - purchasePrice);
        // Required formula: Line Total = Unit Cost including tax × Qty.
        const lineTotal = quantity * incTax;

        $('.line-purchase-price', row).value = purchasePrice.toFixed(6);
        $('.line-item-tax', row).value = itemTax.toFixed(6);
        $('.line-discount-amount', row).value = discountPerUnit.toFixed(6);
        $('.line-discount-percent', row).value = (baseCost > 0 ? discountPerUnit / baseCost * 100 : 0).toFixed(6);
        $('.line-total-value', row).textContent = money(lineTotal);
        row.dataset.lineBeforeTax = String(quantity * purchasePrice);
        row.dataset.lineTax = String(quantity * itemTax);
        row.dataset.lineTotal = String(lineTotal);
        row.dataset.freeTotal = String(freeQty * incTax);

        if (config.enableSellingPrice) {
            const profitInput = $('.line-profit', row);
            const sellingInput = $('.line-selling-price', row);
            // IS2112: selling price comes from variations.sell_price_inc_tax and is
            // invariant on the purchase form. Recalculate only the displayed profit
            // percentage when purchase cost/tax changes; never derive selling price
            // from purchase cost + profit because that changes values such as 414.0000
            // into 413.9820 through percentage/rounding drift.
            const selling = Math.max(0, numeric(sellingInput.value));
            const baseIncTax = incTax / Math.max(0.000001, selectedUnit(row).multiplier);
            profitInput.value = (baseIncTax > 0 ? ((selling / baseIncTax) - 1) * 100 : 0).toFixed(2);
        }
        recalculateTotals();
    }

    function reindexLines() {
        $$('.purchase-line-row', lineBody).forEach(function (row, index) {
            $('.line-number', row).textContent = index + 1;
            $$('[name]', row).forEach(function (field) {
                field.name = field.name.replace(/purchases\[\d+\]/, 'purchases[' + index + ']');
            });
        });
        const productRowCount = $$('.purchase-line-row', lineBody).length;
        $('#purchase_line_count').textContent = productRowCount + (productRowCount === 1 ? ' product row' : ' product rows');
    }

    function ensureEmptyProductRow() {
        if ($$('.purchase-line-row', lineBody).length === 0 && !$('.purchase-empty-row', lineBody)) {
            lineBody.innerHTML = '<tr class="purchase-empty-row"><td colspan="22">Search and select a product to add it to this purchase.</td></tr>';
        }
    }

    // Purchase-level discount amount starts at zero. Clear that placeholder
    // when the user enters the field so the typed discount replaces zero rather
    // than being appended to it. Existing non-zero values are selected in full.
    const purchaseDiscountAmount = $('#discount_amount');
    purchaseDiscountAmount?.addEventListener('focus', function () {
        if (Math.abs(numeric(this.value)) < 0.0000001) {
            this.value = '';
            recalculateTotals();
            return;
        }
        window.setTimeout(() => {
            if (document.activeElement === this && typeof this.select === 'function') this.select();
        }, 0);
    });
    purchaseDiscountAmount?.addEventListener('blur', function () {
        if (String(this.value || '').trim() === '') this.value = '0';
        recalculateTotals();
    });

    // Purchase-level totals.
    ['discount_type', 'discount_amount', 'tax_id', 'shipping_charges', 'price_adjustment', 'apply_free_product_total', 'exchange_rate'].forEach(function (id) {
        const field = document.getElementById(id);
        if (!field) return;
        field.addEventListener('input', recalculateTotals);
        field.addEventListener('change', recalculateTotals);
    });

    function recalculateTotals() {
        let subtotal = 0;
        let lineTax = 0;
        let freeTotal = 0;
        $$('.purchase-line-row', lineBody).forEach(function (row) {
            subtotal += numeric(row.dataset.lineBeforeTax);
            lineTax += numeric(row.dataset.lineTax);
            freeTotal += numeric(row.dataset.freeTotal);
        });
        const discountInput = Math.max(0, numeric($('#discount_amount').value));
        const discount = $('#discount_type').value === 'percentage'
            ? subtotal * Math.min(100, discountInput) / 100
            : Math.min(subtotal, discountInput);
        const orderTaxSelect = $('#tax_id');
        const orderTaxOption = orderTaxSelect.options[orderTaxSelect.selectedIndex];
        const taxRate = orderTaxSelect.value ? numeric(orderTaxOption ? orderTaxOption.dataset.rate : 0) : 0;
        const orderTax = Math.max(0, subtotal - discount) * taxRate / 100;
        const shipping = Math.max(0, numeric($('#shipping_charges').value));
        const adjustment = numeric($('#price_adjustment').value);
        const includedFreeTotal = $('#apply_free_product_total').checked ? freeTotal : 0;
        const finalTotal = Math.max(0, subtotal + lineTax - discount + orderTax + shipping + adjustment + includedFreeTotal);

        $('#total_before_tax').value = subtotal.toFixed(6);
        $('#tax_amount').value = (lineTax + orderTax).toFixed(6);
        $('#final_total').value = finalTotal.toFixed(6);
        $('#summary_subtotal').textContent = money(subtotal);
        $('#summary_line_tax').textContent = money(lineTax);
        $('#summary_discount').textContent = money(discount);
        $('#summary_order_tax').textContent = money(orderTax);
        $('#summary_shipping').textContent = money(shipping);
        $('#summary_adjustment').textContent = money(adjustment);
        $('#summary_free_total').textContent = money(includedFreeTotal);
        $('#summary_final_total').textContent = money(finalTotal);
        recalculatePayments(finalTotal);
    }

    // Multiple payment rows with location/method-linked payment accounts.
    function normalisePaymentMethodKey(method) {
        return String(method || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function paymentMethodAliases(method) {
        const key = normalisePaymentMethodKey(method);
        const aliases = {
            cash: ['cash'],
            bank_transfer: ['bank_transfer', 'direct_bank_deposit'],
            cheque: ['cheque'],
            card: ['card', 'own_cards'],
            advance: ['advance', 'pre_payments', 'prepayment'],
            prepayment: ['prepayment', 'pre_payments', 'advance'],
            other: ['other'],
            credit_purchase: ['credit_purchase', 'credit_purchase_due', 'credit', 'pay_later'],
        };
        return Array.from(new Set([key].concat(aliases[key] || [])));
    }

    function linkedPaymentAccountIds(method) {
        const allMappings = config.paymentMethodAccounts && typeof config.paymentMethodAccounts === 'object'
            ? config.paymentMethodAccounts
            : {};
        const locationMap = allMappings[String(locationSelect.value || '')] || {};
        const ids = [];
        paymentMethodAliases(method).forEach(function (alias) {
            const linked = locationMap[alias];
            if (Array.isArray(linked)) {
                linked.forEach(function (id) {
                    const value = String(id == null ? '' : id).trim();
                    if (value) ids.push(value);
                });
            } else if (linked != null && String(linked).trim() !== '') {
                ids.push(String(linked).trim());
            }
        });
        return Array.from(new Set(ids));
    }

    function eligiblePaymentAccounts(method) {
        const linkedIds = linkedPaymentAccountIds(method);
        if (!linkedIds.length) return [];

        const locationId = String(locationSelect.value || '');

        const inLocation = (config.accounts || []).filter(function (account) {
            const scope = String(account.location_id == null ? 'all' : account.location_id);
            return !(scope && scope !== 'all' && scope !== locationId);
        });

        /*
         | IS2106: match the payment method's linked id against the account's
         | GROUP, not against the account itself.
         |
         | default_payment_accounts stores an account GROUP id - Payment Options
         | links a method to a group, and the entry form then narrows accounts to
         | that group. The old test was:
         |
         |     linkedIds.includes(accountId) || linkedIds.includes(groupId)
         |
         | so an account whose own id happened to equal a group id was offered as
         | well. Group 43 is "Cash Account"; account 43 is a different record
         | entirely, and both matched. That is the irrelevant accounts.
         |
         | Group matches are used whenever there are any. The account-id test is
         | kept only as a fallback for installs whose stored value really is an
         | account id, so older data does not suddenly offer nothing.
        */
        const byGroup = inLocation.filter(function (account) {
            const groupId = String(account.group_id == null ? '' : account.group_id);
            return groupId !== '' && linkedIds.includes(groupId);
        });

        if (byGroup.length) {
            return byGroup;
        }

        return inLocation.filter(function (account) {
            const accountId = String(account.id == null ? '' : account.id);
            return accountId !== '' && linkedIds.includes(accountId);
        });
    }

    function accountOptions(selectedId, method) {
        const accounts = eligiblePaymentAccounts(method);
        if (!accounts.length) {
            return '<option value="">No linked payment account</option>';
        }

        let html = '<option value="">Please select</option>';
        accounts.forEach(function (account) {
            const selected = String(account.id) === String(selectedId || '') ? ' selected' : '';
            const group = account.group_name ? ' — ' + account.group_name : '';
            html += '<option value="' + escapeHtml(account.id) + '"' + selected + '>' + escapeHtml(account.name + group) + '</option>';
        });
        return html;
    }

    function configuredPaymentMethods() {
        const byLocation = config.paymentMethodsByLocation && typeof config.paymentMethodsByLocation === 'object'
            ? config.paymentMethodsByLocation
            : {};
        const locationMethods = byLocation[String(locationSelect?.value || '')];
        if (locationMethods && typeof locationMethods === 'object') return locationMethods;
        return config.paymentMethods && typeof config.paymentMethods === 'object' ? config.paymentMethods : {};
    }

    function firstAvailablePaymentMethod() {
        const keys = Object.keys(configuredPaymentMethods());
        const actual = keys.find(function (key) { return normalisePaymentMethodKey(key) !== 'credit_purchase'; });
        return actual || keys[0] || 'credit_purchase';
    }

    function methodOptions(selectedMethod, preserveExisting) {
        const methods = Object.assign({}, configuredPaymentMethods());
        const selected = String(selectedMethod || '');

        // IS2341: disabled historical payment methods must not be re-injected
        // into Add/Edit dropdowns. The only non-Payment-Options value retained
        // is the module's structural Credit Purchase (Due) row.
        if (selected === 'credit_purchase' && !Object.prototype.hasOwnProperty.call(methods, selected)) {
            methods[selected] = 'Credit Purchase (Due)';
        }

        return Object.keys(methods).map(function (key) {
            return '<option value="' + escapeHtml(key) + '"' + (key === selected ? ' selected' : '') + '>' + escapeHtml(methods[key]) + '</option>';
        }).join('');
    }

    function refreshPaymentMethodsForLocation() {
        const allowed = configuredPaymentMethods();
        $$('.purchase-payment-row', paymentBody || document).forEach(function (row) {
            const select = $('.payment-method', row);
            if (!select) return;
            const current = String(select.value || '');
            const keepCredit = normalisePaymentMethodKey(current) === 'credit_purchase';
            const next = keepCredit || Object.prototype.hasOwnProperty.call(allowed, current)
                ? current
                : firstAvailablePaymentMethod();
            select.innerHTML = methodOptions(next, false);
            if (next) select.value = next;
            togglePaymentDetails(row, false);
        });
    }

    function paymentValue(payment, key, fallback) {
        return payment && typeof payment === 'object' && payment[key] != null ? payment[key] : (fallback == null ? '' : fallback);
    }

    function formatPaymentAmountValue(value) {
        const number = Math.max(0, numeric(value));
        return number.toLocaleString(undefined, {
            minimumFractionDigits: precision,
            maximumFractionDigits: precision,
        });
    }

    function rawPaymentAmountValue(value) {
        const number = Math.max(0, numeric(value));
        if (number === 0) return '';
        return String(Number(number.toFixed(Math.max(precision, 6))));
    }

    function projectedSystemPaymentReference(index, existingReference) {
        const existing = String(existingReference || '').trim();
        if (existing !== '') return existing;

        const base = String(config.paymentReferencePreview || '').trim();
        if (base === '') return 'Generated on save';

        // Preview only: keep the configured prefix/year and project the row number.
        // The server remains authoritative and consumes the real next number on Save.
        const match = base.match(/^(.*-)(\d+)$/);
        if (!match) return base;
        const width = match[2].length;
        const number = Math.max(1, parseInt(match[2], 10) + Math.max(0, Number(index) || 0));
        return match[1] + String(number).padStart(width, '0');
    }

    function buildPaymentRow(index, paymentOrMethod) {
        const payment = paymentOrMethod && typeof paymentOrMethod === 'object' ? paymentOrMethod : { method: paymentOrMethod || 'cash' };
        const method = String(payment.method || 'cash');
        const p = 'payments[' + index + ']';
        const systemReference = String(paymentValue(payment, 'payment_ref_no', '') || '').trim();
        const displaySystemReference = projectedSystemPaymentReference(index, systemReference);
        return '<td><input type="hidden" name="' + p + '[payment_ref_no]" value="' + escapeHtml(systemReference) + '"><select class="form-control payment-method" name="' + p + '[method]">' + methodOptions(method) + '</select></td>' +
            '<td><input type="text" class="form-control payment-system-reference" value="' + escapeHtml(displaySystemReference) + '" readonly title="Preview from Supplier Settings; final reference is assigned on save"></td>' +
            '<td><input type="text" inputmode="decimal" autocomplete="off" class="form-control payment-amount purchase-number" name="' + p + '[amount]" value="' + escapeHtml(formatPaymentAmountValue(paymentValue(payment, 'amount', 0))) + '"></td>' +
            '<td><select class="form-control payment-account" name="' + p + '[account_id]">' + accountOptions(paymentValue(payment, 'account_id'), method) + '</select></td>' +
            '<td><input type="datetime-local" class="form-control payment-paid-on" name="' + p + '[paid_on]" value="' + escapeHtml(paymentValue(payment, 'paid_on', dateTimeNow())) + '"></td>' +
            '<td><input type="text" class="form-control payment-note" name="' + p + '[note]" value="' + escapeHtml(paymentValue(payment, 'note')) + '"></td>' +
            '<td class="text-center"><button type="button" class="remove-payment" title="Remove payment"><i class="fa fa-times-circle"></i></button></td>';
    }

    function initPaymentAccountSelect(select) {
        const jq = window.jQuery;
        if (!select || !jq || !jq.fn || !jq.fn.select2) return;
        const element = jq(select);
        if (element.hasClass('select2-hidden-accessible')) element.select2('destroy');
        element.select2({
            width: '100%',
            minimumResultsForSearch: 0,
            placeholder: 'Type or select payment account',
            allowClear: true,
        });
    }

    function destroyPaymentAccountSelect(select) {
        const jq = window.jQuery;
        if (!select || !jq || !jq.fn || !jq.fn.select2) return;
        const element = jq(select);
        if (element.hasClass('select2-hidden-accessible')) element.select2('destroy');
    }

    function addPaymentRow(paymentOrMethod, autoCredit, skipRecalculate) {
        const payment = paymentOrMethod && typeof paymentOrMethod === 'object'
            ? paymentOrMethod
            : { method: paymentOrMethod || 'cash' };
        const row = document.createElement('tr');
        row.className = 'purchase-payment-row';
        row.dataset.autoCredit = (autoCredit || payment.auto_credit) ? '1' : '0';
        row.innerHTML = buildPaymentRow(paymentIndex++, payment);
        $('#purchase_payment_rows').appendChild(row);
        togglePaymentDetails(row, false);
        reindexPayments();
        if (!skipRecalculate) recalculateTotals();
        return row;
    }

    $('#add_payment_row')?.addEventListener('click', function () { addPaymentRow(firstAvailablePaymentMethod(), false); });
    const paymentBody = $('#purchase_payment_rows');
    paymentBody?.addEventListener('change', function (event) {
        const row = event.target.closest('.purchase-payment-row');
        if (!row) return;
        if (event.target.classList.contains('payment-method')) togglePaymentDetails(row, true);
        recalculateTotals();
    });
    paymentBody?.addEventListener('input', function (event) {
        const row = event.target.closest('.purchase-payment-row');
        if (!row) return;
        if (event.target.classList.contains('payment-amount')) {
            row.dataset.autoCredit = '0';
            const method = normalisePaymentMethodKey($('.payment-method', row)?.value);
            const account = $('.payment-account', row);
            if (account) {
                account.required = method !== 'credit_purchase' && Math.max(0, numeric(event.target.value)) > 0;
            }
        }
        recalculatePayments(numeric($('#final_total').value));
    });
    paymentBody?.addEventListener('focusin', function (event) {
        if (!event.target.classList.contains('payment-amount')) return;
        event.target.value = rawPaymentAmountValue(event.target.value);
        window.setTimeout(function () { event.target.select(); }, 0);
    });
    paymentBody?.addEventListener('focusout', function (event) {
        if (!event.target.classList.contains('payment-amount')) return;
        event.target.value = formatPaymentAmountValue(event.target.value);
        recalculatePayments(numeric($('#final_total').value));
    });
    paymentBody?.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-payment');
        if (!button) return;
        const row = button.closest('.purchase-payment-row');
        destroyPaymentAccountSelect($('.payment-account', row));
        row.remove();
        if (!$$('.purchase-payment-row', paymentBody).length && $('#status').value === 'received') {
            addPaymentRow('credit_purchase', true, true);
        }
        reindexPayments();
        recalculateTotals();
    });

    function togglePaymentDetails(row) {
        const method = $('.payment-method', row).value;
        const account = $('.payment-account', row);
        const paidOn = $('.payment-paid-on', row);
        const isCredit = normalisePaymentMethodKey(method) === 'credit_purchase';
        const previousAccount = account.value;
        const eligible = eligiblePaymentAccounts(method);

        destroyPaymentAccountSelect(account);
        account.innerHTML = accountOptions(previousAccount, method);
        if (previousAccount && eligible.some(function (item) { return String(item.id) === String(previousAccount); })) {
            account.value = previousAccount;
        } else if (eligible.length >= 1) {
            /*
             | Select the FIRST eligible account, not only when there is exactly
             | one.
             |
             | This used to leave the field empty whenever a method offered two
             | or more accounts - and since the account is required, the form
             | became invalid and the Save bar vanished with nothing to say why.
             | Switching payment method almost always makes the previous account
             | ineligible, so this was the common path, not the rare one.
             |
             | Choosing for the user is better than a form that silently refuses
             | to offer saving. A wrong account is visible on screen and can be
             | corrected; an empty one looks like nothing is wrong.
            */
            account.value = String(eligible[0].id);
        } else {
            account.value = '';
        }

        // Credit Purchase is still a due allocation rather than an actual payment,
        // but the configured credit-purchase account must remain visible/selectable
        // so the user can verify the location-specific account mapping.
        account.disabled = eligible.length === 0;
        const paymentAmount = Math.max(0, numeric($('.payment-amount', row)?.value));
        account.required = !isCredit && paymentAmount > 0;
        paidOn.disabled = isCredit;
        initPaymentAccountSelect(account);

        /*
         | Re-check whether the form is ready to save.
         |
         | Changing the payment method clears the account while its list is
         | rebuilt. The readiness check runs during that gap, finds an empty
         | required account and hides the Save bar - then the account IS
         | selected, and nothing looks again. The buttons stay hidden over a
         | form that is perfectly valid, with nothing to say why.
         |
         | Called after the account has been set, not before.
        */
        updateSaveActionVisibility();
    }

    function reindexPayments() {
        $$('.purchase-payment-row', paymentBody).forEach(function (row, index) {
            $$('[name]', row).forEach(function (field) {
                field.name = field.name.replace(/payments\[\d+\]/, 'payments[' + index + ']');
            });
        });
    }

    function refreshPaymentAccounts() {
        $$('.purchase-payment-row', paymentBody).forEach(function (row) {
            togglePaymentDetails(row, false);
        });
    }

    function recalculatePayments(finalTotal) {
        let actualPaid = 0;
        let amountEntered = 0;
        let autoCreditRow = null;

        $$('.purchase-payment-row', paymentBody).forEach(function (row) {
            const method = normalisePaymentMethodKey($('.payment-method', row).value);
            const amount = Math.max(0, numeric($('.payment-amount', row).value));
            const account = $('.payment-account', row);
            if (account) {
                account.required = method !== 'credit_purchase' && amount > 0;
            }
            if (method === 'credit_purchase') {
                if (row.dataset.autoCredit === '1') autoCreditRow = row;
                else amountEntered += amount;
                return;
            }
            actualPaid += amount;
        });

        const remainingBeforeAutoCredit = Math.max(0, finalTotal - actualPaid - amountEntered);
        if (autoCreditRow) {
            const input = $('.payment-amount', autoCreditRow);
            input.value = formatPaymentAmountValue(remainingBeforeAutoCredit);
            amountEntered += remainingBeforeAutoCredit;
        }

        const due = Math.max(0, finalTotal - actualPaid - amountEntered);
        $('#payment_entered_total').textContent = money(amountEntered);
        $('#payment_paid_total').textContent = money(actualPaid);
        $('#payment_due').textContent = money(due);

        const dueItem = $('.purchase-payment-due');
        if (dueItem) dueItem.classList.toggle('has-balance', due > 0.000001);
    }

    $('#status')?.addEventListener('change', applyPurchaseStatusToPayments);
    function applyPurchaseStatusToPayments() {
        const received = $('#status').value === 'received';
        const addButton = $('#add_payment_row');
        addButton.disabled = !received;
        const note = $('#payment_status_note');
        note.textContent = received
            ? 'Multiple payments are supported. The remaining balance is posted as supplier due.'
            : 'Pending/Ordered purchases cannot record actual payments. The full amount remains due until received.';
        note.className = 'purchase-help purchase-payment-note ' + (received ? 'is-success' : 'is-error');

        if (!received) {
            $$('.payment-account', paymentBody).forEach(destroyPaymentAccountSelect);
            paymentBody.innerHTML = '';
            paymentIndex = 0;
        } else if (!$$('.purchase-payment-row', paymentBody).length) {
            addPaymentRow('credit_purchase', true, true);
        }
        reindexPayments();
        recalculatePayments(numeric($('#final_total').value));
        $$('.purchase-unload-row', unloadRows || document).forEach(updateUnloadMatch);
        updateSaveActionVisibility();
    }

    form.addEventListener('input', function (event) {
        const target = event.target;
        if (!target || !target.classList) return;
        target.classList.remove('purchase-required-missing');
        target.removeAttribute('aria-invalid');
        const field = target.closest('.purchase-field');
        if (field) field.classList.remove('has-required-error');
    });

    form.addEventListener('change', function (event) {
        const target = event.target;
        if (!target || !target.classList) return;
        target.classList.remove('purchase-required-missing');
        target.removeAttribute('aria-invalid');
        const field = target.closest('.purchase-field');
        if (field) field.classList.remove('has-required-error');
    });

    // Preserve the clicked save action and submit to this module's endpoint only.
    $$('.purchase-save-button').forEach(function (button) {
        button.addEventListener('click', function () {
            $('#save_action').value = button.dataset.saveAction || 'list';
        });
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (submitting) return;
        hideAlert();
        const clicked = event.submitter || $('.purchase-save-button[data-save-action="' + $('#save_action').value + '"]') || $('#save_purchase_entry');
        if (clicked?.dataset.saveAction) $('#save_action').value = clicked.dataset.saveAction;

        if (showMandatoryFieldIssues()) {
            return;
        }
        if ($('#status').value === 'received' && !unloadAllocationsReady()) {
            showAlert('Allocate the complete received fuel quantity across the available unload tanks.', 'error');
            unloadCard?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        if (!supplierId.value) {
            showAlert('Please select a supplier from the filtered supplier results.', 'error');
            supplierSearch.focus();
            return;
        }
        if (!$$('.purchase-line-row', lineBody).length) {
            showAlert('Add at least one product to the purchase.', 'error');
            productSearch.focus();
            return;
        }
        if (!form.reportValidity()) {
            showAlert('Please correct the highlighted form field before saving.', 'error');
            return;
        }
        if (await checkReference()) {
            showAlert('The supplier invoice/reference number is already used.', 'error');
            $('#ref_no').focus();
            return;
        }

        recalculateTotals();
        submitting = true;
        const buttons = $$('.purchase-save-button');
        buttons.forEach(function (button) {
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
        });
        if (clicked) clicked.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
        form.classList.add('purchase-loading');

        try {
            const data = await fetchJson(config.routes.store, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': config.csrf },
                body: new FormData(form),
            });
            showAlert(data.message || (config.isEdit ? 'Purchase updated successfully.' : 'Purchase saved successfully.'), 'success');
            window.location.href = data.redirect_url || config.routes.index;
        } catch (error) {
            showAlert(error.message, 'error');
            submitting = false;
            buttons.forEach(function (button) {
                button.disabled = false;
                if (button.dataset.originalHtml) button.innerHTML = button.dataset.originalHtml;
            });
            form.classList.remove('purchase-loading');
        }
    });

    form.addEventListener('input', updateSaveActionVisibility);
    form.addEventListener('change', updateSaveActionVisibility);

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.purchase-autocomplete-wrap') && !event.target.closest('.purchase-product-search-wrap')) closeSearchResults();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSearchResults();
            closeModal(supplierModal);
            closeModal(productModal);
        }
    });

    // Initial state.
    setQuickProductScope();
    (config.initialLines || []).forEach(function (product) {
        addProductLine(product, false);
    });
    reindexLines();
    scheduleQuantityColumnResize();

    const initialPayments = Array.isArray(config.initialPayments) ? config.initialPayments : [];
    if ($('#status').value === 'received' && initialPayments.length) {
        paymentBody.innerHTML = '';
        paymentIndex = 0;
        initialPayments.forEach(function (payment) {
            addPaymentRow(payment, Boolean(payment.auto_credit), true);
        });
    }
    refreshPaymentAccounts();
    applyPurchaseStatusToPayments();
    recalculateTotals();
    refreshUnloadCardVisibility();
    if (!config.isEdit && purchaseOrderSelect) {
        if (supplierId?.value) {
            loadPendingPurchaseOrders(supplierId.value);
        } else {
            resetPurchaseOrderSelector('Select a supplier to check its pending purchase orders.', false);
        }
    }
    updateSaveActionVisibility();
})();
