(function(){
    'use strict';
    window.ProductsNewReports = { init: function(){ document.querySelectorAll('.productsnew-table').forEach(function(table){ table.dataset.productsnewReport = 'ready'; }); } };
    document.addEventListener('DOMContentLoaded', window.ProductsNewReports.init);
})();
