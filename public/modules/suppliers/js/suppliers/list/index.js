(function ($) {
    'use strict';

    var SupplierList = {
        init: function () {
            this.bindSearch();
            this.bindPerPage();
            this.bindColumnVisibility();
            this.bindPrint();
            this.bindExportButtons();
            this.initDateRange();
            this.initCurrencyFormatting();
            this.bindActionDropdown();
            this.openRequestedProfileTab();
        },

        submitFilter: function () {
            var form = document.getElementById('supplier_filter_form');

            if (!form || form.getAttribute('data-submitting') === '1') {
                return;
            }

            // Use the native submit method. Some global ERP scripts intercept
            // jQuery form submissions, which previously made the Supplier search
            // appear unresponsive on this page.
            form.setAttribute('data-submitting', '1');
            window.HTMLFormElement.prototype.submit.call(form);
        },

        bindSearch: function () {
            var $search = $('#supplier_global_search');
            var timer = null;
            var composing = false;
            var lastSubmittedValue = String($search.val() || '');

            if (!$search.length) {
                return;
            }

            $search
                .off('.supplierRecordFilter')
                .on('compositionstart.supplierRecordFilter', function () {
                    composing = true;
                })
                .on('compositionend.supplierRecordFilter', function () {
                    composing = false;
                    $(this).trigger('input.supplierRecordFilter');
                })
                .on('input.supplierRecordFilter search.supplierRecordFilter', function () {
                    var currentValue = String($(this).val() || '');

                    if (composing || currentValue === lastSubmittedValue) {
                        return;
                    }

                    clearTimeout(timer);
                    timer = window.setTimeout(function () {
                        lastSubmittedValue = currentValue;
                        SupplierList.submitFilter();
                    }, 350);
                })
                .on('keydown.supplierRecordFilter', function (event) {
                    if (event.key === 'Enter' || event.keyCode === 13) {
                        event.preventDefault();
                        clearTimeout(timer);
                        lastSubmittedValue = String($(this).val() || '');
                        SupplierList.submitFilter();
                    }
                });
        },

        bindPerPage: function () {
            $('#supplier_per_page')
                .off('change.supplierRecordFilter')
                .on('change.supplierRecordFilter', function () {
                    SupplierList.submitFilter();
                });
        },

        normaliseDateRange: function (value) {
            var parts = String(value || '').trim().split('~');
            var datePattern = /^\d{4}-\d{2}-\d{2}$/;

            if (parts.length !== 2) {
                return '';
            }

            var startDate = $.trim(parts[0]);
            var endDate = $.trim(parts[1]);

            if (!datePattern.test(startDate) || !datePattern.test(endDate)) {
                return '';
            }

            return startDate + ' ~ ' + endDate;
        },

        initDateRange: function () {
            var $dateRange = $('#supplier_date_range');

            if (!$dateRange.length) {
                return;
            }

            var initialValue = SupplierList.normaliseDateRange($dateRange.val());
            var lastSubmittedValue = initialValue;

            if (initialValue) {
                $dateRange.val(initialValue);
            }

            if ($.fn.daterangepicker) {
                var pickerOptions = {
                    autoUpdateInput: false,
                    locale: {
                        format: 'YYYY-MM-DD',
                        separator: ' ~ ',
                        cancelLabel: 'Clear'
                    }
                };

                if (initialValue && typeof window.moment === 'function') {
                    var initialParts = initialValue.split(' ~ ');
                    pickerOptions.startDate = window.moment(initialParts[0], 'YYYY-MM-DD', true);
                    pickerOptions.endDate = window.moment(initialParts[1], 'YYYY-MM-DD', true);
                }

                $dateRange.daterangepicker(pickerOptions);

                $dateRange
                    .off('.supplierRecordDateRange')
                    .on('apply.daterangepicker.supplierRecordDateRange', function (event, picker) {
                        var value = picker.startDate.format('YYYY-MM-DD') + ' ~ ' + picker.endDate.format('YYYY-MM-DD');
                        $(this).val(value);
                        lastSubmittedValue = value;
                        SupplierList.submitFilter();
                    })
                    .on('cancel.daterangepicker.supplierRecordDateRange', function () {
                        $(this).val('');
                        lastSubmittedValue = '';
                        SupplierList.submitFilter();
                    });
            }

            $dateRange
                .off('keydown.supplierRecordManualDate change.supplierRecordManualDate')
                .on('keydown.supplierRecordManualDate', function (event) {
                    if (event.key !== 'Enter' && event.keyCode !== 13) {
                        return;
                    }

                    event.preventDefault();
                    var value = SupplierList.normaliseDateRange($(this).val());

                    if (!value && $.trim($(this).val()) !== '') {
                        return;
                    }

                    $(this).val(value);
                    lastSubmittedValue = value;
                    SupplierList.submitFilter();
                })
                .on('change.supplierRecordManualDate', function () {
                    var rawValue = $.trim($(this).val());
                    var value = SupplierList.normaliseDateRange(rawValue);

                    if (!value && rawValue !== '') {
                        return;
                    }

                    if (value === lastSubmittedValue) {
                        return;
                    }

                    $(this).val(value);
                    lastSubmittedValue = value;
                    SupplierList.submitFilter();
                });
        },

        bindColumnVisibility: function () {
            $('.supplier-column-menu').on('click', function (event) {
                // Keep the menu open while the user enables or disables several columns.
                event.stopPropagation();
            });

            $('.supplier-colvis').on('change', function () {
                var columnIndex = parseInt($(this).data('column'), 10) + 1;
                var isVisible = $(this).is(':checked');

                $('#supplier_records_table tr').each(function () {
                    $(this).children(':nth-child(' + columnIndex + ')').toggle(isVisible);
                });

                SupplierList.saveColumnVisibility();
            });

            SupplierList.restoreColumnVisibility();
        },

        saveColumnVisibility: function () {
            var state = {};
            $('.supplier-colvis').each(function () {
                state[$(this).data('column')] = $(this).is(':checked') ? 1 : 0;
            });
            window.localStorage.setItem('supplier_records_column_visibility_v3', JSON.stringify(state));
        },

        restoreColumnVisibility: function () {
            var raw = window.localStorage.getItem('supplier_records_column_visibility_v3');
            if (!raw) {
                return;
            }

            try {
                var state = JSON.parse(raw);
                $('.supplier-colvis').each(function () {
                    var column = $(this).data('column');
                    if (Object.prototype.hasOwnProperty.call(state, column)) {
                        $(this).prop('checked', parseInt(state[column], 10) === 1).trigger('change');
                    }
                });
            } catch (e) {
                window.localStorage.removeItem('supplier_records_column_visibility_v3');
            }
        },

        bindPrint: function () {
            $('.supplier-print-btn').on('click', function () {
                window.print();
            });
        },

        bindExportButtons: function () {
            $('.supplier-export-btn').on('click', function () {
                var exportUrl = $(this).data('export-url');
                var query = $('#supplier_filter_form').serialize();

                if (query) {
                    exportUrl += (exportUrl.indexOf('?') === -1 ? '?' : '&') + query;
                }

                window.location.href = exportUrl;
            });
        },

        initCurrencyFormatting: function () {
            if (typeof window.__currency_convert_recursively === 'function') {
                window.__currency_convert_recursively($('#supplier_records_table'));
            }
        },

        bindActionDropdown: function () {
            var dropdownSelector = '#supplier_records_table .supplier-row-actions';

            // This page can be reached through more than one Supplier tab. Remove
            // only this component's previous handlers so the dropdown logic is
            // never registered twice after cached/partial page loads.
            $(document).off('.supplierActionDropdown');
            $(window).off('.supplierActionDropdown');

            SupplierList.closeAllActionDropdowns();

            $(document).on('show.bs.dropdown.supplierActionDropdown', dropdownSelector, function () {
                var $currentDropdown = $(this);

                // A detached Bootstrap menu is no longer a child of its original
                // .btn-group. Explicitly close and restore every other row before
                // opening this one; otherwise old menu fragments can remain visible.
                SupplierList.restoreDetachedMenu($currentDropdown);
                SupplierList.closeAllActionDropdowns($currentDropdown);
            });

            $(document).on('shown.bs.dropdown.supplierActionDropdown', dropdownSelector, function () {
                SupplierList.detachActionMenu($(this));
            });

            $(document).on(
                'hide.bs.dropdown.supplierActionDropdown hidden.bs.dropdown.supplierActionDropdown',
                dropdownSelector,
                function () {
                    SupplierList.restoreDetachedMenu($(this));
                }
            );

            // IS-SUPPLIER-ACTION-STABLE-20260910:
            // Keep the currently open Action menu visible while the page or the
            // horizontally-scrollable table moves. The previous handler closed the
            // dropdown on every scroll, which made the menu disappear while the user
            // was trying to reach an action. Reposition the detached menu instead.
            var scheduleReposition = (function () {
                var frame = null;

                return function () {
                    if (frame !== null) {
                        return;
                    }

                    frame = window.requestAnimationFrame(function () {
                        frame = null;
                        SupplierList.repositionDetachedMenus();
                    });
                };
            }());

            $(window).on('resize.supplierActionDropdown scroll.supplierActionDropdown', scheduleReposition);
            $('.supplier-table-wrap')
                .off('scroll.supplierActionDropdown')
                .on('scroll.supplierActionDropdown', scheduleReposition);
        },

        detachActionMenu: function ($dropdown) {
            var $toggle = $dropdown.children('.dropdown-toggle');
            var $menu = $dropdown.children('.supplier-action-menu');

            if (!$toggle.length || !$menu.length) {
                return;
            }

            if (!$menu.parent().is('body')) {
                $dropdown.data('supplier-detached-menu', $menu);
                $menu.data('supplier-action-owner', $dropdown.get(0));

                $menu
                    .addClass('supplier-action-menu-detached')
                    .appendTo(document.body)
                    .css({
                        display: 'block',
                        position: 'fixed',
                        visibility: 'hidden',
                        top: 0,
                        left: 0,
                        right: 'auto'
                    });
            }

            SupplierList.positionActionMenu($dropdown);
        },

        positionActionMenu: function ($dropdown) {
            var $toggle = $dropdown.children('.dropdown-toggle');
            var $menu = $dropdown.data('supplier-detached-menu');

            if (!$menu || !$menu.length) {
                $menu = $('body > .supplier-action-menu-detached').filter(function () {
                    return $(this).data('supplier-action-owner') === $dropdown.get(0);
                }).first();
            }

            if (!$toggle.length || !$menu.length || !$toggle.get(0) || !document.documentElement.contains($toggle.get(0))) {
                return;
            }

            var toggleEl = $toggle.get(0);
            var menuEl = $menu.get(0);
            var toggleRect = toggleEl.getBoundingClientRect();
            var viewportWidth = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
            var viewportHeight = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
            var viewportGap = 8;
            var anchorGap = 4;
            var menuWidth = Math.min(300, Math.max(240, viewportWidth - (viewportGap * 2)));

            // Measure the natural menu height first. The menu is detached to <body>
            // so table-responsive overflow cannot clip it.
            menuEl.style.setProperty('display', 'block', 'important');
            menuEl.style.setProperty('position', 'fixed', 'important');
            menuEl.style.setProperty('visibility', 'hidden', 'important');
            menuEl.style.setProperty('top', '0px', 'important');
            menuEl.style.setProperty('left', '0px', 'important');
            menuEl.style.setProperty('right', 'auto', 'important');
            menuEl.style.setProperty('width', menuWidth + 'px', 'important');
            menuEl.style.setProperty('min-width', menuWidth + 'px', 'important');
            menuEl.style.setProperty('max-width', menuWidth + 'px', 'important');
            menuEl.style.setProperty('max-height', 'none', 'important');
            menuEl.style.setProperty('overflow-x', 'hidden', 'important');
            menuEl.style.setProperty('overflow-y', 'visible', 'important');

            var naturalMenuHeight = Math.max(menuEl.scrollHeight, menuEl.offsetHeight, 1);
            var availableBelow = Math.max(0, viewportHeight - toggleRect.bottom - anchorGap - viewportGap);
            var availableAbove = Math.max(0, toggleRect.top - anchorGap - viewportGap);
            var openBelow = availableBelow >= naturalMenuHeight || availableBelow >= availableAbove;
            var availableOnChosenSide = openBelow ? availableBelow : availableAbove;

            // Always keep the menu physically attached to the Action button. If the
            // full list does not fit on the chosen side, reduce only the menu height
            // and give the menu its own scrollbar. Never pin it to the page top.
            var renderedHeight = Math.min(naturalMenuHeight, Math.max(80, availableOnChosenSide));
            var top;

            if (openBelow) {
                top = toggleRect.bottom + anchorGap;
            } else {
                top = toggleRect.top - anchorGap - renderedHeight;
            }

            // Horizontal alignment starts exactly at the Action button and is only
            // shifted when required to keep the complete menu inside the viewport.
            var left = toggleRect.left;
            if (left + menuWidth > viewportWidth - viewportGap) {
                left = viewportWidth - viewportGap - menuWidth;
            }
            left = Math.max(viewportGap, left);

            menuEl.style.setProperty('top', Math.round(top) + 'px', 'important');
            menuEl.style.setProperty('left', Math.round(left) + 'px', 'important');
            menuEl.style.setProperty('right', 'auto', 'important');
            menuEl.style.setProperty('max-height', Math.round(renderedHeight) + 'px', 'important');
            menuEl.style.setProperty('overflow-y', naturalMenuHeight > renderedHeight ? 'auto' : 'visible', 'important');
            menuEl.style.setProperty('visibility', 'visible', 'important');
        },

        repositionDetachedMenus: function () {
            $('body > .supplier-action-menu-detached').each(function () {
                var $menu = $(this);
                var owner = $menu.data('supplier-action-owner');

                if (!owner || !document.documentElement.contains(owner)) {
                    $menu.remove();
                    return;
                }

                var $dropdown = $(owner);
                var isOpen = $dropdown.hasClass('open')
                    || $dropdown.hasClass('show')
                    || $menu.hasClass('show');

                if (!isOpen) {
                    SupplierList.restoreDetachedMenu($dropdown);
                    return;
                }

                SupplierList.positionActionMenu($dropdown);
            });
        },

        closeAllActionDropdowns: function ($exceptDropdown) {
            $('#supplier_records_table .supplier-row-actions').each(function () {
                var $dropdown = $(this);

                if ($exceptDropdown && $exceptDropdown.length && $dropdown.get(0) === $exceptDropdown.get(0)) {
                    return;
                }

                $dropdown.removeClass('open show');
                $dropdown.children('.dropdown-toggle').attr('aria-expanded', 'false');

                var $detachedMenu = $dropdown.data('supplier-detached-menu');
                if ($detachedMenu && $detachedMenu.length) {
                    $detachedMenu.removeClass('show');
                } else {
                    $dropdown.children('.supplier-action-menu').removeClass('show');
                }

                SupplierList.restoreDetachedMenu($dropdown);
            });

            // Defensive cleanup for an owner row that may have been replaced or
            // removed while its menu was attached directly to <body>.
            $('body > .supplier-action-menu-detached').each(function () {
                var $menu = $(this);
                var owner = $menu.data('supplier-action-owner');

                if (owner && document.documentElement.contains(owner)) {
                    SupplierList.restoreDetachedMenu($(owner));
                } else {
                    $menu.remove();
                }
            });
        },

        restoreDetachedMenu: function ($dropdown) {
            var $menu = $dropdown.data('supplier-detached-menu');

            if (!$menu || !$menu.length) {
                return;
            }

            $menu
                .removeClass('supplier-action-menu-detached')
                .removeData('supplier-action-owner')
                .removeAttr('style')
                .appendTo($dropdown);

            $dropdown.removeData('supplier-detached-menu');
        },


        openRequestedProfileTab: function () {
            // This file can be loaded globally from the Suppliers layout. Honour a
            // direct action-menu hash when the destination is the profile screen.
            if (!window.location.hash || !$('.supplier-profile-tabs').length) {
                return;
            }

            var $tab = $('.supplier-profile-tabs a[href="' + window.location.hash + '"]');
            if ($tab.length && typeof $tab.tab === 'function') {
                $tab.tab('show');
            }
        }
    };

    $(document).ready(function () {
        SupplierList.init();
    });
})(jQuery);
