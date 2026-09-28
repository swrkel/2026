(function ($) {
    'use strict';

    function initToolbar($toolbar) {
        var target = $toolbar.find('[data-target-table]').first().data('target-table');
        if (!target) return;
        var table = $(target).DataTable ? $(target).DataTable() : null;
        if (!table) return;

        $toolbar.find('.pg-global-search').off('keyup.pg').on('keyup.pg', function () {
            table.search(this.value).draw();
        });

        $toolbar.find('.pg-date-range').off('change.pg').on('change.pg', function () {
            table.draw();
        });

        $toolbar.find('.pg-export').off('click.pg').on('click.pg', function () {
            var type = $(this).data('type');
            var btn = type === 'excel' ? '.buttons-excel' : '.buttons-' + type;
            $(target).closest('.dataTables_wrapper').find(btn).first().trigger('click');
        });

        $toolbar.find('.pg-print').off('click.pg').on('click.pg', function () {
            $(target).closest('.dataTables_wrapper').find('.buttons-print').first().trigger('click');
        });
    }

    $(function () {
        $('[data-pg-toolbar="1"]').each(function () {
            initToolbar($(this));
        });
    });
})(jQuery);
