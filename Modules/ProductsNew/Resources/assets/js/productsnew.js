(function (window, document) {
    'use strict';

    function ready(fn) {
        document.readyState !== 'loading'
            ? fn()
            : document.addEventListener('DOMContentLoaded', fn);
    }

    function selectPlaceholder(select) {
        return select.getAttribute('data-placeholder')
            || (select.options && select.options.length && select.options[0].value === '' ? select.options[0].text : '')
            || 'Type to search and select';
    }

    function enhanceSelects(root) {
        root = root || document;
        var selects = root.querySelectorAll('select:not([data-no-search]):not(.pn-no-search)');

        selects.forEach(function (select) {
            if (select.dataset.pnSearchReady === '1') return;

            select.dataset.pnSearchReady = '1';
            select.classList.add('pn-searchable-select');

            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                var $select = window.jQuery(select);
                if ($select.hasClass('select2-hidden-accessible')) return;

                $select.select2({
                    width: '100%',
                    placeholder: selectPlaceholder(select),
                    allowClear: !select.required && !select.multiple,
                    closeOnSelect: !select.multiple,
                    minimumResultsForSearch: 0,
                    dropdownAutoWidth: false,
                    language: {
                        noResults: function () { return 'No matching records found'; },
                        searching: function () { return 'Searching…'; },
                        inputTooShort: function () { return 'Type to search'; }
                    }
                });

                $select.on('select2:select select2:clear', function () {
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });

                return;
            }

            // Dependency-free fallback: a search box filters the native option list.
            var wrapper = document.createElement('div');
            wrapper.className = 'pn-native-select-search';

            var input = document.createElement('input');
            input.type = 'search';
            input.className = 'form-control pn-native-select-search-input';
            input.placeholder = selectPlaceholder(select);
            input.setAttribute('aria-label', 'Search dropdown options');

            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(input);
            wrapper.appendChild(select);

            select._pnSearchOptions = Array.prototype.map.call(select.options, function (option) {
                return {
                    value: option.value,
                    text: option.text,
                    selected: option.selected,
                    disabled: option.disabled,
                    parentId: option.getAttribute('data-parent-id') || ''
                };
            });

            input.addEventListener('input', function () {
                var query = input.value.trim().toLowerCase();
                var current = select.value;
                var source = select._pnSearchOptions || [];

                select.innerHTML = '';

                source.forEach(function (item) {
                    if (
                        !query
                        || item.text.toLowerCase().indexOf(query) !== -1
                        || item.value.toLowerCase().indexOf(query) !== -1
                    ) {
                        var option = new Option(
                            item.text,
                            item.value,
                            false,
                            item.value === current
                        );
                        option.disabled = item.disabled;
                        if (item.parentId) option.setAttribute('data-parent-id', item.parentId);
                        select.add(option);
                    }
                });
            });
        });
    }

    function numericValue(value) {
        var normalised = String(value == null ? '' : value).replace(/,/g, '').trim();
        var number = parseFloat(normalised);

        return Number.isFinite(number) ? number : null;
    }

    function formatNumber(value, decimals) {
        return Number(value || 0).toFixed(decimals);
    }

    function initWizard(form) {
        var tabs = Array.prototype.slice.call(
            form.querySelectorAll('.pn-wizard-tabs [data-pn-target], .pn-wizard-tabs [data-target]')
        );
        var sections = Array.prototype.slice.call(
            form.querySelectorAll('.pn-form-section[data-pn-section], .pn-form-section[data-section]')
        );
        var previous = form.querySelector('[data-pn-prev]');
        var next = form.querySelector('[data-pn-next]');
        var currentIndex = 0;

        if (!tabs.length || !sections.length) return;

        function tabTarget(tab) {
            return tab.getAttribute('data-pn-target') || tab.getAttribute('data-target');
        }

        function sectionTarget(section) {
            return section.getAttribute('data-pn-section') || section.getAttribute('data-section');
        }

        function activate(target, options) {
            options = options || {};

            tabs.forEach(function (tab, index) {
                var active = tabTarget(tab) === target;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');

                if (active) currentIndex = index;
            });

            sections.forEach(function (section) {
                var active = sectionTarget(section) === target;
                section.hidden = !active;
                section.classList.toggle('active', active);
            });

            if (previous) previous.disabled = currentIndex <= 0;
            if (next) {
                next.disabled = currentIndex >= tabs.length - 1;
                next.style.display = currentIndex >= tabs.length - 1 ? 'none' : '';
            }

            if (options.focus) {
                var activeSection = sections.find(function (section) {
                    return sectionTarget(section) === target;
                });
                var focusable = activeSection
                    ? activeSection.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])')
                    : null;

                if (focusable) focusable.focus({ preventScroll: true });
            }

            if (options.scroll) {
                var tabBar = form.querySelector('.pn-wizard-tabs');
                if (tabBar) {
                    tabBar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activate(tabTarget(tab), { focus: true });
            });
        });

        if (previous) {
            previous.addEventListener('click', function () {
                if (currentIndex > 0) {
                    activate(tabTarget(tabs[currentIndex - 1]), { focus: true, scroll: true });
                }
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                if (currentIndex < tabs.length - 1) {
                    activate(tabTarget(tabs[currentIndex + 1]), { focus: true, scroll: true });
                }
            });
        }

        // When native validation fails on a hidden tab, open the correct tab.
        form.addEventListener('invalid', function (event) {
            var section = event.target.closest('.pn-form-section');
            if (section) {
                activate(sectionTarget(section), { scroll: true });
            }
        }, true);

        var sectionWithError = form.querySelector('.pn-form-section .has-error');
        var initialSection = sectionWithError
            ? sectionWithError.closest('.pn-form-section')
            : null;
        var initialTarget = initialSection
            ? sectionTarget(initialSection)
            : tabTarget(tabs.find(function (tab) { return tab.classList.contains('active'); }) || tabs[0]);

        activate(initialTarget);
    }

    /*
     * MA-002: two-way price calculation, driven by the selected tax.
     *
     * WHAT IT DID BEFORE
     *   Only one direction: Excl. Tax -> Incl. Tax. There was no way to enter
     *   an inclusive price and get the exclusive one back.
     *
     *   Worse, each inclusive box carried a "pnManual" flag: the moment anyone
     *   typed in it, it was marked manual and NEVER updated again - not even
     *   when the tax rate or the exclusive price changed. That is why a product
     *   could sit showing 1,200 excluding and 1,500 including with no tax rate
     *   that produces those two numbers.
     *
     * WHAT IT DOES NOW
     *   Whichever box you type in is the source, and its partner is worked out
     *   from it:
     *
     *       Incl. = Excl. x (1 + rate/100)
     *       Excl. = Incl. / (1 + rate/100)
     *
     *   for BOTH the purchase pair and the selling pair. Change the tax and
     *   both pairs recalculate from whichever side was entered last, so the
     *   figures can never disagree with the rate.
     *
     *   Profit % still drives the selling price from the purchase price exactly
     *   as before.
     *
     * WHY THERE IS NO LOOP
     *   Setting .value from script does NOT fire an "input" event, so writing
     *   the partner box cannot trigger another round.
     */
    function initPriceCalculator(form) {
        var purchaseEx = form.querySelector('[data-pn-price="purchase-ex"]');
        var purchaseInc = form.querySelector('[data-pn-price="purchase-inc"]');
        var profit = form.querySelector('[data-pn-price="profit"]');
        var sellingEx = form.querySelector('[data-pn-price="selling-ex"]');
        var sellingInc = form.querySelector('[data-pn-price="selling-inc"]');
        var tax = form.querySelector('[data-pn-tax]');
        var saleTax = form.querySelector('[data-pn-sale-tax]');
        var basis = form.querySelector('[data-pn-profit-basis]');
        var precisionField = form.querySelector('[data-pn-precision]');
        var isExistingProduct = form.getAttribute('data-pn-existing-product') === '1';

        /*
         * MA-002: decimals for the CALCULATED figures.
         *
         * Two separate things were wrong before:
         *
         * 1. Every price input carried step="0.0001". The browser enforces that,
         *    so a calculated profit of 1.509852 was rejected outright with
         *    "Please enter a valid value. The two nearest valid values are
         *    1.5098 and 1.5099." The form could not be saved at all.
         *    Those are now step="any" - operators can type as many decimals as
         *    they like into any price, with no limit.
         *
         * 2. The calculated figures were all rounded to a fixed 4 decimals.
         *    They now follow Settings > Business Settings > Business > Currency
         *    Precision, which is 2 on most of your businesses and 3 on at least
         *    one - so it could not be hard coded.
         *
         * PURCHASE PRICES ARE DELIBERATELY LEFT AT FULL PRECISION. Rounding the
         * cost before the margin is worked out would push the error into every
         * figure derived from it. Only what is SHOWN is rounded, and only where
         * you asked: the profit percentage and the selling prices.
         */
        function precision() {
            if (!precisionField) return 2;

            var value = parseInt(precisionField.value, 10);

            return isNaN(value) ? 2 : Math.max(0, Math.min(6, value));
        }

        if (!purchaseEx || !purchaseInc || !profit || !sellingEx || !sellingInc) return;

        /*
         * IS2215: keep the selector deterministic even when a third-party select
         * enhancer or stale cached markup leaves it without a valid value. The
         * server-rendered saved basis wins; tax type is only a final compatibility
         * fallback for VAT-inclusive products.
         */
        if (basis && basis.value !== 'inclusive' && basis.value !== 'exclusive') {
            var savedBasis = basis.getAttribute('data-pn-saved-profit-basis');
            var taxTypeField = form.querySelector('[data-pn-tax-type]');

            if (savedBasis === 'inclusive' || savedBasis === 'exclusive') {
                basis.value = savedBasis;
            } else if (taxTypeField && taxTypeField.value === 'inclusive') {
                basis.value = 'inclusive';
            } else {
                basis.value = 'exclusive';
            }
        }

        /*
         * IS2212:
         * On CREATE, Profit % is the natural starting source.
         *
         * On EDIT, the saved prices are the source of truth. The previous code
         * always started from "margin" and immediately recalculated the form.
         * With a stored selling excl. price such as 211.86 and 18% VAT, that
         * startup recalculation produced 211.86 x 1.18 = 249.9948 and displayed
         * 249.99, even though the saved inclusive selling price was exactly 250.
         *
         * Prefer the saved inclusive values on edit so reopening the product never
         * changes a stored price merely by rendering the page.
         */
        var purchaseSource = isExistingProduct && String(purchaseInc.value || '').trim() !== ''
            ? 'inc'
            : 'ex';

        /*
         * sellingSource decides which of the three related figures is the one
         * the operator MEANT, so the other two follow it:
         *
         *   'margin' - they typed a Profit %      -> selling price follows
         *   'ex'     - they typed Excl. selling   -> profit follows
         *   'inc'    - they typed Incl. selling   -> excl and profit follow
         */
        var sellingSource = 'margin';

        if (isExistingProduct) {
            if (String(sellingInc.value || '').trim() !== '') {
                sellingSource = 'inc';
            } else if (String(sellingEx.value || '').trim() !== '') {
                sellingSource = 'ex';
            }
        }

        function rateOf(select) {
            if (!select || !select.options || select.selectedIndex < 0) return null;

            var raw = select.options[select.selectedIndex].getAttribute('data-rate');
            if (raw === null || String(raw).trim() === '') return null;

            return numericValue(raw) || 0;
        }

        // The Applicable Tax, used for the purchase side.
        function taxRate() {
            var r = rateOf(tax);

            return r === null ? 0 : r;
        }

        /*
         * MA-002: the SELLING side can be taxed at a different rate.
         *
         * The form has always had a "Selling Price Tax" field with a default of
         * "Use Applicable Tax", but nothing ever read it - selling prices were
         * taxed at the Applicable Tax rate whatever was chosen. It is now used,
         * falling back to the Applicable Tax when left on the default.
         *
         * This also gives "Profit Percentage On" something to do: with one rate
         * on both sides the inclusive and exclusive margins are arithmetically
         * IDENTICAL, because the same factor cancels out of both sides of the
         * division. They differ only when the two rates differ.
         */
        function saleTaxRate() {
            var r = rateOf(saleTax);

            return r === null ? taxRate() : r;
        }

        // A rate of 0 - "Tax Not Applicable" - gives a factor of 1, so the two
        // boxes simply mirror each other. No special case needed.
        function purchaseFactor() {
            return 1 + (taxRate() / 100);
        }

        function sellingFactor() {
            return 1 + (saleTaxRate() / 100);
        }

        function profitBasis() {
            return basis && basis.value === 'inclusive' ? 'inclusive' : 'exclusive';
        }

        function syncPurchase() {
            var f = purchaseFactor();

            if (purchaseSource === 'inc') {
                var inc = numericValue(purchaseInc.value);
                if (inc !== null) {
                    purchaseEx.value = formatNumber(f === 0 ? inc : inc / f, 4);
                }
                return;
            }

            var ex = numericValue(purchaseEx.value);
            if (ex !== null) {
                // Full precision on purpose: this is a COST. Rounding it here
                // would push the error into the margin and every price
                // derived from it.
                purchaseInc.value = formatNumber(ex * f, 4);
            }
        }

        function syncSelling() {
            var f = sellingFactor();

            if (sellingSource === 'inc') {
                var inc = numericValue(sellingInc.value);
                if (inc !== null) {
                    sellingEx.value = formatNumber(f === 0 ? inc : inc / f, precision());
                }
                return;
            }

            var ex = numericValue(sellingEx.value);
            if (ex !== null) {
                sellingInc.value = formatNumber(ex * f, precision());
            }
        }

        /*
         * Profit % works in BOTH directions.
         *
         *   type a margin  -> selling price is calculated
         *   type a price   -> margin is calculated
         *
         * The selected "Profit Percentage On" value decides whether the margin
         * uses the exclusive pair or the inclusive pair.
         */
        function applyMargin() {
            var pEx = numericValue(purchaseEx.value);
            var pInc = numericValue(purchaseInc.value);
            var selectedBasis = profitBasis();
            var costBase = selectedBasis === 'inclusive' ? pInc : pEx;

            /*
             * IS2212:
             * "Profit Percentage On" now controls the actual calculation basis.
             *
             * Tax Inclusive:
             *     Selling Incl. = Purchase Incl. x (1 + margin/100)
             *
             * Tax Exclusive:
             *     Selling Excl. = Purchase Excl. x (1 + margin/100)
             *
             * The companion selling field is then derived using the selling-tax
             * factor. This prevents the basis selector from being only cosmetic.
             */
            if (sellingSource === 'margin') {
                var margin = numericValue(profit.value);
                if (costBase === null || margin === null) return;

                var target = costBase * (1 + margin / 100);
                var sf = sellingFactor();

                if (selectedBasis === 'inclusive') {
                    sellingInc.value = formatNumber(target, precision());
                    sellingEx.value = formatNumber(sf === 0 ? target : target / sf, precision());
                } else {
                    sellingEx.value = formatNumber(target, precision());
                    sellingInc.value = formatNumber(target * sf, precision());
                }

                return;
            }

            // The operator entered a selling price. syncSelling() has already
            // filled its companion, so read the pair matching the selected basis.
            var sellBase = selectedBasis === 'inclusive'
                ? numericValue(sellingInc.value)
                : numericValue(sellingEx.value);

            if (costBase === null || costBase === 0 || sellBase === null) return;

            profit.value = formatNumber(((sellBase - costBase) / costBase) * 100, precision());
        }

        function openingCosts() {
            // Finance values inventory at purchase cost INCLUDING tax.
            var inc = numericValue(purchaseInc.value);

            form.querySelectorAll('.pn-opening-cost').forEach(function (costInput) {
                if (costInput.value.trim() === '' && inc !== null) {
                    costInput.value = formatNumber(inc, 4);
                }
            });

            recalculateOpeningStock(form);
        }

        function recalculateAll() {
            syncPurchase();

            if (sellingSource === 'margin') {
                // applyMargin() writes BOTH selling boxes for the selected basis.
                applyMargin();
            } else {
                // selling (either side) -> the other side -> margin
                syncSelling();
                applyMargin();
            }

            openingCosts();
        }

        purchaseEx.addEventListener('input', function () {
            purchaseSource = 'ex';
            recalculateAll();
        });

        purchaseInc.addEventListener('input', function () {
            purchaseSource = 'inc';
            recalculateAll();
        });

        sellingEx.addEventListener('input', function () {
            sellingSource = 'ex';
            syncSelling();     // fills the inclusive box
            applyMargin();     // then the margin follows from it
        });

        sellingInc.addEventListener('input', function () {
            sellingSource = 'inc';
            syncSelling();     // fills the exclusive box from the inclusive one
            applyMargin();     // margin follows, using that exclusive figure
        });

        profit.addEventListener('input', function () {
            // A typed margin leads: the selling prices follow it.
            sellingSource = 'margin';
            recalculateAll();
        });

        if (tax) {
            tax.addEventListener('change', recalculateAll);
        }

        if (saleTax) {
            saleTax.addEventListener('change', recalculateAll);
        }

        if (basis) {
            /*
             * Switching the basis re-reads the SAME prices and shows the margin
             * measured the other way. It does not change any price - only which
             * pair the percentage is taken between.
             */
            basis.addEventListener('change', function () {
                if (sellingSource === 'margin') {
                    // A margin already typed keeps its number and the prices move
                    // to suit the new basis.
                    recalculateAll();
                    return;
                }

                applyMargin();
            });
        }

        /*
         * IS2212:
         * Do not recalculate a saved product merely because the edit page opened.
         * The database values must be displayed exactly as saved (e.g. 250 must
         * remain 250, not become 249.99 through an excl->incl round trip).
         * New products still initialise the calculator immediately.
         */
        if (!isExistingProduct) {
            recalculateAll();
        }
    }

    function recalculateOpeningStock(form) {
        var total = 0;

        form.querySelectorAll('[data-pn-opening-row]').forEach(function (row) {
            var qtyInput = row.querySelector('.pn-opening-qty');
            var costInput = row.querySelector('.pn-opening-cost');
            var valueCell = row.querySelector('[data-pn-opening-value]');
            var qty = numericValue(qtyInput ? qtyInput.value : null) || 0;
            var cost = numericValue(costInput ? costInput.value : null) || 0;

            total += qty;

            if (valueCell) {
                valueCell.textContent = formatNumber(qty * cost, 4);
            }

            row.classList.toggle('has-opening-stock', qty > 0);

            if (qty > 0) {
                var locationId = row.getAttribute('data-location-id');
                var checkbox = form.querySelector('[data-pn-location-checkbox="' + locationId + '"]');
                if (checkbox) checkbox.checked = true;
            }
        });

        var totalElement = form.querySelector('[data-pn-opening-total]');
        if (totalElement) totalElement.textContent = formatNumber(total, 3);
    }

    function initOpeningStock(form) {
        var panel = form.querySelector('[data-pn-opening-panel]');
        var enableStock = form.querySelector('[data-pn-enable-stock]');

        if (!panel) return;

        panel.addEventListener('input', function (event) {
            if (
                event.target.classList.contains('pn-opening-qty')
                || event.target.classList.contains('pn-opening-cost')
            ) {
                recalculateOpeningStock(form);
            }
        });

        function applyStockState() {
            var enabled = !enableStock || enableStock.checked;
            panel.classList.toggle('is-disabled', !enabled);

            panel.querySelectorAll('input').forEach(function (input) {
                input.disabled = !enabled;
            });
        }

        if (enableStock) {
            enableStock.addEventListener('change', applyStockState);
        }

        applyStockState();
        recalculateOpeningStock(form);
    }

    function initProductTypeNote(form) {
        var type = form.querySelector('[data-pn-product-type]');
        var note = form.querySelector('[data-pn-price-note]');

        if (!type || !note) return;

        function update() {
            if (type.value === 'variable') {
                note.innerHTML = '<i class="fa fa-info-circle"></i> These values are saved as the default/base variation prices. Individual variation pricing can be maintained after the product is created.';
            } else if (type.value === 'combo') {
                note.innerHTML = '<i class="fa fa-info-circle"></i> These values are saved as the combo product’s default purchase and selling prices.';
            } else {
                note.innerHTML = '<i class="fa fa-calculator"></i> Selling price is calculated automatically from purchase price and profit percentage when it is left blank.';
            }
        }

        type.addEventListener('change', update);
        update();
    }

    function refreshEnhancedSelect(select) {
        select._pnSearchOptions = Array.prototype.map.call(select.options, function (option) {
            return {
                value: option.value,
                text: option.text,
                selected: option.selected,
                disabled: option.disabled,
                parentId: option.getAttribute('data-parent-id') || ''
            };
        });

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            var $select = window.jQuery(select);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.trigger('change.select2');
            }
        }
    }

    function initDependentSubcategories(root) {
        root = root || document;
        root.querySelectorAll('[data-pn-category]').forEach(function (category) {
            var scope = category.closest('form') || root;
            var subcategory = scope.querySelector('[data-pn-subcategory]');
            if (!subcategory || subcategory.dataset.pnDependencyReady === '1') return;

            subcategory.dataset.pnDependencyReady = '1';
            var initialSelected = String(subcategory.getAttribute('data-selected') || subcategory.value || '');
            var allOptions = Array.prototype.map.call(subcategory.options, function (option) {
                return {
                    value: String(option.value || ''),
                    text: option.text,
                    parentId: String(option.getAttribute('data-parent-id') || ''),
                    disabled: option.disabled
                };
            }).filter(function (item) { return item.value !== ''; });

            function rebuild(keepValue) {
                var parentId = String(category.value || '');
                var current = String(keepValue == null ? subcategory.value : keepValue);
                /*
                 * MA-002 (IS-1919 #2): "All" in both states.
                 *
                 * This rebuilds the subcategory list whenever the category changes,
                 * so changing only the Blade would have been undone the moment
                 * anyone touched the category dropdown.
                 */
                var placeholder = 'All';
                var matching = allOptions.filter(function (item) {
                    return parentId && item.parentId === parentId;
                });

                subcategory.innerHTML = '';
                subcategory.add(new Option(placeholder, '', false, false));
                matching.forEach(function (item) {
                    var option = new Option(item.text, item.value, false, item.value === current);
                    option.disabled = item.disabled;
                    option.setAttribute('data-parent-id', item.parentId);
                    subcategory.add(option);
                });

                if (!matching.some(function (item) { return item.value === current; })) {
                    subcategory.value = '';
                }

                subcategory.disabled = !parentId || matching.length === 0;
                refreshEnhancedSelect(subcategory);
            }

            category.addEventListener('change', function () {
                rebuild('');
                subcategory.dispatchEvent(new Event('change', { bubbles: true }));
            });

            rebuild(initialSelected);
        });
    }

    function initImagePreview(form) {
        var input = form.querySelector('[data-pn-image-input]');
        var preview = form.querySelector('[data-pn-image-preview]');
        if (!input || !preview || input.dataset.pnImageReady === '1') return;

        input.dataset.pnImageReady = '1';
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;

            if (file.type && file.type.indexOf('image/') !== 0) {
                input.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function (event) {
                preview.innerHTML = '';
                var image = document.createElement('img');
                image.src = event.target.result;
                image.alt = 'Selected product image';
                preview.appendChild(image);
                preview.classList.add('has-image');
            };
            reader.readAsDataURL(file);
        });
    }

    function initProductFilters(form) {
        if (!form || form.dataset.pnFilterReady === '1') return;
        form.dataset.pnFilterReady = '1';
        initDependentSubcategories(form);

        form.addEventListener('submit', function () {
            form.querySelectorAll('input[name], select[name]').forEach(function (field) {
                if (!String(field.value || '').trim()) field.disabled = true;
            });
        });
    }

    function initCategoryManager(root) {
        var modal = root.querySelector('[data-pn-category-modal]');
        if (!modal || modal.dataset.pnCategoryReady === '1') return;
        modal.dataset.pnCategoryReady = '1';

        var form = modal.querySelector('form');
        var title = modal.querySelector('[data-pn-category-modal-title]');
        var method = form ? form.querySelector('[name="_method"]') : null;
        var subToggle = form ? form.querySelector('input[type="checkbox"][name="add_as_sub_category"]') : null;
        var parentWrap = form ? form.querySelector('[data-pn-category-parent-wrap]') : null;
        var parentSelect = form ? form.querySelector('[name="parent_id"]') : null;
        var weightToggle = form ? form.querySelector('input[type="checkbox"][name="weight_excess_loss_applicable"]') : null;
        var weightAccountWraps = form ? form.querySelectorAll('[data-pn-weight-account-wrap]') : [];
        var submitButton = form ? form.querySelector('button[type="submit"]') : null;
        var storeAction = form ? form.getAttribute('data-store-action') : '';

        function setParentState() {
            if (!subToggle || !parentWrap || !parentSelect) return;
            var enabled = subToggle.checked;
            parentWrap.hidden = !enabled;
            parentSelect.disabled = !enabled;
            parentSelect.required = enabled;
            if (!enabled) parentSelect.value = '';
            refreshEnhancedSelect(parentSelect);
        }

        function setWeightAccountState() {
            if (!weightToggle || !weightAccountWraps.length) return;
            var visible = weightToggle.checked;
            Array.prototype.forEach.call(weightAccountWraps, function (wrap) {
                wrap.hidden = !visible;
                if (visible) {
                    wrap.querySelectorAll('select').forEach(refreshEnhancedSelect);
                }
            });
        }

        function fieldByName(name) {
            if (!form) return null;
            return form.querySelector('[name="' + name + '"]:not([type="hidden"])')
                || form.querySelector('[name="' + name + '"]');
        }

        function populate(payload) {
            payload = payload || {};
            Object.keys(payload).forEach(function (name) {
                var field = fieldByName(name);
                if (!field) return;
                if (field.type === 'checkbox') {
                    field.checked = payload[name] === true || payload[name] === 1 || payload[name] === '1' || payload[name] === 'on';
                } else {
                    field.value = payload[name] == null ? '' : payload[name];
                }
                if (field.tagName === 'SELECT') refreshEnhancedSelect(field);
            });

            if (subToggle) {
                subToggle.checked = payload.add_as_sub_category === true
                    || payload.add_as_sub_category === 1
                    || payload.add_as_sub_category === '1'
                    || payload.add_as_sub_category === 'on';
            }
            setParentState();
            setWeightAccountState();
        }

        function showModal() {
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
                window.jQuery(modal).modal('show');
            } else {
                modal.classList.add('in');
                modal.style.display = 'block';
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
            }
        }

        function openCategory(button, forcedPayload) {
            if (!form) return;
            form.reset();
            form.querySelectorAll('select').forEach(refreshEnhancedSelect);
            form.action = button ? (button.getAttribute('data-action') || storeAction) : storeAction;
            if (method) method.value = button ? (button.getAttribute('data-method') || 'POST') : 'POST';
            if (title) title.textContent = button ? (button.getAttribute('data-title') || 'Add Category') : 'Add Category';

            var payload = forcedPayload || {};
            if (!forcedPayload && button) {
                try { payload = JSON.parse(button.getAttribute('data-category') || '{}'); } catch (e) { payload = {}; }
            }
            populate(payload);
            showModal();
        }

        if (subToggle) subToggle.addEventListener('change', setParentState);
        if (weightToggle) weightToggle.addEventListener('change', setWeightAccountState);

        root.querySelectorAll('[data-pn-open-category]').forEach(function (button) {
            button.addEventListener('click', function () {
                openCategory(button);
            });
        });

        if (form) {
            form.addEventListener('submit', function (event) {
                setParentState();

                if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                    event.preventDefault();
                    if (typeof form.reportValidity === 'function') form.reportValidity();
                    return;
                }

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.setAttribute('aria-busy', 'true');
                    submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
                }
            });
        }

        modal.querySelectorAll('[data-dismiss="modal"], [data-pn-modal-close]').forEach(function (close) {
            close.addEventListener('click', function () {
                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) return;
                modal.classList.remove('in');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            });
        });

        setParentState();
        setWeightAccountState();

        var oldPayloadRaw = modal.getAttribute('data-pn-category-old');
        if (oldPayloadRaw) {
            try {
                openCategory(null, JSON.parse(oldPayloadRaw));
            } catch (e) {
                // Ignore malformed old input and keep the page usable.
            }
        }
    }

    ready(function () {
        document.querySelectorAll('[data-pn-confirm]').forEach(function (element) {
            element.addEventListener('click', function (event) {
                if (!window.confirm(element.getAttribute('data-pn-confirm') || 'Are you sure?')) {
                    event.preventDefault();
                }
            });
        });

        enhanceSelects(document);
        initDependentSubcategories(document);

        document.querySelectorAll('[data-pn-product-wizard]').forEach(function (form) {
            initWizard(form);
            initPriceCalculator(form);
            initOpeningStock(form);
            initProductTypeNote(form);
            initImagePreview(form);
        });

        document.querySelectorAll('[data-pn-product-filter]').forEach(initProductFilters);
        initCategoryManager(document);

        document.querySelectorAll('.table-responsive table').forEach(function (table) {
            table.setAttribute('data-pn-table', '1');
        });

        document.querySelectorAll('input[type="date"]').forEach(function (input) {
            if (!input.getAttribute('aria-label')) input.setAttribute('aria-label', 'Date');
        });

        // Apply searchable dropdowns to AJAX/modals/dynamically inserted forms.
        if (window.MutationObserver) {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) {
                            enhanceSelects(node.matches && node.matches('select') ? node.parentNode : node);
                        }
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });
        }
    });
})(window, document);

