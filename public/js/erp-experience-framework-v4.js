
(function(window, $){
  'use strict';
  window.ERP_EXF = window.ERP_EXF || {};
  window.ERP_EXF.version = '4.0.0';
  window.ERP_EXF.registry = window.ERP_EXF.registry || {};
  window.ERP_EXF.register = function(name, meta){ window.ERP_EXF.registry[name] = $.extend({registered_at: new Date().toISOString()}, meta || {}); };
  window.ERP_EXF.toast = function(message, type){
    type = type || 'success';
    if (window.toastr && toastr[type]) { toastr[type](message); } else { console.log(type.toUpperCase()+': '+message); }
  };
  window.ERP_EXF.refreshDataTables = function(scope){
    scope = scope || document;
    if (!$.fn.DataTable) return;
    $(scope).find('table.dataTable').each(function(){
      try { if ($.fn.DataTable.isDataTable(this)) { $(this).DataTable().columns.adjust(); } } catch(e) {}
    });
  };
  window.ERP_EXF.applyActionDropdowns = function(scope){
    scope = scope || document;
    $(scope).find('.dropdown, .btn-group').has('[data-toggle="dropdown"], .dropdown-toggle').addClass('erp-exf-action-dropdown');
  };
  $(document).ready(function(){
    ERP_EXF.register('tabs', {version:'1.0', status:'stable'});
    ERP_EXF.register('toolbar', {version:'1.0', status:'foundation'});
    ERP_EXF.register('datatable', {version:'1.0', status:'foundation'});
    ERP_EXF.register('page-header', {version:'1.0', status:'foundation'});
    ERP_EXF.applyActionDropdowns(document);
    setTimeout(function(){ ERP_EXF.refreshDataTables(document); }, 300);
  });
  $(document).on('draw.dt shown.bs.tab shown.bs.modal', function(e){
    ERP_EXF.applyActionDropdowns(document);
    ERP_EXF.refreshDataTables(document);
  });
})(window, jQuery);
