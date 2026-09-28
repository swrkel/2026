(function (root, factory) {
    'use strict';

    var api = factory(root);

    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    }

    if (root && root.jQuery && root.document) {
        api.install(root.jQuery, root.document, root.MutationObserver);
    }
})(typeof window !== 'undefined' ? window : null, function (root) {
    'use strict';

    function normalizedKey(value) {
        var key = String(value || '').trim().toLowerCase();
        var bracketKeys = key.match(/\[([^\]]+)\]/g);
        if (bracketKeys && bracketKeys.length) {
            key = bracketKeys[bracketKeys.length - 1].replace(/^\[|\]$/g, '');
        }

        return key.replace(/\[\]$/, '');
    }

    function isLocationKey(value) {
        var key = normalizedKey(value);

        return key === 'location'
            || key === 'business_location'
            || key === 'business_location_id'
            || key === 'location_id'
            || /(^|_)location_id$/.test(key);
    }

    function isLocationSelect(element) {
        if (!element || String(element.tagName || '').toUpperCase() !== 'SELECT') {
            return false;
        }
        if (element.multiple || element.type === 'select-multiple') {
            return false;
        }
        if (element.getAttribute && element.getAttribute('data-location-dropdown-rule') === 'off') {
            return false;
        }

        return isLocationKey(element.name) || isLocationKey(element.id);
    }

    function firstSelectableValue(element) {
        if (!element || !element.options) {
            return null;
        }

        for (var index = 0; index < element.options.length; index += 1) {
            var option = element.options[index];
            var value = String(option.value == null ? '' : option.value).trim();
            if (value !== '' && value !== '0' && !option.disabled) {
                return value;
            }
        }

        return null;
    }

    function applyInitialDefault(element) {
        if (!isLocationSelect(element) || element.disabled) {
            return false;
        }
        var currentValue = String(element.value == null ? '' : element.value).trim();
        if (currentValue !== '' && currentValue !== '0') {
            return false;
        }

        var firstValue = firstSelectableValue(element);
        if (firstValue === null) {
            return false;
        }

        element.value = firstValue;
        if (element.setAttribute) {
            element.setAttribute('data-location-default-applied', '1');
        }

        return true;
    }

    function assignedLocations() {
        var config = root && root.erpLocationDropdownConfig;
        return config && Array.isArray(config.locations) ? config.locations : [];
    }

    function shouldEnforceBusinessBoundary() {
        var config = root && root.erpLocationDropdownConfig;

        return !!(config && config.enforceBusinessBoundary);
    }

    function allowedLocationIds(locations) {
        var allowed = Object.create(null);
        (Array.isArray(locations) ? locations : []).forEach(function (location) {
            var id = String(location && location.id != null ? location.id : '').trim();
            if (id) {
                allowed[id] = true;
            }
        });

        return allowed;
    }

    /**
     * Server-rendered options are not automatically trustworthy because a
     * legacy module may have loaded business_locations without the shared
     * Eloquent scope. Remove every real location that is not present in the
     * authenticated business/user allow-list. Empty and zero values are kept
     * because they are placeholders such as "Please select" or "All".
     */
    function pruneDisallowedLocations(element, locations) {
        if (!shouldEnforceBusinessBoundary()
            || !isLocationSelect(element)
            || !element.options) {
            return false;
        }

        var allowed = allowedLocationIds(locations);
        var changed = false;

        for (var index = element.options.length - 1; index >= 0; index -= 1) {
            var option = element.options[index];
            var value = String(option.value == null ? '' : option.value).trim();

            if (value !== '' && value !== '0' && !allowed[value]) {
                element.remove(index);
                changed = true;
            }
        }

        return changed;
    }

    function createOption(element, location) {
        var option;
        var documentObject = element && (element.ownerDocument || (root && root.document));
        if (documentObject && typeof documentObject.createElement === 'function') {
            option = documentObject.createElement('option');
            option.value = String(location.id);
            option.textContent = String(location.name);
        } else {
            option = {
                value: String(location.id),
                text: String(location.name),
                textContent: String(location.name),
                disabled: false,
                selected: false,
            };
        }

        return option;
    }

    function hydrateAssignedLocations(element, locations) {
        if (!isLocationSelect(element) || !element.options || !Array.isArray(locations)) {
            return false;
        }

        // Existing server-rendered options are authoritative and can carry
        // price-group, printer and payment-account metadata. Populate only an
        // actually empty location select.
        if (firstSelectableValue(element) !== null) {
            return false;
        }

        var existing = Object.create(null);
        Array.prototype.forEach.call(element.options, function (option) {
            existing[String(option.value == null ? '' : option.value)] = true;
        });

        var added = false;
        locations.forEach(function (location) {
            var id = String(location && location.id != null ? location.id : '').trim();
            if (!id || existing[id]) {
                return;
            }
            element.appendChild(createOption(element, {
                id: id,
                name: location.name == null ? id : location.name,
            }));
            existing[id] = true;
            added = true;
        });

        return added;
    }

    function locationSelect2Options(options) {
        var result = {};
        Object.keys(options && typeof options === 'object' ? options : {}).forEach(function (key) {
            result[key] = options[key];
        });
        result.minimumResultsForSearch = 0;
        result.width = result.width || '100%';

        return result;
    }

    function isSelect2Initialized(element) {
        return !!element && (
            (element.classList && element.classList.contains('select2-hidden-accessible'))
            || (' ' + String(element.className || '') + ' ').indexOf(' select2-hidden-accessible ') !== -1
        );
    }

    function install($, documentObject, MutationObserverConstructor) {
        if (!$ || !$.fn || !$.fn.select2 || !documentObject) {
            return;
        }

        var originalSelect2 = $.fn.select2;
        if (!originalSelect2.__erpLocationDropdownWrapped) {
            var wrappedSelect2 = function () {
                var args = Array.prototype.slice.call(arguments);
                if (typeof args[0] === 'string') {
                    return originalSelect2.apply(this, args);
                }

                var $selects = this.filter(function () {
                    return String(this.tagName || '').toUpperCase() === 'SELECT';
                });
                var $other = $selects.filter(function () {
                    return !isLocationSelect(this);
                });
                if ($other.length) {
                    originalSelect2.apply($other, args);
                }

                $selects.filter(function () {
                    return isLocationSelect(this) && !isSelect2Initialized(this);
                }).each(function () {
                    var options = locationSelect2Options(args[0]);
                    var $element = $(this);
                    var $modal = $element.closest('.modal');
                    if ($modal.length && !options.dropdownParent) {
                        options.dropdownParent = $modal;
                    }
                    originalSelect2.call($element, options);
                });

                return this;
            };

            Object.keys(originalSelect2).forEach(function (property) {
                wrappedSelect2[property] = originalSelect2[property];
            });
            wrappedSelect2.__erpLocationDropdownWrapped = true;
            wrappedSelect2.__erpLocationDropdownOriginal = originalSelect2;
            $.fn.select2 = wrappedSelect2;
        }

        function locationSelectsWithin(node) {
            var $node = $(node);
            var $selects = $node.is('select') ? $node : $node.find('select');
            return $selects.filter(function () {
                return isLocationSelect(this);
            });
        }

        function prepare(node, initialize) {
            var changed = [];
            locationSelectsWithin(node).each(function () {
                var element = this;
                var $element = $(element);
                $element.addClass('select2 erp-location-dropdown')
                    .attr('data-location-dropdown-rule', 'active');

                var locations = assignedLocations();
                pruneDisallowedLocations(element, locations);
                hydrateAssignedLocations(element, locations);
                if (applyInitialDefault(element)) {
                    changed.push(element);
                }

                if (initialize && !isSelect2Initialized(element)) {
                    $element.select2();
                }
            });

            if (changed.length) {
                root.setTimeout(function () {
                    $(changed).trigger('change');
                }, 0);
            }
        }

        $(function () {
            prepare(documentObject, true);

            $(documentObject).on('shown.bs.modal.erpLocationDropdown', '.modal', function () {
                prepare(this, true);
            });

            if (MutationObserverConstructor && documentObject.body) {
                var observer = new MutationObserverConstructor(function (mutations) {
                    mutations.forEach(function (mutation) {
                        Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
                            if (node && node.nodeType === 1) {
                                // AJAX/module code often appends OPTION nodes
                                // to an existing location SELECT. Re-validate
                                // the parent select immediately in that case.
                                if (String(node.tagName || '').toUpperCase() === 'OPTION'
                                    && node.parentNode) {
                                    prepare(node.parentNode, true);
                                } else {
                                    prepare(node, true);
                                }
                            }
                        });
                    });
                });
                observer.observe(documentObject.body, {childList: true, subtree: true});
            }
        });
    }

    return {
        applyInitialDefault: applyInitialDefault,
        firstSelectableValue: firstSelectableValue,
        hydrateAssignedLocations: hydrateAssignedLocations,
        install: install,
        isLocationSelect: isLocationSelect,
        isSelect2Initialized: isSelect2Initialized,
        locationSelect2Options: locationSelect2Options,
        pruneDisallowedLocations: pruneDisallowedLocations,
    };
});