/*
 * MA-002 (IS-1941): "None" on Selling Price Tax.
 *
 * When None is chosen the product carries no selling tax, so Selling Price Tax
 * Type has nothing to describe - with a zero rate, inclusive and exclusive give
 * the same price. The select is disabled and a note shown.
 *
 * tax_type is enum('inclusive','exclusive') NOT NULL, so it must still POST a
 * valid value. A disabled select posts nothing, hence the hidden fallback - it
 * is enabled exactly when the select is disabled, and never both at once.
 */
(function () {
    function applyNoSellingTax(form) {
        var saleTax = form.querySelector('[data-pn-sale-tax]');
        var taxType = form.querySelector('[data-pn-tax-type]');
        var fallback = form.querySelector('[data-pn-tax-type-fallback]');
        var note = form.querySelector('[data-pn-tax-type-note]');

        if (!saleTax || !taxType) {
            return;
        }

        var isNone = saleTax.value === '0';

        taxType.disabled = isNone;

        if (fallback) {
            // Carries the value only while the select cannot.
            fallback.disabled = !isNone;
            fallback.value = taxType.value || 'exclusive';
        }

        if (note) {
            note.style.display = isNone ? '' : 'none';
        }
    }

    document.addEventListener('change', function (event) {
        if (!event.target.hasAttribute || !event.target.hasAttribute('data-pn-sale-tax')) {
            return;
        }

        var form = event.target.closest('form');
        if (form) {
            applyNoSellingTax(form);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(
            document.querySelectorAll('form'),
            applyNoSellingTax
        );
    });
}());
