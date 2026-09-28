(function(){
  window.BeautySaloons = window.BeautySaloons || {};
  window.BeautySaloons.initDatePickers = function(){
    if (window.jQuery && jQuery.fn.datepicker) {
      jQuery('.bs-datepicker').datepicker({autoclose:true,todayHighlight:true});
    }
  };
  document.addEventListener('DOMContentLoaded', window.BeautySaloons.initDatePickers);
})();
