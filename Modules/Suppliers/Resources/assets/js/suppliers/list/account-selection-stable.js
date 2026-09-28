/*
 * Supplier > Actions > Pay Due Amount / Advance Payment
 * Accounting Module account loader + stable selection - 17 Sep 2026 v3.
 *
 * Goals:
 * 1. Preload the account option lists while the Payment Method control is being
 *    opened, so connected Accounting Module accounts appear immediately when a
 *    method is selected.
 * 2. Keep the user's explicit account selection authoritative for the same
 *    Payment Method + Business Location even if a late shared AJAX response
 *    rebuilds the select.
 *
 * This remains Supplier-page-only. It reuses the ERP's canonical Finance
 * account endpoint and does not duplicate account-group rules in this module.
 */
(function ($, window, document) {
    'use strict';

    if (!$) {
        return;
    }

    var MODAL = '.pay_contact_due_modal';
    var ROW = '.payment_row, .payment-row';
    var METHOD = '.payment_types_dropdown';
    var ACCOUNT = '.account_id';
    var LOCATION = '.location_id, #location_id, #pmt_location_id';
    var ENDPOINT = '/finance/get-account-group-name-dp';
    var LEGACY_ENDPOINT = '/accounting-module/get-account-group-name-dp';
    var STATE_KEY = 'supplier-account-selection-state-v3';
    var OBSERVER_KEY = 'supplier-account-selection-observer-v3';
    var USER_INTENT_KEY = 'supplier-account-user-intent-v3';
    var NATIVE_CAPTURE_FLAG = '__supplierAccountStableNativeCaptureV3';
    var CACHE_PREFIX = 'supplier-account-options-v3:';
    var CACHE_TTL = 120000; // 2 minutes: instant UI without keeping stale account maps for long.
    var optionCache = {};
    var pendingCache = {};
    var restoreTimers = [];

    function stringValue(value) {
        return $.trim(String(value == null ? '' : value));
    }

    function isAccountRefreshUrl(url) {
        url = stringValue(url);
        return url.indexOf(ENDPOINT) !== -1 || url.indexOf(LEGACY_ENDPOINT) !== -1;
    }

    function currentMethod($row) {
        return stringValue($row.find(METHOD).first().val()).toLowerCase();
    }

    function currentLocation($row) {
        var $location = $row.find(LOCATION).first();
        if (!$location.length) {
            $location = $row.closest(MODAL).find(LOCATION).first();
        }
        return stringValue($location.val());
    }

    function cacheKey(locationId, method) {
        return CACHE_PREFIX + stringValue(locationId) + ':' + stringValue(method).toLowerCase();
    }

    function storageGet(key) {
        try {
            var raw = window.sessionStorage ? window.sessionStorage.getItem(key) : null;
            if (!raw) {
                return null;
            }
            var parsed = JSON.parse(raw);
            if (!parsed || !parsed.html || !parsed.at || (Date.now() - Number(parsed.at)) > CACHE_TTL) {
                if (window.sessionStorage) {
                    window.sessionStorage.removeItem(key);
                }
                return null;
            }
            return parsed;
        } catch (ignore) {
            return null;
        }
    }

    function storageSet(key, entry) {
        try {
            if (window.sessionStorage) {
                window.sessionStorage.setItem(key, JSON.stringify(entry));
            }
        } catch (ignore) {
            // sessionStorage may be unavailable or full; the in-memory cache is enough.
        }
    }

    function getCachedOptions(locationId, method) {
        var key = cacheKey(locationId, method);
        var entry = optionCache[key];

        if (entry && entry.html && (Date.now() - Number(entry.at || 0)) <= CACHE_TTL) {
            return entry.html;
        }

        entry = storageGet(key);
        if (entry) {
            optionCache[key] = entry;
            return entry.html;
        }

        return '';
    }

    function setCachedOptions(locationId, method, html) {
        html = String(html || '');
        if (!html) {
            return;
        }

        var key = cacheKey(locationId, method);
        var entry = {html: html, at: Date.now()};
        optionCache[key] = entry;
        storageSet(key, entry);
    }

    function fetchOptions(locationId, method) {
        locationId = stringValue(locationId);
        method = stringValue(method).toLowerCase();

        if (!locationId || !method) {
            return $.Deferred().reject().promise();
        }

        var cached = getCachedOptions(locationId, method);
        if (cached) {
            return $.Deferred().resolve(cached).promise();
        }

        var key = cacheKey(locationId, method);
        if (pendingCache[key]) {
            return pendingCache[key];
        }

        pendingCache[key] = $.ajax({
            method: 'GET',
            url: ENDPOINT,
            data: {group_name: method, location_id: locationId},
            dataType: 'html',
            cache: true
        }).done(function (html) {
            setCachedOptions(locationId, method, html);
        }).always(function () {
            delete pendingCache[key];
        });

        return pendingCache[key];
    }

    function rowState($row) {
        var state = $row.data(STATE_KEY);
        if (!state || typeof state !== 'object') {
            state = {
                method: currentMethod($row),
                locationId: currentLocation($row),
                accountId: '',
                accountText: '',
                userSelected: false,
                restoring: false,
                pendingRefreshes: 0
            };
            $row.data(STATE_KEY, state);
        }
        return state;
    }

    function accountOption($account, accountId) {
        accountId = stringValue(accountId);
        if (!accountId || !$account.length) {
            return $();
        }

        return $account.find('option').filter(function () {
            return stringValue(this.value) === accountId;
        }).first();
    }

    function syncPreviousAccount($row, accountId) {
        var $previous = $row.find('.previous_account').first();
        if ($previous.length) {
            $previous.val(accountId);
        }
    }

    function syncSelect2Display($account) {
        if ($account.length && $account.hasClass('select2-hidden-accessible')) {
            $account.trigger('change.select2');
        }
    }

    function stateStillMatchesRow($row, state) {
        return state.method === currentMethod($row) && state.locationId === currentLocation($row);
    }

    function selectedText($account, explicitText) {
        explicitText = stringValue(explicitText);
        if (explicitText) {
            return explicitText;
        }
        return stringValue($account.find('option:selected').first().text());
    }

    function restoreRow($row) {
        if (!$row || !$row.length || !document.documentElement.contains($row.get(0))) {
            return;
        }

        var $account = $row.find(ACCOUNT).first();
        if (!$account.length) {
            return;
        }

        var state = rowState($row);
        if (!state.userSelected || !state.accountId || !stateStillMatchesRow($row, state)) {
            return;
        }

        var $option = accountOption($account, state.accountId);
        if (!$option.length) {
            $account.append(new Option(state.accountText || ('Account #' + state.accountId), state.accountId, true, true));
        }

        if (stringValue($account.val()) !== state.accountId) {
            state.restoring = true;
            $account.val(state.accountId).prop('disabled', false);
            syncPreviousAccount($row, state.accountId);
            syncSelect2Display($account);
            state.restoring = false;
            $row.data(STATE_KEY, state);
        }
    }

    function clearTimers() {
        while (restoreTimers.length) {
            window.clearTimeout(restoreTimers.pop());
        }
    }

    function scheduleRestore($row) {
        [0, 10, 35, 90, 180, 350, 700, 1200, 2000].forEach(function (delay) {
            restoreTimers.push(window.setTimeout(function () {
                restoreRow($row);
            }, delay));
        });
    }

    function rememberUserSelection($account, explicitId, explicitText) {
        if (!$account || !$account.length) {
            return;
        }

        var $row = $account.closest(ROW);
        if (!$row.length) {
            return;
        }

        var accountId = stringValue(explicitId || $account.val());
        if (!accountId) {
            return;
        }

        var state = rowState($row);
        state.method = currentMethod($row);
        state.locationId = currentLocation($row);
        state.accountId = accountId;
        state.accountText = selectedText($account, explicitText);
        state.userSelected = true;
        state.restoring = false;
        $row.data(STATE_KEY, state);

        syncPreviousAccount($row, accountId);
        scheduleRestore($row);
    }

    function resetSelectionForContextChange($row) {
        var state = rowState($row);
        var method = currentMethod($row);
        var locationId = currentLocation($row);

        if (state.method === method && state.locationId === locationId) {
            return false;
        }

        state.method = method;
        state.locationId = locationId;
        state.accountId = '';
        state.accountText = '';
        state.userSelected = false;
        state.restoring = false;
        $row.data(STATE_KEY, state);
        return true;
    }

    function applyCachedForCurrent($row) {
        var method = currentMethod($row);
        var locationId = currentLocation($row);
        var html = getCachedOptions(locationId, method);
        var $account = $row.find(ACCOUNT).first();

        if (!method || !locationId || !html || !$account.length) {
            return false;
        }

        var state = rowState($row);
        var currentValue = stringValue($account.val());
        var previousValue = stringValue($row.find('.previous_account').first().val());
        var preferred = state.userSelected && stateStillMatchesRow($row, state)
            ? state.accountId
            : (currentValue || previousValue);
        var preferredText = state.userSelected ? state.accountText : '';

        $account.html(html).prop('disabled', false);

        if (preferred && !accountOption($account, preferred).length && state.userSelected) {
            $account.append(new Option(preferredText || ('Account #' + preferred), preferred, true, true));
        }

        if (preferred && accountOption($account, preferred).length) {
            $account.val(preferred);
        } else {
            var selected = stringValue($account.find('option:selected').val());
            if (!selected) {
                selected = stringValue($account.find('option[value!=""]').first().val());
                if (selected) {
                    $account.val(selected);
                }
            }
        }

        syncSelect2Display($account);
        return true;
    }

    function methodValues($row) {
        var values = [];
        $row.find(METHOD).first().find('option').each(function () {
            var value = stringValue(this.value).toLowerCase();
            if (value && values.indexOf(value) === -1) {
                values.push(value);
            }
        });
        return values;
    }

    function prefetchRow($row) {
        var locationId = currentLocation($row);
        if (!locationId) {
            return;
        }

        methodValues($row).slice(0, 24).forEach(function (method) {
            fetchOptions(locationId, method);
        });
    }

    function bindRow($row) {
        if (!$row.length) {
            return;
        }

        var $account = $row.find(ACCOUNT).first();
        if (!$account.length) {
            return;
        }

        rowState($row);

        var oldObserver = $row.data(OBSERVER_KEY);
        if (oldObserver && typeof oldObserver.disconnect === 'function') {
            oldObserver.disconnect();
        }

        if (typeof window.MutationObserver === 'function') {
            var observer = new window.MutationObserver(function (mutations) {
                var changed = mutations.some(function (mutation) {
                    return mutation.type === 'childList' &&
                        (mutation.target === $account.get(0) || $.contains($account.get(0), mutation.target));
                });

                if (changed) {
                    scheduleRestore($row);
                }
            });

            observer.observe($account.get(0), {childList: true, subtree: true});
            $row.data(OBSERVER_KEY, observer);
        }
    }

    function bindModal($modal) {
        if (!$modal.length) {
            return;
        }
        $modal.find(ROW).each(function () {
            var $row = $(this);
            bindRow($row);
            prefetchRow($row);
            applyCachedForCurrent($row);
        });
    }

    if (!window[NATIVE_CAPTURE_FLAG]) {
        document.addEventListener('change', function (event) {
            var target = event.target;
            if (!target || !target.matches || !target.matches(MODAL + ' ' + ACCOUNT)) {
                return;
            }
            if (event.isTrusted && stringValue(target.value)) {
                rememberUserSelection($(target));
            }
        }, true);
        window[NATIVE_CAPTURE_FLAG] = true;
    }

    $(document)
        .off('.supplierAccountStableV3')
        .on('pointerdown.supplierAccountStableV3 focusin.supplierAccountStableV3', MODAL + ' ' + METHOD, function () {
            prefetchRow($(this).closest(ROW));
        })
        .on('pointerdown.supplierAccountStableV3 mousedown.supplierAccountStableV3 keydown.supplierAccountStableV3', MODAL + ' ' + ACCOUNT, function () {
            $(this).data(USER_INTENT_KEY, true);
        })
        .on('select2:opening.supplierAccountStableV3 select2:selecting.supplierAccountStableV3', MODAL + ' ' + ACCOUNT, function () {
            $(this).data(USER_INTENT_KEY, true);
        })
        .on('select2:select.supplierAccountStableV3', MODAL + ' ' + ACCOUNT, function (event) {
            var data = event && event.params ? event.params.data : null;
            rememberUserSelection($(this), data && data.id != null ? data.id : null, data && data.text != null ? data.text : null);
            $(this).removeData(USER_INTENT_KEY);
        })
        .on('change.supplierAccountStableV3', MODAL + ' ' + ACCOUNT, function (event) {
            var $account = $(this);
            var $row = $account.closest(ROW);
            var state = rowState($row);
            var userIntent = $account.data(USER_INTENT_KEY) === true;
            var trusted = !!(event.originalEvent && event.originalEvent.isTrusted);
            var value = stringValue($account.val());

            $account.removeData(USER_INTENT_KEY);
            if (userIntent || trusted) {
                if (value) {
                    rememberUserSelection($account);
                }
                return;
            }
            if (!state.restoring && state.userSelected && value !== state.accountId) {
                scheduleRestore($row);
            }
        })
        .on('change.supplierAccountStableV3', MODAL + ' ' + METHOD, function () {
            var $row = $(this).closest(ROW);
            resetSelectionForContextChange($row);
            // Shared app.js starts its normal request first; this immediately
            // restores the already-prefetched list in the same event turn.
            applyCachedForCurrent($row);
            prefetchRow($row);
        })
        .on('change.supplierAccountStableV3', MODAL + ' ' + LOCATION, function () {
            var $row = $(this).closest(ROW);
            if (!$row.length) {
                $row = $(this).closest(MODAL).find(ROW).first();
            }
            resetSelectionForContextChange($row);
            prefetchRow($row);
            applyCachedForCurrent($row);
        })
        .on('shown.bs.modal.supplierAccountStableV3', MODAL, function () {
            var $modal = $(this);
            bindModal($modal);
            $modal.find(ROW).each(function () {
                scheduleRestore($(this));
            });
        })
        .on('hidden.bs.modal.supplierAccountStableV3', MODAL, function () {
            clearTimers();
            $(this).find(ROW).each(function () {
                var $row = $(this);
                var observer = $row.data(OBSERVER_KEY);
                if (observer && typeof observer.disconnect === 'function') {
                    observer.disconnect();
                }
                $row.removeData(OBSERVER_KEY).removeData(STATE_KEY);
            });
        });

    $(document)
        .off('ajaxSend.supplierAccountStableV3 ajaxSuccess.supplierAccountStableV3 ajaxComplete.supplierAccountStableV3')
        .on('ajaxSend.supplierAccountStableV3', function (event, xhr, settings) {
            if (!isAccountRefreshUrl(settings && settings.url)) {
                return;
            }
            $(MODAL + ':visible').find(ROW).each(function () {
                var $row = $(this);
                var state = rowState($row);
                state.pendingRefreshes = Math.max(0, Number(state.pendingRefreshes || 0)) + 1;
                $row.data(STATE_KEY, state);
            });
        })
        .on('ajaxSuccess.supplierAccountStableV3', function (event, xhr, settings, response) {
            if (!isAccountRefreshUrl(settings && settings.url)) {
                return;
            }

            var data = settings && settings.data ? settings.data : {};
            var method = '';
            var locationId = '';
            if (typeof data === 'string') {
                try {
                    var params = new URLSearchParams(data);
                    method = params.get('group_name') || '';
                    locationId = params.get('location_id') || '';
                } catch (ignore) {}
            } else {
                method = data.group_name || '';
                locationId = data.location_id || '';
            }
            if (method && locationId && typeof response === 'string') {
                setCachedOptions(locationId, method, response);
            }
        })
        .on('ajaxComplete.supplierAccountStableV3', function (event, xhr, settings) {
            if (!isAccountRefreshUrl(settings && settings.url)) {
                return;
            }
            $(MODAL + ':visible').find(ROW).each(function () {
                var $row = $(this);
                var state = rowState($row);
                state.pendingRefreshes = Math.max(0, Number(state.pendingRefreshes || 0) - 1);
                $row.data(STATE_KEY, state);
                bindRow($row);
                applyCachedForCurrent($row);
                scheduleRestore($row);
            });
        });
})(window.jQuery, window, document);
