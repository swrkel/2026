/**
 * ERP Global Modal System
 * Adds presentation hooks to existing and AJAX-loaded Bootstrap modals.
 * Does not replace Bootstrap methods, form handlers, validation, or AJAX.
 */
(function (window, document, $) {
    'use strict';

    if (!$) return;

    var SYSTEM_CLASS = 'erp-modal-system';
    var sizeClasses = 'erp-modal-compact erp-modal-standard erp-modal-large erp-modal-wide';

    function textOf($element) {
        return $.trim(($element.text() || '').replace(/\s+/g, ' '));
    }

    function iconFor(title) {
        title = (title || '').toLowerCase();
        if (/delete|remove|cancel|void/.test(title)) return 'fa-trash';
        if (/cheque|check/.test(title)) return 'fa-id-card-o';
        if (/deposit|transfer|payment|amount|cash|card/.test(title)) return 'fa-money';
        if (/account|ledger|finance/.test(title)) return 'fa-university';
        if (/print|pdf|report/.test(title)) return 'fa-file-text-o';
        if (/view|detail|information/.test(title)) return 'fa-eye';
        if (/edit|update|change/.test(title)) return 'fa-pencil';
        if (/add|create|new/.test(title)) return 'fa-plus';
        return 'fa-window-maximize';
    }

    function subtitleFor(title) {
        title = (title || '').toLowerCase();
        if (/delete|remove|void/.test(title)) return 'Please review carefully before continuing.';
        if (/view|detail|information|report/.test(title)) return 'Review the information below.';
        return 'Complete the required information below.';
    }

    function chooseSize($modal) {
        var $dialog = $modal.find('.modal-dialog').first();
        var requestedSize = String($modal.attr('data-erp-modal-size') || '').toLowerCase();
        var fields = $modal.find('.modal-body').first()
            .find('input:not([type="hidden"]), select, textarea').length;
        var columns = $modal.find('.modal-body table thead th').length;
        var hasTable = $modal.find('.modal-body table').length > 0;
        var inlineWidth = ($dialog.get(0) && $dialog.get(0).style.width) || '';
        var explicitWidth = parseFloat(inlineWidth) || 0;

        $modal.removeClass(sizeClasses);

        if (/^(compact|standard|large|wide)$/.test(requestedSize)) {
            $modal.addClass('erp-modal-' + requestedSize);
        } else if ($dialog.hasClass('modal-xl') || columns >= 7
            || (/%$/.test(inlineWidth) && explicitWidth >= 75)
            || (/px$/.test(inlineWidth) && explicitWidth >= 1050)) {
            $modal.addClass('erp-modal-wide');
        } else if ($dialog.hasClass('modal-lg') || hasTable || fields >= 8
            || (/%$/.test(inlineWidth) && explicitWidth >= 55)
            || (/px$/.test(inlineWidth) && explicitWidth >= 760)) {
            $modal.addClass('erp-modal-large');
        } else if ($dialog.hasClass('modal-sm') || fields <= 2) {
            $modal.addClass('erp-modal-compact');
        } else {
            $modal.addClass('erp-modal-standard');
        }
    }

    function enhanceHeader($modal) {
        var $header = $modal.find('.modal-header').first();
        var $title = $header.find('.modal-title').first();
        /*
         * Module-specific popups often wrap .modal-title inside their own header
         * row. Checking only direct children failed for those forms, so every
         * observer pass wrapped the same title again and produced the diagonal
         * chain of icons/subtitles seen before the browser became unresponsive.
         */
        if (!$header.length || !$title.length
            || $title.attr('data-erp-modal-title-enhanced') === '1'
            || $header.find('.erp-modal-title-row').length) {
            return;
        }

        $title.attr('data-erp-modal-title-enhanced', '1');

        var title = textOf($title);
        var $row = $('<div class="erp-modal-title-row"></div>');
        var $icon = $('<span class="erp-modal-title-icon" aria-hidden="true"><i class="fa"></i></span>');
        $icon.find('i').addClass(iconFor(title));
        var $copy = $('<div class="erp-modal-title-copy"></div>');

        $title.before($row);
        $copy.append($title);
        $copy.append($('<small class="erp-modal-subtitle"></small>').text(
            $modal.attr('data-erp-modal-subtitle') || subtitleFor(title)
        ));
        $row.append($icon, $copy);
    }

    function markLoading($modal) {
        var $body = $modal.find('.modal-body').first();
        if (!$body.length || !$body.find('.fa-spinner').length) return;
        if (/loading/i.test(textOf($body))) {
            $body.addClass('erp-modal-loading');
        }
    }

    function enhance(modal) {
        var $modal = $(modal);
        if (!$modal.length || !$modal.hasClass('modal')) return;

        $modal.addClass(SYSTEM_CLASS);
        $modal.attr('data-erp-modal', 'ready');
        chooseSize($modal);
        enhanceHeader($modal);
        markLoading($modal);

        $modal.find('.modal-content').attr('role', 'document');
        $modal.find('.modal-header .close').attr('title', 'Close');
    }

    function enhanceAll(context) {
        $(context || document).find('.modal').each(function () {
            if ($(this).find('.modal-dialog, .modal-content').length) enhance(this);
        });
    }

    window.ERPModalSystem = {
        enhance: enhance,
        refresh: enhanceAll
    };

    $(function () { enhanceAll(document); });

    $(document).on('show.bs.modal.erpGlobalModal shown.bs.modal.erpGlobalModal', '.modal', function () {
        enhance(this);
    });

    /*
     * AJAX forms can add hundreds of nodes (Select2, tables, date pickers, etc.).
     * The previous observer called enhanceAll(document) for every mutation and
     * ajaxComplete, repeatedly scanning every modal on the page. On large ERP
     * pages this multiplied into thousands of full-document scans and could make
     * the browser report "Page Unresponsive".
     *
     * Queue only the modal containers affected by the added nodes and process
     * each container once per animation frame.
     */
    var pendingModals = [];
    var pendingModalNodes = [];
    var refreshScheduled = false;

    function queueModal(modal) {
        if (!modal || !modal.nodeType || modal.nodeType !== 1) return;
        if (pendingModalNodes.indexOf(modal) !== -1) return;

        pendingModalNodes.push(modal);
        pendingModals.push(modal);

        if (refreshScheduled) return;
        refreshScheduled = true;

        var schedule = window.requestAnimationFrame || function (callback) {
            return window.setTimeout(callback, 16);
        };

        schedule(function () {
            var queue = pendingModals.slice(0);
            pendingModals.length = 0;
            pendingModalNodes.length = 0;
            refreshScheduled = false;

            queue.forEach(function (item) {
                if (document.documentElement.contains(item)) {
                    enhance(item);
                }
            });
        });
    }

    function queueAffectedModals(node) {
        if (!node || node.nodeType !== 1) return;

        if ($(node).hasClass('modal')) {
            queueModal(node);
        }

        var ownerModal = $(node).closest('.modal').get(0);
        if (ownerModal) {
            queueModal(ownerModal);
        }

        $(node).find('.modal').each(function () {
            queueModal(this);
        });
    }

    if (window.MutationObserver && document.body) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (!mutation.addedNodes || !mutation.addedNodes.length) return;
                for (var index = 0; index < mutation.addedNodes.length; index += 1) {
                    queueAffectedModals(mutation.addedNodes[index]);
                }
            });
        }).observe(document.body, { childList: true, subtree: true });
    }
})(window, document, window.jQuery);
