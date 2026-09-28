(function () {
    'use strict';

    var page = document.querySelector('.pdirectnew-pumper-page');
    if (!page) return;

    var tabBody = document.getElementById('pdirectnew-tab-body');
    var filterForm = document.getElementById('pdirectnew-location-filter');
    var locationSelect = filterForm ? filterForm.querySelector('[name="location_id"]') : null;
    var indexUrl = page.getAttribute('data-index-url');
    var csrfToken = '{{ csrf_token() }}';
    var cache = new Map();
    var loadingController = null;
    var prefetched = new Set();

    function selectedLocation() {
        return locationSelect ? (locationSelect.value || '') : '';
    }

    function key(tab) {
        return tab + '|' + selectedLocation();
    }

    function tabLink(tab) {
        return page.querySelector('[data-pdirectnew-tab="' + tab + '"]');
    }

    function setActive(tab) {
        page.querySelectorAll('[data-pdirectnew-tab]').forEach(function (link) {
            var active = link.getAttribute('data-pdirectnew-tab') === tab;
            link.classList.toggle('active', active);
            link.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    function updateAddress(tab) {
        if (!window.history || !window.history.replaceState) return;
        var url = new URL(indexUrl, window.location.origin);
        url.searchParams.set('tab', tab);
        if (selectedLocation()) url.searchParams.set('location_id', selectedLocation());
        window.history.replaceState({pdirectnewTab: tab}, '', url.toString());
    }

    function showLoader() {
        tabBody.classList.add('pdn-tab-loading');
        tabBody.setAttribute('aria-busy', 'true');
    }

    function hideLoader() {
        tabBody.classList.remove('pdn-tab-loading');
        tabBody.setAttribute('aria-busy', 'false');
    }

    function initializeControls(container) {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(container).find('select.select2').each(function () {
                var $select = window.jQuery(this);
                if ($select.hasClass('select2-hidden-accessible')) return;
                var options = {width: '100%'};
                var modalElement = $select.closest('.pdn-modal');
                if (modalElement.length) options.dropdownParent = modalElement.find('.pdn-modal-dialog').first();
                $select.select2(options);
            });

            window.jQuery(container).find('select[data-pdn-user-picker]').each(function () {
                var $select = window.jQuery(this);
                if ($select.hasClass('select2-hidden-accessible')) return;
                $select.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: 'Search linked user',
                    minimumInputLength: 0,
                    dropdownParent: window.jQuery('#pdn-operator-form-modal .pdn-modal-dialog'),
                    ajax: {
                        url: $select.data('search-url'),
                        dataType: 'json',
                        delay: 180,
                        cache: true,
                        data: function (params) { return {q: params.term || ''}; },
                        processResults: function (payload) { return {results: payload.results || []}; }
                    }
                });
            });
        }
    }

    function endpoint(tab) {
        var link = tabLink(tab);
        if (!link) return null;
        var url = new URL(link.getAttribute('data-tab-url'), window.location.origin);
        if (selectedLocation()) url.searchParams.set('location_id', selectedLocation());
        return url.toString();
    }

    async function fetchTab(tab, force, signal) {
        var cacheKey = key(tab);
        if (!force && cache.has(cacheKey)) return cache.get(cacheKey);

        var url = endpoint(tab);
        if (!url) throw new Error('Tab endpoint is unavailable.');

        var response = await fetch(url, {
            credentials: 'same-origin',
            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            signal: signal
        });

        if (!response.ok) throw new Error('Unable to load this tab (' + response.status + ').');
        var payload = await response.json();
        cache.set(cacheKey, payload.html);
        return payload.html;
    }

    async function loadTab(tab, options) {
        options = options || {};
        setActive(tab);
        var cacheKey = key(tab);

        if (!options.force && cache.has(cacheKey)) {
            tabBody.innerHTML = cache.get(cacheKey);
            tabBody.setAttribute('data-current-tab', tab);
            initializeControls(tabBody);
            updateAddress(tab);
            applyRequestedOperator(options.operatorId);
            return;
        }

        if (loadingController) loadingController.abort();
        loadingController = new AbortController();
        showLoader();

        try {
            var html = await fetchTab(tab, !!options.force, loadingController.signal);
            tabBody.innerHTML = html;
            tabBody.setAttribute('data-current-tab', tab);
            initializeControls(tabBody);
            updateAddress(tab);
            applyRequestedOperator(options.operatorId);
        } catch (error) {
            if (error.name !== 'AbortError') {
                tabBody.innerHTML = '<div class="alert alert-danger"><strong>Unable to load the tab.</strong> ' + escapeHtml(error.message || error) + '</div>';
            }
        } finally {
            hideLoader();
            loadingController = null;
        }
    }

    function applyRequestedOperator(operatorId) {
        if (!operatorId) return;
        tabBody.querySelectorAll('select[name="operator_id"]').forEach(function (select) {
            if (select.querySelector('option[value="' + operatorId + '"]')) {
                select.value = String(operatorId);
                if (window.jQuery) window.jQuery(select).trigger('change');
            }
        });
    }

    function prefetchTabs() {
        var links = Array.prototype.slice.call(page.querySelectorAll('[data-pdirectnew-tab]'));
        var current = tabBody.getAttribute('data-current-tab') || 'operators';
        var tabs = links.map(function (link) { return link.getAttribute('data-pdirectnew-tab'); })
            .filter(function (tab) { return tab !== current && !prefetched.has(key(tab)); });

        var run = function () {
            var tab = tabs.shift();
            if (!tab) return;
            prefetched.add(key(tab));
            fetchTab(tab, false).catch(function () {}).finally(function () { window.setTimeout(run, 75); });
        };

        if ('requestIdleCallback' in window) window.requestIdleCallback(run, {timeout: 1500});
        else window.setTimeout(run, 350);
    }

    function modal(id) {
        return document.getElementById(id);
    }

    function openModal(element) {
        if (!element) return;
        element.classList.add('is-open');
        element.setAttribute('aria-hidden', 'false');
        document.documentElement.classList.add('pdn-modal-open');
        var first = element.querySelector('input:not([type="hidden"]), select, textarea, button');
        if (first) window.setTimeout(function () { first.focus(); }, 20);
    }

    function closeModal(element) {
        if (!element) return;
        element.classList.remove('is-open');
        element.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.pdn-modal.is-open')) document.documentElement.classList.remove('pdn-modal-open');
    }

    function resetOperatorForm() {
        var form = document.getElementById('pdn-operator-form');
        if (!form) return;
        form.reset();
        form.action = form.getAttribute('data-store-url');
        form.querySelector('[name="_method"]').value = 'POST';
        var location = form.querySelector('[name="location_id"]');
        if (location && selectedLocation()) location.value = selectedLocation();
        var userPicker = form.querySelector('[data-pdn-user-picker]');
        if (userPicker) {
            userPicker.innerHTML = '<option value="">Not Linked</option>';
            userPicker.value = '';
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) window.jQuery(userPicker).trigger('change');
        }
        var active = form.querySelector('[name="is_active"][type="checkbox"]');
        if (active) active.checked = true;
        var title = document.getElementById('pdn-operator-modal-title');
        if (title) title.innerHTML = '<i class="fa fa-user-plus"></i> Add Pump Operator';
        setOperatorErrors([]);
    }

    function setOperatorErrors(errors) {
        var box = document.querySelector('.pdn-operator-errors');
        if (!box) return;
        if (!errors || !errors.length) {
            box.hidden = true;
            box.innerHTML = '';
            return;
        }
        box.hidden = false;
        box.innerHTML = '<strong>Please correct the following:</strong><ul>' + errors.map(function (error) {
            return '<li>' + escapeHtml(error) + '</li>';
        }).join('') + '</ul>';
    }

    function setField(form, name, value) {
        var field = form.querySelector('[name="' + name + '"]:not([type="hidden"])');
        if (!field) return;
        if (field.type === 'checkbox') field.checked = !!Number(value);
        else { var text = value === null || typeof value === 'undefined' ? '' : String(value); field.value = field.type === 'date' ? text.substring(0, 10) : text; }
    }

    function fillOperatorForm(operator, updateUrl) {
        var form = document.getElementById('pdn-operator-form');
        if (!form) return;
        resetOperatorForm();
        form.action = updateUrl;
        form.querySelector('[name="_method"]').value = 'PUT';
        [
            'location_id', 'user_id', 'operator_no', 'name', 'address', 'mobile', 'landline',
            'dob', 'nic', 'email', 'username', 'opening_balance', 'commission_type',
            'commission_value', 'short_amount', 'excess_amount', 'transaction_date',
            'can_login', 'is_default', 'can_fullscreen',
            'hide_in_direct_settlement_if_pending_shifts', 'is_active'
        ].forEach(function (name) { setField(form, name, operator[name]); });
        var userPicker = form.querySelector('[data-pdn-user-picker]');
        if (userPicker && operator.user_id) {
            var label = operator.linked_user_name || operator.linked_username || operator.linked_email || ('User ' + operator.user_id);
            userPicker.innerHTML = '<option value="">Not Linked</option><option value="' + escapeHtml(operator.user_id) + '" selected>' + escapeHtml(label) + '</option>';
            userPicker.value = String(operator.user_id);
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) window.jQuery(userPicker).trigger('change');
        }
        var title = document.getElementById('pdn-operator-modal-title');
        if (title) title.innerHTML = '<i class="fa fa-pencil-square-o"></i> Edit Pump Operator';
    }

    function operatorViewHtml(operator) {
        var values = [
            ['Operator No.', operator.operator_no], ['Name', operator.name], ['Location', operator.location_name],
            ['Mobile', operator.mobile], ['Landline', operator.landline], ['NIC / CNIC', operator.nic],
            ['Date of Birth', formatDate(operator.dob)], ['Email', operator.email || operator.linked_email],
            ['Username', operator.username || operator.linked_username], ['Linked User', operator.linked_user_name],
            ['Address', operator.address], ['Commission Type', operator.commission_type],
            ['Commission Value', formatNumber(operator.commission_value)], ['Opening Balance', formatNumber(operator.opening_balance)],
            ['Shortage Amount', formatNumber(operator.short_amount)], ['Excess Amount', formatNumber(operator.excess_amount)],
            ['Dashboard Login', Number(operator.can_login) ? 'Enabled' : 'Disabled'],
            ['Fullscreen', Number(operator.can_fullscreen) ? 'Yes' : 'No'],
            ['Status', Number(operator.is_active) ? 'Active' : 'Inactive'],
            ['Last Synchronized', operator.source_updated_at || operator.updated_at]
        ];
        return '<div class="pdn-operator-detail-grid">' + values.map(function (item) {
            return '<div><span>' + escapeHtml(item[0]) + '</span><strong>' + escapeHtml(item[1] || '—') + '</strong></div>';
        }).join('') + '</div>';
    }

    function formatNumber(value) {
        var number = Number(value || 0);
        return number.toLocaleString(undefined, {minimumFractionDigits: 4, maximumFractionDigits: 4});
    }

    function formatDate(value) {
        if (!value) return '—';
        return String(value).substring(0, 10);
    }

    function escapeHtml(value) {
        return String(value === null || typeof value === 'undefined' ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    async function requestJson(url, options) {
        options = options || {};
        options.credentials = 'same-origin';
        options.headers = Object.assign({'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, options.headers || {});
        var response = await fetch(url, options);
        var payload = {};
        try { payload = await response.json(); } catch (e) {}
        if (!response.ok) {
            var error = new Error(payload.message || 'The operation could not be completed (' + response.status + ').');
            error.payload = payload;
            throw error;
        }
        return payload;
    }

    function notify(type, message) {
        if (window.toastr && window.toastr[type]) window.toastr[type](message);
        else if (type === 'error') window.alert(message);
    }

    page.addEventListener('click', async function (event) {
        var link = event.target.closest('[data-pdirectnew-tab]');
        if (link) {
            event.preventDefault();
            loadTab(link.getAttribute('data-pdirectnew-tab'));
            return;
        }

        var clear = event.target.closest('#pdirectnew-clear-filter');
        if (clear) {
            event.preventDefault();
            if (locationSelect) locationSelect.value = '';
            cache.clear();
            prefetched.clear();
            loadTab(tabBody.getAttribute('data-current-tab') || 'operators', {force: true});
            return;
        }

        var close = event.target.closest('[data-pdn-close-modal]');
        if (close) {
            event.preventDefault();
            closeModal(close.closest('.pdn-modal'));
            return;
        }

        var genericOpen = event.target.closest('[data-pdn-open-modal]');
        if (genericOpen) {
            event.preventDefault();
            var genericModal = modal(genericOpen.getAttribute('data-pdn-open-modal'));
            openModal(genericModal);
            initializeControls(genericModal);
            return;
        }

        var add = event.target.closest('[data-pdn-open-operator-modal="add"]');
        if (add) {
            event.preventDefault();
            resetOperatorForm();
            openModal(modal('pdn-operator-form-modal'));
            initializeControls(modal('pdn-operator-form-modal'));
            return;
        }

        var view = event.target.closest('[data-pdn-view-operator]');
        if (view) {
            event.preventDefault();
            try {
                var viewPayload = await requestJson(view.getAttribute('data-pdn-view-operator'));
                var viewBody = document.getElementById('pdn-operator-view-body');
                if (viewBody) viewBody.innerHTML = operatorViewHtml(viewPayload.operator);
                openModal(modal('pdn-operator-view-modal'));
            } catch (error) { notify('error', error.message); }
            return;
        }

        var edit = event.target.closest('[data-pdn-edit-operator]');
        if (edit) {
            event.preventDefault();
            try {
                var editPayload = await requestJson(edit.getAttribute('data-pdn-edit-operator'));
                fillOperatorForm(editPayload.operator, edit.getAttribute('data-update-url'));
                openModal(modal('pdn-operator-form-modal'));
                initializeControls(modal('pdn-operator-form-modal'));
            } catch (error) { notify('error', error.message); }
            return;
        }

        var toggle = event.target.closest('[data-pdn-toggle-operator]');
        if (toggle) {
            event.preventDefault();
            var toggleData = new URLSearchParams({_token: csrfToken});
            try {
                var togglePayload = await requestJson(toggle.getAttribute('data-pdn-toggle-operator'), {
                    method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body: toggleData.toString()
                });
                cache.delete(key('operators'));
                await loadTab('operators', {force: true});
                notify('success', togglePayload.message);
            } catch (error) { notify('error', error.message); }
            return;
        }

        var remove = event.target.closest('[data-pdn-delete-operator]');
        if (remove) {
            event.preventDefault();
            if (!window.confirm('Deactivate this Pump Operator? Historical records will be preserved.')) return;
            var deleteData = new URLSearchParams({_token: csrfToken, _method: 'DELETE'});
            try {
                var deletePayload = await requestJson(remove.getAttribute('data-pdn-delete-operator'), {
                    method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body: deleteData.toString()
                });
                cache.delete(key('operators'));
                await loadTab('operators', {force: true});
                notify('success', deletePayload.message);
            } catch (error) { notify('error', error.message); }
            return;
        }

        var jump = event.target.closest('[data-pdn-jump-tab]');
        if (jump) {
            event.preventDefault();
            loadTab(jump.getAttribute('data-pdn-jump-tab'), {operatorId: jump.getAttribute('data-operator-id')});
        }
    });

    page.addEventListener('input', function (event) {
        if (event.target.id !== 'pdn-operator-search') return;
        var term = event.target.value.trim().toLowerCase();
        var visible = 0;
        tabBody.querySelectorAll('[data-pdn-operator-row]').forEach(function (row) {
            var show = !term || (row.getAttribute('data-search') || '').indexOf(term) !== -1;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        var count = document.getElementById('pdn-operator-visible-count');
        if (count) count.textContent = String(visible);
    });

    function collectValidationErrors(error) {
        var errors = [];
        if (error && error.payload && error.payload.errors) {
            Object.keys(error.payload.errors).forEach(function (field) {
                errors = errors.concat(error.payload.errors[field]);
            });
        }
        return errors;
    }

    function setFormErrors(box, errors) {
        if (!box) return;
        if (!errors || !errors.length) {
            box.hidden = true;
            box.innerHTML = '';
            return;
        }
        box.hidden = false;
        box.innerHTML = '<strong>Please correct the following:</strong><ul>' + errors.map(function (item) {
            return '<li>' + escapeHtml(item) + '</li>';
        }).join('') + '</ul>';
    }

    page.addEventListener('submit', async function (event) {
        var operatorForm = event.target.closest('#pdn-operator-form');
        if (operatorForm) {
            event.preventDefault();
            setOperatorErrors([]);
            var save = document.getElementById('pdn-operator-save');
            var original = save ? save.innerHTML : '';
            if (save) { save.disabled = true; save.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving'; }
            try {
                var payload = await requestJson(operatorForm.action, {method: 'POST', body: new FormData(operatorForm)});
                closeModal(modal('pdn-operator-form-modal'));
                cache.delete(key('operators'));
                await loadTab('operators', {force: true});
                notify('success', payload.message);
            } catch (error) {
                var validationErrors = collectValidationErrors(error);
                setOperatorErrors(validationErrors.length ? validationErrors : [error.message]);
            } finally {
                if (save) { save.disabled = false; save.innerHTML = original; }
            }
            return;
        }

        var form = event.target.closest('[data-pdn-ajax-form]');
        if (!form) return;
        event.preventDefault();
        var confirmation = form.getAttribute('data-confirm');
        if (confirmation && !window.confirm(confirmation)) return;

        var submit = form.querySelector('[type="submit"]');
        var submitOriginal = submit ? submit.innerHTML : '';
        var errorBox = form.querySelector('.pdn-form-errors');
        setFormErrors(errorBox, []);
        if (submit) { submit.disabled = true; submit.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving'; }

        try {
            var result = await requestJson(form.action, {method: 'POST', body: new FormData(form)});
            var parentModal = form.closest('.pdn-modal');
            if (parentModal) closeModal(parentModal);
            var currentTab = tabBody.getAttribute('data-current-tab') || 'operators';
            cache.delete(key(currentTab));
            await loadTab(currentTab, {force: true});
            notify('success', result.message || 'Saved successfully.');
        } catch (error) {
            var formErrors = collectValidationErrors(error);
            setFormErrors(errorBox, formErrors.length ? formErrors : [error.message]);
            if (!errorBox) notify('error', error.message);
        } finally {
            if (submit) { submit.disabled = false; submit.innerHTML = submitOriginal; }
        }
    });

    if (filterForm) {
        filterForm.addEventListener('submit', function (event) {
            event.preventDefault();
            cache.clear();
            prefetched.clear();
            loadTab(tabBody.getAttribute('data-current-tab') || 'operators', {force: true});
            prefetchTabs();
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') document.querySelectorAll('.pdn-modal.is-open').forEach(closeModal);
    });

    cache.set(key(tabBody.getAttribute('data-current-tab') || 'operators'), tabBody.innerHTML);
    initializeControls(tabBody);
    prefetchTabs();
})();
