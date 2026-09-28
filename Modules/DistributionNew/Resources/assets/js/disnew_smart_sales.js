(function ($) {
  'use strict';
  $(document).ready(function () {
    if ($.fn.DataTable) {
      $('.disnew-datatable').DataTable({ responsive: true, pageLength: 25 });
    }
  });
})(jQuery);
