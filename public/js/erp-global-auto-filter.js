/*
 * erp-global-auto-filter.js
 *
 * Filters apply themselves. Change a filter - type in a search box, pick a
 * category, choose a date range - and the list refreshes on its own, with no
 * need to press "Apply Filters".
 *
 * WHY IT IS DONE THIS WAY
 *
 * There are 38 filter screens across 15 modules, and they do not share a filter
 * implementation: some are server-side DataTables, some client-side, some
 * reload the page, and their buttons carry different ids and classes. Writing
 * filtering logic here would mean reimplementing all of it, and getting one of
 * them subtly wrong.
 *
 * So this file does not filter anything. It finds the button that page already
 * uses and clicks it for you. Whatever that button did before, it still does -
 * this only changes WHEN it happens. That is the safest possible version of
 * this change, and it means a screen with unusual filtering behaviour cannot
 * break, because its own code is still the thing doing the work.
 *
 * WHAT IT WILL NOT TOUCH
 *
 *   - buttons whose text is not clearly a filter action, so a Save, Update or
 *     Delete button can never be clicked automatically;
 *   - anything inside a modal, where a stray click could submit a half-filled
 *     form;
 *   - DataTables' own search box, which already filters as you type;
 *   - any element, or any container, carrying data-no-auto-filter.
 *
 * TURNING IT OFF
 *
 *   - one screen:  add data-no-auto-filter to the filter panel or the button
 *   - everywhere:  set window.ERP_DISABLE_AUTO_FILTER = true before this loads,
 *                  or remove the <script> tag from layouts/app.blade.php
 */
(function () {
    'use strict';

    if (window.ERP_DISABLE_AUTO_FILTER) return;
    if (window.__erpAutoFilterLoaded) return;
    window.__erpAutoFilterLoaded = true;

    if (typeof jQuery === 'undefined') return;

    var $ = jQuery;

    // Delay before applying. Long enough to finish typing a word, short enough
    // that picking from a dropdown feels immediate.
    var TYPING_DELAY = 600;
    var CHOICE_DELAY = 150;

    /*
     * Buttons this is allowed to click. The explicit ids and classes come from
     * the screens that already use them; the text test is the general case.
     *
     * "Search" and "Show" are deliberately NOT accepted - too many unrelated
     * buttons carry those words, and clicking the wrong one would be worse than
     * leaving the screen as it was.
     */
    var KNOWN_BUTTONS = [
        '#apply_filters',
        '#applyFilters',
        '.supplier-apply-filter',
        '#list_deposit_transfer_filter_btn',
        '#f18_list_filter_btn',
        '[data-auto-filter-button]'
    ].join(',');

    var TEXT_TEST = /^(apply\s*filters?|apply|filter)$/i;

    function isApplyButton(el) {
        var $el = $(el);

        if ($el.is('[data-no-auto-filter]')) return false;
        if ($el.closest('[data-no-auto-filter]').length) return false;
        if ($el.closest('.modal').length) return false;
        if ($el.is(':disabled') || $el.is('[disabled]')) return false;
        if (!$el.is(':visible')) return false;

        if ($el.is(KNOWN_BUTTONS)) return true;

        return TEXT_TEST.test($.trim($el.text()));
    }

    /*
     * The filter area a button belongs to. Preferring the surrounding form keeps
     * the watched inputs to the ones that button actually submits; the fallbacks
     * cover panels built without a form element.
     */
    function containerFor($button) {
        var $form = $button.closest('form');

        if ($form.length) return $form;

        var $panel = $button.closest('.filters, .filter-panel, .box, .card, .panel, .well, section');

        return $panel.length ? $panel : $();
    }

    function watchedFields($container) {
        return $container
            .find('input, select, textarea')
            .not('[type=hidden], [type=submit], [type=button], [type=reset], [type=file]')
            .not('[data-no-auto-filter]')
            .not('.dataTables_filter input')          // already live
            .not(function () {
                return $(this).closest('[data-no-auto-filter], .modal, .dataTables_filter').length > 0;
            });
    }

    function bind($button) {
        if ($button.data('erpAutoFilterBound')) return;

        var $container = containerFor($button);

        if (!$container.length) return;

        $button.data('erpAutoFilterBound', true);

        var timer = null;
        var applying = false;

        function apply() {
            // Guard against a click that changes a field and re-triggers this.
            if (applying) return;

            if ($button.is(':disabled') || !$button.is(':visible')) return;

            applying = true;

            try {
                $button.trigger('click');
            } finally {
                // Released on the next tick so changes made by the click itself
                // do not start another round.
                window.setTimeout(function () { applying = false; }, 0);
            }
        }

        function schedule(delay) {
            window.clearTimeout(timer);
            timer = window.setTimeout(apply, delay);
        }

        $container
            .off('.erpAutoFilter')
            .on('input.erpAutoFilter', 'input[type=text], input[type=search], input[type=number], textarea', function () {
                if (!watchedFields($container).is(this)) return;
                schedule(TYPING_DELAY);
            })
            .on('change.erpAutoFilter', 'select, input[type=date], input[type=checkbox], input[type=radio], input', function () {
                if (!watchedFields($container).is(this)) return;
                schedule(CHOICE_DELAY);
            });

        // select2 and daterangepicker fire their own events on the same element.
        $container.on('select2:select.erpAutoFilter select2:unselect.erpAutoFilter apply.daterangepicker.erpAutoFilter cancel.daterangepicker.erpAutoFilter', function () {
            schedule(CHOICE_DELAY);
        });

        // Pressing Enter in a filter box should apply at once, not wait.
        $container.on('keydown.erpAutoFilter', 'input', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                window.clearTimeout(timer);
                apply();
            }
        });
    }

    function scan() {
        $('button, a.btn, input[type=button]').each(function () {
            if (isApplyButton(this)) bind($(this));
        });
    }

    $(function () {
        scan();

        // Filter panels are often rendered after load, or replaced by a tab
        // change or an AJAX refresh.
        if (window.MutationObserver) {
            var pending = null;

            new MutationObserver(function () {
                window.clearTimeout(pending);
                pending = window.setTimeout(scan, 300);
            }).observe(document.body, { childList: true, subtree: true });
        }
    });
})();
