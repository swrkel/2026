<style>
/* S560: dedicated VAT Statement column selector. It does not depend on the
 * DataTables ColVis collection, so global dropdown/overflow rules cannot hide it. */
body > .vat-statement-colvis-panel {
    position: fixed;
    display: block;
    width: 260px;
    max-width: calc(100vw - 16px);
    max-height: min(420px, calc(100vh - 16px));
    overflow: hidden;
    padding: 0;
    margin: 0;
    background: #fff;
    border: 1px solid #d9e1ea;
    border-radius: 8px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .20);
    z-index: 1000000;
}

.vat-statement-colvis-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 12px;
    border-bottom: 1px solid #e7edf3;
    background: #f8fafc;
    color: #243447;
    font-size: 13px;
    font-weight: 700;
}

.vat-statement-colvis-panel__close {
    border: 0;
    background: transparent;
    color: #667085;
    font-size: 18px;
    line-height: 1;
    padding: 0 2px;
}

.vat-statement-colvis-panel__tools {
    padding: 8px 12px;
    border-bottom: 1px solid #edf1f5;
}

.vat-statement-colvis-panel__show-all {
    width: 100%;
    border: 1px solid #cfd8e3;
    border-radius: 5px;
    background: #fff;
    color: #344054;
    padding: 6px 10px;
    text-align: center;
    font-size: 12px;
    font-weight: 600;
}

.vat-statement-colvis-panel__list {
    max-height: 310px;
    overflow-y: auto;
    padding: 6px 0;
}

.vat-statement-colvis-panel__item {
    display: flex;
    align-items: center;
    gap: 9px;
    width: 100%;
    margin: 0;
    padding: 7px 12px;
    color: #344054;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
}

.vat-statement-colvis-panel__item:hover {
    background: #f5f8fb;
}

.vat-statement-colvis-panel__item input {
    margin: 0;
}

.vat-statement-colvis-button-active {
    box-shadow: 0 0 0 2px rgba(79, 70, 229, .18) !important;
}
</style>

