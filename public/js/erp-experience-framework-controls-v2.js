/* ERP Experience Framework V2 - small safe JS helpers */
(function(window, document){
  'use strict';
  window.ERP_EXF = window.ERP_EXF || {};
  window.ERP_EXF.version = window.ERP_EXF.version || {};
  window.ERP_EXF.version.controls = '2.0.0';
  window.ERP_EXF.applyButtonStandard = function(root){
    root = root || document;
    var map = [
      ['.erp-toolbar .btn:not(.erp-btn)', 'erp-btn erp-btn-light'],
      ['.dt-buttons .btn:not(.erp-btn)', 'erp-btn erp-btn-light']
    ];
    map.forEach(function(item){
      root.querySelectorAll(item[0]).forEach(function(el){ item[1].split(' ').forEach(function(cls){ el.classList.add(cls); }); });
    });
  };
  document.addEventListener('DOMContentLoaded', function(){ window.ERP_EXF.applyButtonStandard(document); });
})(window, document);
