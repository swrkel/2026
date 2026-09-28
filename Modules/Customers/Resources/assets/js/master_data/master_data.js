(function () {
    'use strict';
    $(document).on('click', '.customers-master-table .dropdown-toggle', function () {
        $(this).closest('.customers-master-data-box').css('overflow', 'visible');
    });
})();
