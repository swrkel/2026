(function () {
    'use strict';

    var searchStates = new WeakMap();
    var batchStates = new WeakMap();
    var contextRefreshTimer = null;
    var openSearchInputs = new Set();
    var repositionFrame = null;

    /**
     * DataTables does not support colspan cells inside tbody. Older empty-state
     * rows in this module used a single colspan cell, which caused the
     * "Incorrect column count" warning before DataTables could build its own
     * empty-table row. Remove only those placeholder rows; normal data rows are
     * never changed.
     */
    function removeUnsafeEmptyTableRows() {
        document.querySelectorAll('.san-page table tbody tr').forEach(function (row) {
            var cells = row.children;
            if (cells.length !== 1) {
                return;
            }

            var cell = cells[0];
            if (cell.tagName === 'TD' && Number(cell.getAttribute('colspan') || 1) > 1) {
                row.remove();
            }
        });
    }

    removeUnsafeEmptyTableRows();

    function getSearchState(input) {
        if (!searchStates.has(input)) {
            var lookup = input ? input.closest('.san-product-lookup') : null;
            searchStates.set(input, {
                timer: null,
                controller: null,
                items: [],
                activeIndex: -1,
                resultsBox: lookup ? lookup.querySelector('[data-san-product-results]') : null
            });
        }

        return searchStates.get(input);
    }

    function getBatchState(select) {
        if (!batchStates.has(select)) {
            batchStates.set(select, {
                controller: null
            });
        }

        return batchStates.get(select);
    }

    function getForm(element) {
        return element ? element.closest('[data-san-adjustment-form]') : null;
    }

    function getRow(element) {
        return element ? element.closest('[data-san-line-row]') : null;
    }

    function getResultsBox(input) {
        if (!input) {
            return null;
        }

        var state = searchStates.get(input);
        if (state && state.resultsBox) {
            return state.resultsBox;
        }

        var lookup = input.closest('.san-product-lookup');
        var resultsBox = lookup ? lookup.querySelector('[data-san-product-results]') : null;
        if (state) {
            state.resultsBox = resultsBox;
        }

        return resultsBox;
    }

    /**
     * Product suggestions are moved to document.body while open. This avoids
     * clipping by Bootstrap table-responsive wrappers, table cells and the
     * action bar below the table. The list is returned to its lookup container
     * when closed so cloned rows and normal form behaviour remain unchanged.
     */
    function restoreResultsBox(input, resultsBox) {
        var lookup = input ? input.closest('.san-product-lookup') : null;
        if (!lookup || !resultsBox) {
            return;
        }

        resultsBox.classList.remove('san-product-results-portal', 'opens-up');
        resultsBox.style.position = '';
        resultsBox.style.top = '';
        resultsBox.style.left = '';
        resultsBox.style.right = '';
        resultsBox.style.width = '';
        resultsBox.style.maxHeight = '';
        resultsBox.style.zIndex = '';

        if (resultsBox.parentNode !== lookup) {
            lookup.appendChild(resultsBox);
        }
    }

    function positionResultsBox(input) {
        var resultsBox = getResultsBox(input);
        if (!input || !resultsBox || !resultsBox.classList.contains('is-open')) {
            return;
        }

        if (resultsBox.parentNode !== document.body) {
            document.body.appendChild(resultsBox);
        }

        resultsBox.classList.add('san-product-results-portal');

        var rect = input.getBoundingClientRect();
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 1024;
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 768;
        var gap = 4;
        var edge = 8;
        var preferredMaxHeight = 310;
        var belowSpace = Math.max(0, viewportHeight - rect.bottom - gap - edge);
        var aboveSpace = Math.max(0, rect.top - gap - edge);
        var openUp = belowSpace < 150 && aboveSpace > belowSpace;
        var availableSpace = openUp ? aboveSpace : belowSpace;
        var maxHeight = Math.max(90, Math.min(preferredMaxHeight, availableSpace || preferredMaxHeight));
        var width = Math.max(rect.width, 260);
        width = Math.min(width, viewportWidth - (edge * 2));
        var left = Math.min(Math.max(edge, rect.left), Math.max(edge, viewportWidth - width - edge));

        resultsBox.style.position = 'fixed';
        resultsBox.style.left = Math.round(left) + 'px';
        resultsBox.style.right = 'auto';
        resultsBox.style.width = Math.round(width) + 'px';
        resultsBox.style.maxHeight = Math.round(maxHeight) + 'px';
        resultsBox.style.zIndex = '2147483000';
        resultsBox.classList.toggle('opens-up', openUp);

        var renderedHeight = Math.min(maxHeight, resultsBox.scrollHeight || maxHeight);
        var top = openUp
            ? Math.max(edge, rect.top - gap - renderedHeight)
            : Math.min(viewportHeight - edge, rect.bottom + gap);

        resultsBox.style.top = Math.round(top) + 'px';
    }

    function openResultsBox(input, resultsBox) {
        if (!input || !resultsBox) {
            return;
        }

        resultsBox.classList.add('is-open');
        input.setAttribute('aria-expanded', 'true');
        openSearchInputs.add(input);
        positionResultsBox(input);
    }

    function scheduleOpenResultsPosition() {
        if (repositionFrame !== null) {
            return;
        }

        repositionFrame = window.requestAnimationFrame(function () {
            repositionFrame = null;
            openSearchInputs.forEach(function (input) {
                if (!document.documentElement.contains(input)) {
                    openSearchInputs.delete(input);
                    return;
                }
                positionResultsBox(input);
            });
        });
    }

    function valueOf(row, selector) {
        var field = row ? row.querySelector(selector) : null;
        return field ? field.value.trim() : '';
    }

    function setValue(row, selector, value) {
        var field = row ? row.querySelector(selector) : null;
        if (field) {
            field.value = value === null || typeof value === 'undefined' ? '' : String(value);
        }
        return field;
    }

    function normaliseNumber(value) {
        var number = Number(value);
        if (!Number.isFinite(number)) {
            return '0';
        }

        return number.toFixed(4).replace(/\.?0+$/, '') || '0';
    }

    function displayNumber(value) {
        var number = Number(value);
        return Number.isFinite(number) ? number.toFixed(4) : '0.0000';
    }

    function setBatchHelp(row, message) {
        var help = row ? row.querySelector('[data-san-batch-help]') : null;
        if (help) {
            help.textContent = message || '';
        }
    }

    function restoreProductSystemQty(row) {
        if (!row) {
            return;
        }

        var productSystemQty = row.dataset.productSystemQty;
        if (typeof productSystemQty !== 'undefined' && productSystemQty !== '') {
            setValue(row, '[data-san-system-qty]', productSystemQty);
        }
    }

    function resetBatch(row, message) {
        var select = row ? row.querySelector('[data-san-batch-no]') : null;
        if (!select) {
            return;
        }

        var state = getBatchState(select);
        if (state.controller) {
            state.controller.abort();
            state.controller = null;
        }

        select.innerHTML = '';
        var option = document.createElement('option');
        option.value = '';
        option.textContent = message || 'Select product first';
        select.appendChild(option);

        select.value = '';
        select.disabled = true;
        select.required = false;
        select.dataset.requiresBatch = '0';
        select.dataset.selectedValue = '';
        select.setCustomValidity('');
        setValue(row, '[data-san-expiry-date]', '');
        restoreProductSystemQty(row);
        setBatchHelp(row, message || 'Select a product to load available batches.');
    }

    function buildBatchUrl(form, row) {
        var endpoint = form ? form.dataset.batchSearchUrl : '';
        var productId = valueOf(row, '[data-san-product-id]');
        if (!endpoint || !/^\d+$/.test(productId) || Number(productId) <= 0) {
            return null;
        }

        var url = new URL(endpoint, window.location.href);
        url.searchParams.set('product_id', productId);

        var variationId = valueOf(row, '[data-san-variation-id]');
        var locationId = valueOf(form, '[data-san-location-id]');
        var storeId = valueOf(form, '[data-san-store-id]');

        if (/^\d+$/.test(variationId) && Number(variationId) > 0) {
            url.searchParams.set('variation_id', variationId);
        }

        if (/^\d+$/.test(locationId) && Number(locationId) > 0) {
            url.searchParams.set('location_id', locationId);
        }

        if (/^\d+$/.test(storeId) && Number(storeId) > 0) {
            url.searchParams.set('store_id', storeId);
        }

        return url.toString();
    }

    function batchOptionText(batch) {
        var label = batch.label || batch.batch_no || '';
        var details = ['Stock: ' + displayNumber(batch.available_qty)];

        if (batch.expiry_date) {
            details.push('Expiry: ' + batch.expiry_date);
        }

        return label + ' — ' + details.join(' | ');
    }

    function applyBatchSelection(select) {
        var row = getRow(select);
        if (!row) {
            return;
        }

        var selectedOption = select.options[select.selectedIndex] || null;
        var hasBatch = Boolean(select.value && selectedOption);

        if (!hasBatch) {
            setValue(row, '[data-san-expiry-date]', '');
            restoreProductSystemQty(row);
            select.dataset.selectedValue = '';
            select.setCustomValidity(
                select.dataset.requiresBatch === '1'
                    ? 'Please select an available batch number.'
                    : ''
            );
            return;
        }

        setValue(row, '[data-san-system-qty]', normaliseNumber(selectedOption.dataset.availableQty));
        setValue(row, '[data-san-expiry-date]', selectedOption.dataset.expiryDate || '');

        var unitCost = row.querySelector('[data-san-unit-cost]');
        var batchUnitCost = Number(selectedOption.dataset.unitCost || 0);
        if (unitCost && (unitCost.value.trim() === '' || Number(unitCost.value) === 0) && batchUnitCost > 0) {
            unitCost.value = normaliseNumber(batchUnitCost);
        }

        select.dataset.selectedValue = select.value;
        select.setCustomValidity('');
    }

    function renderBatches(row, batches, preferredBatchNo, requiresBatch) {
        var select = row ? row.querySelector('[data-san-batch-no]') : null;
        if (!select) {
            return;
        }

        var availableBatches = Array.isArray(batches)
            ? batches.filter(function (batch) {
                return batch && batch.batch_no && Number(batch.available_qty) > 0;
            })
            : [];

        if (!availableBatches.length) {
            resetBatch(row, 'No available batch stock');
            setBatchHelp(row, 'No batch number with positive stock is available for this product.');
            return;
        }

        select.innerHTML = '';

        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Please select batch number';
        select.appendChild(placeholder);

        availableBatches.forEach(function (batch) {
            var option = document.createElement('option');
            option.value = String(batch.batch_no);
            option.textContent = batchOptionText(batch);
            option.dataset.availableQty = normaliseNumber(batch.available_qty);
            option.dataset.expiryDate = batch.expiry_date || '';
            option.dataset.unitCost = normaliseNumber(batch.unit_cost);
            select.appendChild(option);
        });

        select.disabled = false;
        select.required = Boolean(requiresBatch);
        select.dataset.requiresBatch = requiresBatch ? '1' : '0';
        select.setCustomValidity(requiresBatch ? 'Please select an available batch number.' : '');

        var preferred = String(preferredBatchNo || '').trim();
        if (preferred) {
            var matchingOption = Array.from(select.options).find(function (option) {
                return option.value.toLowerCase() === preferred.toLowerCase();
            });

            if (matchingOption) {
                select.value = matchingOption.value;
                applyBatchSelection(select);
            }
        }

        setBatchHelp(
            row,
            availableBatches.length + (availableBatches.length === 1 ? ' available batch' : ' available batches') +
                ' with positive stock.'
        );
    }

    function fetchAvailableBatches(row, preferredBatchNo) {
        var form = getForm(row);
        var select = row ? row.querySelector('[data-san-batch-no]') : null;
        var url = buildBatchUrl(form, row);

        if (!select || !url) {
            resetBatch(row, 'Select product first');
            return;
        }

        var state = getBatchState(select);
        if (state.controller) {
            state.controller.abort();
        }

        var preferred = String(
            preferredBatchNo ||
            select.dataset.selectedValue ||
            select.value ||
            ''
        ).trim();

        select.innerHTML = '<option value="">Loading available batches...</option>';
        select.disabled = true;
        select.required = false;
        select.dataset.requiresBatch = '0';
        select.setCustomValidity('');
        setBatchHelp(row, 'Checking batch stock availability...');

        var controller = new AbortController();
        state.controller = controller;

        window.fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            signal: controller.signal
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Batch lookup failed with status ' + response.status);
                }

                return response.json();
            })
            .then(function (payload) {
                renderBatches(
                    row,
                    payload && Array.isArray(payload.results) ? payload.results : [],
                    preferred,
                    Boolean(payload && payload.requires_batch)
                );
            })
            .catch(function (error) {
                if (error.name !== 'AbortError') {
                    resetBatch(row, 'Unable to load batches');
                    setBatchHelp(row, 'Unable to check batch stock. Please retry by selecting the product again.');
                }
            })
            .finally(function () {
                if (state.controller === controller) {
                    state.controller = null;
                }
            });
    }

    function setSelectionValidity(row, valid) {
        if (!row) {
            return;
        }

        var message = valid ? '' : 'Please select a product from the filtered list.';
        row.querySelectorAll('[data-san-product-search]').forEach(function (input) {
            input.setCustomValidity(message);
            input.classList.toggle('san-product-not-selected', !valid && input.value.trim() !== '');
        });
    }

    function closeSearch(input) {
        if (!input) {
            return;
        }

        var state = getSearchState(input);
        var resultsBox = getResultsBox(input);

        if (state.timer) {
            window.clearTimeout(state.timer);
            state.timer = null;
        }

        if (state.controller) {
            state.controller.abort();
            state.controller = null;
        }

        state.items = [];
        state.activeIndex = -1;

        if (resultsBox) {
            resultsBox.innerHTML = '';
            resultsBox.classList.remove('is-open');
            restoreResultsBox(input, resultsBox);
        }

        openSearchInputs.delete(input);
        input.setAttribute('aria-expanded', 'false');
    }

    function closeAllSearches(exceptInput) {
        document.querySelectorAll('[data-san-product-search]').forEach(function (input) {
            if (input !== exceptInput) {
                closeSearch(input);
            }
        });
    }

    function clearSelectedProduct(row, sourceInput) {
        if (!row) {
            return;
        }

        setValue(row, '[data-san-product-id]', '');
        setValue(row, '[data-san-variation-id]', '');
        setValue(row, '[data-san-product-sku]', '');
        setValue(row, '[data-san-system-qty]', '0');
        delete row.dataset.productSystemQty;
        resetBatch(row, 'Select product first');

        var codeInput = row.querySelector('[data-san-product-code]');
        var nameInput = row.querySelector('[data-san-product-name]');
        var counterpart = sourceInput === codeInput ? nameInput : codeInput;

        if (counterpart && counterpart.value === (counterpart.dataset.selectedValue || '')) {
            counterpart.value = '';
        }

        if (codeInput) {
            codeInput.dataset.selectedValue = '';
        }
        if (nameInput) {
            nameInput.dataset.selectedValue = '';
        }

        setSelectionValidity(row, false);
    }

    function buildSearchUrl(form, term, row) {
        var endpoint = form ? form.dataset.productSearchUrl : '';
        if (!endpoint) {
            return null;
        }

        var url = new URL(endpoint, window.location.href);
        url.searchParams.set('q', term || '');
        url.searchParams.set('limit', '25');

        var locationId = valueOf(form, '[data-san-location-id]');
        var storeId = valueOf(form, '[data-san-store-id]');
        var categoryId = valueOf(form, '[data-san-product-category-filter]');
        var subCategoryId = valueOf(form, '[data-san-product-sub-category-filter]');
        var stockAdjustmentType = valueOf(row, '[data-san-line-adjustment-type]');

        if (stockAdjustmentType === 'increase' || stockAdjustmentType === 'decrease') {
            url.searchParams.set('stock_adjustment_type', stockAdjustmentType);
        }

        if (/^\d+$/.test(locationId) && Number(locationId) > 0) {
            url.searchParams.set('location_id', locationId);
        }

        if (/^\d+$/.test(storeId) && Number(storeId) > 0) {
            url.searchParams.set('store_id', storeId);
        }

        if (/^\d+$/.test(categoryId) && Number(categoryId) > 0) {
            url.searchParams.set('category_id', categoryId);
        }

        if (/^\d+$/.test(subCategoryId) && Number(subCategoryId) > 0) {
            url.searchParams.set('sub_category_id', subCategoryId);
        }

        return url.toString();
    }

    function resultCode(product) {
        var parts = ['ID ' + product.product_id];
        if (product.sku) {
            parts.push('SKU ' + product.sku);
        }
        return parts.join(' | ');
    }

    function setActiveResult(input, nextIndex) {
        var state = getSearchState(input);
        var resultsBox = getResultsBox(input);
        var options = resultsBox ? Array.from(resultsBox.querySelectorAll('[data-result-index]')) : [];

        if (!options.length) {
            state.activeIndex = -1;
            return;
        }

        state.activeIndex = Math.max(0, Math.min(nextIndex, options.length - 1));
        options.forEach(function (option, index) {
            var active = index === state.activeIndex;
            option.classList.toggle('is-active', active);
            option.setAttribute('aria-selected', active ? 'true' : 'false');
            if (active) {
                option.scrollIntoView({block: 'nearest'});
            }
        });
    }

    function applyProduct(row, product, preferredBatchNo) {
        if (!row || !product) {
            return;
        }

        var codeInput = setValue(row, '[data-san-product-code]', product.identifier || product.sku || product.product_id);
        var nameInput = setValue(row, '[data-san-product-name]', product.name || product.product_name || '');

        setValue(row, '[data-san-product-id]', product.product_id);
        setValue(row, '[data-san-variation-id]', product.variation_id || '');
        setValue(row, '[data-san-product-sku]', product.sku || '');
        row.dataset.productSystemQty = normaliseNumber(product.system_qty);
        setValue(row, '[data-san-system-qty]', row.dataset.productSystemQty);

        var unitCost = row.querySelector('[data-san-unit-cost]');
        if (unitCost && (unitCost.value.trim() === '' || Number(unitCost.value) === 0)) {
            unitCost.value = normaliseNumber(product.unit_cost);
        }

        if (codeInput) {
            codeInput.dataset.selectedValue = codeInput.value;
        }
        if (nameInput) {
            nameInput.dataset.selectedValue = nameInput.value;
        }

        setSelectionValidity(row, true);
        row.querySelectorAll('[data-san-product-search]').forEach(closeSearch);
        fetchAvailableBatches(row, preferredBatchNo || '');
    }

    function renderResults(input, products, message) {
        var resultsBox = getResultsBox(input);
        var state = getSearchState(input);
        if (!resultsBox) {
            return;
        }

        resultsBox.innerHTML = '';
        state.items = Array.isArray(products) ? products : [];
        state.activeIndex = -1;

        if (!state.items.length) {
            var empty = document.createElement('div');
            empty.className = 'san-product-result-empty';
            empty.textContent = message || 'No matching products found.';
            resultsBox.appendChild(empty);
            openResultsBox(input, resultsBox);
            return;
        }

        state.items.forEach(function (product, index) {
            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'san-product-result';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.dataset.resultIndex = String(index);

            var code = document.createElement('span');
            code.className = 'san-product-result-code';
            code.textContent = resultCode(product);

            var name = document.createElement('span');
            name.className = 'san-product-result-name';
            name.textContent = product.name || product.product_name || '';

            var meta = document.createElement('span');
            meta.className = 'san-product-result-meta';
            meta.textContent = 'Stock: ' + displayNumber(product.system_qty) + ' | Cost: ' + displayNumber(product.unit_cost);

            option.appendChild(code);
            option.appendChild(name);
            option.appendChild(meta);

            option.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });
            option.addEventListener('click', function () {
                applyProduct(getRow(input), product);
            });

            resultsBox.appendChild(option);
        });

        openResultsBox(input, resultsBox);
        setActiveResult(input, 0);
    }

    function fetchProducts(input, term, immediate) {
        var form = getForm(input);
        var state = getSearchState(input);
        var url = buildSearchUrl(form, term, getRow(input));

        if (!url) {
            renderResults(input, [], 'Product search is not configured.');
            return;
        }

        if (state.timer) {
            window.clearTimeout(state.timer);
        }
        if (state.controller) {
            state.controller.abort();
        }

        var execute = function () {
            var controller = new AbortController();
            state.controller = controller;
            renderResults(input, [], 'Searching products...');

            window.fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                signal: controller.signal
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Product search failed with status ' + response.status);
                    }
                    return response.json();
                })
                .then(function (payload) {
                    renderResults(input, payload && Array.isArray(payload.results) ? payload.results : []);
                })
                .catch(function (error) {
                    if (error.name !== 'AbortError') {
                        renderResults(input, [], 'Unable to load products. Please try again.');
                    }
                })
                .finally(function () {
                    if (state.controller === controller) {
                        state.controller = null;
                    }
                    state.timer = null;
                });
        };

        state.timer = window.setTimeout(execute, immediate ? 0 : 250);
    }

    function resetRow(row) {
        if (!row) {
            return;
        }

        row.querySelectorAll('input').forEach(function (input) {
            if (input.matches('[data-san-system-qty], [data-san-counted-qty], [data-san-unit-cost]')) {
                input.value = '0';
            } else {
                input.value = '';
            }
            input.classList.remove('san-product-not-selected');
            input.setCustomValidity('');
            if (input.hasAttribute('data-selected-value')) {
                input.dataset.selectedValue = '';
            }
        });

        row.querySelectorAll('[data-san-product-search]').forEach(closeSearch);

        delete row.dataset.productSystemQty;
        resetBatch(row, 'Select product first');
    }

    function replaceLineIndex(row, index) {
        row.querySelectorAll('[name]').forEach(function (field) {
            field.name = field.name.replace(/lines\[\d+\]/, 'lines[' + index + ']');
        });
    }

    function addLine(button) {
        // Restore any portalled suggestion list before cloning the template row.
        closeAllSearches(null);

        var form = getForm(button);
        var tbody = form ? form.querySelector('#san-lines tbody') : null;
        var firstRow = tbody ? tbody.querySelector('[data-san-line-row]') : null;
        if (!tbody || !firstRow) {
            return;
        }

        var index = Number(form.dataset.nextLineIndex || tbody.querySelectorAll('[data-san-line-row]').length);
        var row = firstRow.cloneNode(true);
        replaceLineIndex(row, index);
        resetRow(row);
        tbody.appendChild(row);
        form.dataset.nextLineIndex = String(index + 1);

        var productCode = row.querySelector('[data-san-product-code]');
        if (productCode) {
            productCode.focus();
        }
    }

    function removeLine(button) {
        var row = getRow(button);
        var tbody = row ? row.parentElement : null;
        if (!row || !tbody) {
            return;
        }

        if (tbody.querySelectorAll('[data-san-line-row]').length === 1) {
            resetRow(row);
            var productCode = row.querySelector('[data-san-product-code]');
            if (productCode) {
                productCode.focus();
            }
            return;
        }

        row.querySelectorAll('[data-san-product-search]').forEach(closeSearch);
        row.remove();
    }

    function refreshSelectedRow(row) {
        var productId = Number(valueOf(row, '[data-san-product-id]'));
        var variationId = Number(valueOf(row, '[data-san-variation-id]')) || null;
        var batchSelect = row.querySelector('[data-san-batch-no]');
        var preferredBatchNo = batchSelect
            ? (batchSelect.value || batchSelect.dataset.selectedValue || '')
            : '';
        var input = row.querySelector('[data-san-product-code]');
        var form = getForm(row);
        var url = input ? buildSearchUrl(form, String(productId), row) : null;

        if (!productId || !url) {
            return;
        }

        window.fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (payload) {
                var products = payload && Array.isArray(payload.results) ? payload.results : [];
                var match = products.find(function (product) {
                    return Number(product.product_id) === productId
                        && (variationId === null || Number(product.variation_id || 0) === variationId);
                });

                if (match) {
                    applyProduct(row, match, preferredBatchNo);
                }
            })
            .catch(function () {
                // Keep the current selection. Server-side validation will still
                // re-resolve the product and stock quantity before saving.
            });
    }

    function refreshSelectedRows(form) {
        if (!form) {
            return;
        }

        if (contextRefreshTimer) {
            window.clearTimeout(contextRefreshTimer);
        }

        contextRefreshTimer = window.setTimeout(function () {
            form.querySelectorAll('[data-san-line-row]').forEach(refreshSelectedRow);
        }, 250);
    }

    document.addEventListener('input', function (event) {
        var searchInput = event.target.closest('[data-san-adjustment-search]');
        if (searchInput) {
            var table = document.querySelector('[data-san-adjustments-table]');
            var term = searchInput.value.trim().toLowerCase();

            if (table) {
                // Use the active DataTables API when the host application has
                // already enhanced this table; otherwise apply a lightweight
                // client-side fallback to the server-rendered rows.
                if (
                    window.jQuery
                    && window.jQuery.fn
                    && window.jQuery.fn.DataTable
                    && window.jQuery.fn.DataTable.isDataTable(table)
                ) {
                    window.jQuery(table).DataTable().search(searchInput.value).draw();
                } else {
                    table.querySelectorAll('tbody tr').forEach(function (row) {
                        row.hidden = term !== '' && row.textContent.toLowerCase().indexOf(term) === -1;
                    });
                }
            }
            return;
        }

        var countedInput = event.target.closest('[data-san-counted-qty]');
        if (countedInput) {
            countedInput.setCustomValidity('');
            return;
        }

        var input = event.target.closest('[data-san-product-search]');
        if (!input) {
            return;
        }

        var row = getRow(input);
        if (input.value !== (input.dataset.selectedValue || '')) {
            clearSelectedProduct(row, input);
        }

        closeAllSearches(input);
        fetchProducts(input, input.value.trim(), false);
    });

    document.addEventListener('focusin', function (event) {
        var input = event.target.closest('[data-san-product-search]');
        if (!input) {
            return;
        }

        closeAllSearches(input);
        fetchProducts(input, input.value.trim(), true);
    });

    document.addEventListener('keydown', function (event) {
        var input = event.target.closest('[data-san-product-search]');
        if (!input) {
            return;
        }

        var state = getSearchState(input);
        var resultsBox = getResultsBox(input);
        var isOpen = resultsBox && resultsBox.classList.contains('is-open');

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (!isOpen) {
                fetchProducts(input, input.value.trim(), true);
            } else {
                setActiveResult(input, state.activeIndex + 1);
            }
        } else if (event.key === 'ArrowUp' && isOpen) {
            event.preventDefault();
            setActiveResult(input, state.activeIndex - 1);
        } else if (event.key === 'Enter' && isOpen && state.items.length) {
            event.preventDefault();
            applyProduct(getRow(input), state.items[Math.max(0, state.activeIndex)]);
        } else if (event.key === 'Escape') {
            event.preventDefault();
            closeSearch(input);
        } else if (event.key === 'Tab') {
            closeSearch(input);
        }
    });

    document.addEventListener('click', function (event) {
        var addButton = event.target.closest('[data-san-add-line]');
        if (addButton) {
            addLine(addButton);
            return;
        }

        var removeButton = event.target.closest('[data-san-remove-line]');
        if (removeButton) {
            removeLine(removeButton);
            return;
        }

        if (
            !event.target.closest('.san-product-lookup')
            && !event.target.closest('.san-product-results-portal')
        ) {
            closeAllSearches(null);
        }
    });

    function refreshCategoryFilterSelect(select) {
        if (!select || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
            return;
        }

        var $select = window.jQuery(select);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.trigger('change.select2');
        }
    }

    function filterCreateSubCategories(form) {
        if (!form) {
            return;
        }

        var category = form.querySelector('[data-san-product-category-filter]');
        var subCategory = form.querySelector('[data-san-product-sub-category-filter]');
        if (!category || !subCategory) {
            return;
        }

        var selectedCategory = category.value;
        Array.prototype.forEach.call(subCategory.options, function (option, index) {
            if (index === 0) {
                return;
            }

            var allowed = !selectedCategory || option.dataset.parentId === selectedCategory;
            option.hidden = !allowed;
            option.disabled = !allowed;
            if (!allowed && option.selected) {
                option.selected = false;
            }
        });

        refreshCategoryFilterSelect(subCategory);
    }

    function initialiseCreateCategoryFilters(form) {
        if (!form) {
            return;
        }

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(form).find('[data-san-static-select2]').each(function () {
                var select = this;
                var $select = window.jQuery(select);
                try {
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }
                } catch (error) {}

                // Remove containers left by any global Select2 initialiser so
                // every dropdown has only one search field.
                var sibling = select.nextElementSibling;
                while (sibling && sibling.classList && sibling.classList.contains('select2-container')) {
                    var stale = sibling;
                    sibling = sibling.nextElementSibling;
                    stale.remove();
                }

                $select.removeClass('select2-hidden-accessible')
                    .removeAttr('data-select2-id aria-hidden tabindex')
                    .removeData('select2')
                    .select2({
                        width: '100%',
                        minimumResultsForSearch: 0
                    });
            });
        }

        filterCreateSubCategories(form);
        filterStoresByLocation(form, true);
    }

    function filterStoresByLocation(form, preserveSelected) {
        if (!form) return;
        var location = form.querySelector('[data-san-location-id]');
        var store = form.querySelector('[data-san-store-id]');
        if (!location || !store) return;

        var locationId = location.value;
        Array.prototype.forEach.call(store.options, function (option, index) {
            if (index === 0) return;
            var optionLocation = String(option.dataset.locationId || '').trim();
            var allowed = !locationId || !optionLocation || optionLocation === locationId;
            if (preserveSelected && option.selected) allowed = true;
            option.hidden = !allowed;
            option.disabled = !allowed;
            if (!preserveSelected && !allowed && option.selected) option.selected = false;
        });

        refreshCategoryFilterSelect(store);
    }

    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-san-batch-no]')) {
            applyBatchSelection(event.target);
            return;
        }

        if (event.target.matches('[data-san-product-category-filter]')) {
            var categoryForm = getForm(event.target);
            filterCreateSubCategories(categoryForm);
            closeAllSearches(null);
            return;
        }

        if (event.target.matches('[data-san-product-sub-category-filter]')) {
            closeAllSearches(null);
            return;
        }

        if (event.target.matches('[data-san-location-id]')) {
            var locationForm = getForm(event.target);
            filterStoresByLocation(locationForm, false);
            refreshSelectedRows(locationForm);
            return;
        }

        if (event.target.matches('[data-san-store-id]')) {
            refreshSelectedRows(getForm(event.target));
            return;
        }

        if (event.target.matches('[data-san-line-adjustment-type]')) {
            var directionRow = getRow(event.target);
            var countedField = directionRow ? directionRow.querySelector('[data-san-counted-qty]') : null;
            if (countedField) {
                countedField.setCustomValidity('');
            }
            closeAllSearches(null);
        }
    });

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-san-adjustment-form]');
        if (!form) {
            return;
        }

        var rows = Array.from(form.querySelectorAll('[data-san-line-row]'));
        rows.slice(1).forEach(function (row) {
            var hasSelection = valueOf(row, '[data-san-product-id]') !== '';
            var hasTypedValue = valueOf(row, '[data-san-product-code]') !== ''
                || valueOf(row, '[data-san-product-name]') !== '';

            if (!hasSelection && !hasTypedValue) {
                row.remove();
            }
        });

        var invalidRow = Array.from(form.querySelectorAll('[data-san-line-row]')).find(function (row) {
            return valueOf(row, '[data-san-product-id]') === '';
        });

        if (invalidRow) {
            event.preventDefault();
            setSelectionValidity(invalidRow, false);
            var input = invalidRow.querySelector('[data-san-product-code]');
            if (input) {
                input.focus();
                input.reportValidity();
            }
            return;
        }

        Array.from(form.querySelectorAll('[data-san-counted-qty]')).forEach(function (field) {
            field.setCustomValidity('');
        });

        var invalidDirectionRow = Array.from(form.querySelectorAll('[data-san-line-row]')).find(function (row) {
            var direction = valueOf(row, '[data-san-line-adjustment-type]');
            var system = Number(valueOf(row, '[data-san-system-qty]') || 0);
            var counted = Number(valueOf(row, '[data-san-counted-qty]') || 0);
            return (direction === 'increase' && counted <= system)
                || (direction === 'decrease' && counted >= system);
        });

        if (invalidDirectionRow) {
            event.preventDefault();
            var direction = valueOf(invalidDirectionRow, '[data-san-line-adjustment-type]');
            var countedField = invalidDirectionRow.querySelector('[data-san-counted-qty]');
            if (countedField) {
                countedField.setCustomValidity(
                    direction === 'decrease'
                        ? 'For a Decrease adjustment, Counted Qty must be lower than System Qty.'
                        : 'For an Increase adjustment, Counted Qty must be greater than System Qty.'
                );
                countedField.focus();
                countedField.reportValidity();
            }
            return;
        }

        var invalidBatchRow = Array.from(form.querySelectorAll('[data-san-line-row]')).find(function (row) {
            var select = row.querySelector('[data-san-batch-no]');
            return select
                && select.dataset.requiresBatch === '1'
                && select.value.trim() === '';
        });

        if (invalidBatchRow) {
            event.preventDefault();
            var batchSelect = invalidBatchRow.querySelector('[data-san-batch-no]');
            batchSelect.setCustomValidity('Please select an available batch number.');
            batchSelect.focus();
            batchSelect.reportValidity();
        }
    });

    window.addEventListener('resize', scheduleOpenResultsPosition);
    window.addEventListener('scroll', scheduleOpenResultsPosition, true);

    document.querySelectorAll('[data-san-adjustment-form]').forEach(initialiseCreateCategoryFilters);
    window.setTimeout(function () {
        document.querySelectorAll('[data-san-adjustment-form]').forEach(initialiseCreateCategoryFilters);
    }, 350);

    document.querySelectorAll('[data-san-line-row]').forEach(function (row) {
        var hasProduct = valueOf(row, '[data-san-product-id]') !== '';
        setSelectionValidity(row, hasProduct);

        if (hasProduct) {
            var batchSelect = row.querySelector('[data-san-batch-no]');
            fetchAvailableBatches(
                row,
                batchSelect ? (batchSelect.dataset.selectedValue || batchSelect.value || '') : ''
            );
        } else {
            resetBatch(row, 'Select product first');
        }
    });
})();