<script>
(function ($) {
    'use strict';

    if (window.VatStatementColumnVisibility) {
        return;
    }

    var panelId = 'vat-statement-column-visibility-panel';
    var activeTable = null;
    var activeButton = null;

    function getPanel() {
        return $('#' + panelId);
    }

    function closePanel() {
        getPanel().remove();

        if (activeButton && activeButton.length) {
            activeButton.removeClass('vat-statement-colvis-button-active').attr('aria-expanded', 'false');
        }

        activeTable = null;
        activeButton = null;
    }

    function safeTitle(column, index) {
        var header = column.header();
        var title = header ? $.trim($(header).text()) : '';
        return title || ('Column ' + (index + 1));
    }

    function eligibleColumns(dt) {
        var columns = [];

        dt.columns().every(function (index) {
            var header = this.header();
            var $header = $(header);

            if ($header.hasClass('noColvis') || $header.hasClass('notexport')) {
                return;
            }

            columns.push({
                index: index,
                title: safeTitle(this, index),
                visible: this.visible()
            });
        });

        return columns;
    }

    function refreshTable(dt) {
        dt.columns.adjust();

        if (dt.responsive && typeof dt.responsive.recalc === 'function') {
            dt.responsive.recalc();
        }
    }

    function positionPanel($panel, $button) {
        if (!$panel.length || !$button.length || !$button[0]) {
            return;
        }

        var rect = $button[0].getBoundingClientRect();
        var viewportWidth = document.documentElement.clientWidth || window.innerWidth;
        var viewportHeight = document.documentElement.clientHeight || window.innerHeight;
        var panelWidth = $panel.outerWidth() || 260;
        var panelHeight = $panel.outerHeight() || 260;
        var left = rect.left;
        var top = rect.bottom + 5;

        if (left + panelWidth > viewportWidth - 8) {
            left = rect.right - panelWidth;
        }

        left = Math.max(8, Math.min(left, viewportWidth - panelWidth - 8));

        if (top + panelHeight > viewportHeight - 8 && rect.top - panelHeight - 5 >= 8) {
            top = rect.top - panelHeight - 5;
        }

        top = Math.max(8, Math.min(top, viewportHeight - panelHeight - 8));

        $panel.css({
            top: top + 'px',
            left: left + 'px'
        });
    }

    function buildPanel(dt, $button) {
        var $panel = $('<div/>', {
            id: panelId,
            class: 'vat-statement-colvis-panel',
            role: 'dialog',
            'aria-label': 'Column Visibility'
        });

        var $header = $('<div/>', { class: 'vat-statement-colvis-panel__header' })
            .append($('<span/>').text('Column Visibility'))
            .append($('<button/>', {
                type: 'button',
                class: 'vat-statement-colvis-panel__close',
                'aria-label': 'Close column visibility'
            }).html('&times;'));

        var $showAll = $('<button/>', {
            type: 'button',
            class: 'vat-statement-colvis-panel__show-all'
        }).text('Show All Columns');

        var $tools = $('<div/>', { class: 'vat-statement-colvis-panel__tools' }).append($showAll);
        var $list = $('<div/>', { class: 'vat-statement-colvis-panel__list' });

        eligibleColumns(dt).forEach(function (item) {
            var inputId = panelId + '-column-' + item.index;
            var $checkbox = $('<input/>', {
                type: 'checkbox',
                id: inputId,
                checked: item.visible,
                'data-column-index': item.index
            });

            var $label = $('<label/>', {
                class: 'vat-statement-colvis-panel__item',
                for: inputId
            }).append($checkbox).append($('<span/>').text(item.title));

            $list.append($label);
        });

        $panel.append($header, $tools, $list).appendTo('body');

        $panel.on('click.s560VatColvis', function (event) {
            event.stopPropagation();
        });

        $panel.on('click.s560VatColvis', '.vat-statement-colvis-panel__close', function () {
            closePanel();
        });

        $panel.on('change.s560VatColvis', 'input[data-column-index]', function () {
            var columnIndex = parseInt($(this).attr('data-column-index'), 10);
            if (isNaN(columnIndex)) {
                return;
            }

            dt.column(columnIndex).visible(this.checked, false);
            refreshTable(dt);
        });

        $showAll.on('click.s560VatColvis', function () {
            $panel.find('input[data-column-index]').each(function () {
                var columnIndex = parseInt($(this).attr('data-column-index'), 10);
                if (!isNaN(columnIndex)) {
                    dt.column(columnIndex).visible(true, false);
                    this.checked = true;
                }
            });

            refreshTable(dt);
        });

        $button.addClass('vat-statement-colvis-button-active').attr('aria-expanded', 'true');
        positionPanel($panel, $button);
    }

    function toggle(dt, buttonNode) {
        if (typeof window.closeVatActionMenus === 'function') {
            window.closeVatActionMenus();
        }

        var $button = $(buttonNode);
        var $existing = getPanel();

        if ($existing.length && activeTable === dt) {
            closePanel();
            return;
        }

        closePanel();
        activeTable = dt;
        activeButton = $button;
        buildPanel(dt, $button);
    }

    $(document)
        .off('click.s560VatColvis')
        .on('click.s560VatColvis', function () {
            closePanel();
        })
        .off('keydown.s560VatColvis')
        .on('keydown.s560VatColvis', function (event) {
            if (event.key === 'Escape' || event.keyCode === 27) {
                closePanel();
            }
        })
        .off('preDraw.dt.s560VatColvis destroy.dt.s560VatColvis')
        .on('preDraw.dt.s560VatColvis destroy.dt.s560VatColvis', function () {
            closePanel();
        });

    $(window)
        .off('scroll.s560VatColvis resize.s560VatColvis pageshow.s560VatColvis')
        .on('scroll.s560VatColvis resize.s560VatColvis pageshow.s560VatColvis', function () {
            closePanel();
        });

    window.VatStatementColumnVisibility = {
        toggle: toggle,
        close: closePanel
    };
})(jQuery);
</script>
