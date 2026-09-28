(function($){'use strict';
function reindex(){
  $('#atn_segments_table tbody tr').each(function(index){
    $(this).find('.atn-segment-number').text(index+1);
    $(this).find('[data-name]').each(function(){
      $(this).attr('name','segments['+index+']['+$(this).data('name')+']');
    });
  });
}
function addSegment(){
  var tpl=document.getElementById('atn_segment_template');
  if(!tpl){return;}
  $('#atn_segments_table tbody').append($(tpl.content.cloneNode(true)));
  reindex();
  $('#atn_segments_table tbody tr:last select').each(function(){
    if($.fn.select2){$(this).select2({width:'100%'});}
  });
}
$(function(){
  $('#atn_add_segment').on('click',addSegment);
  $(document).on('click','.atn-remove-segment',function(){$(this).closest('tr').remove();reindex();});
  if($('#atn_segments_table').length){addSegment();}
  if($.fn.select2 && window.ATN_QUOTATION){
    $('.atn-ajax-passenger').select2({width:'100%',ajax:{url:window.ATN_QUOTATION.passengerLookup,dataType:'json',delay:250,data:function(p){return{q:p.term||''};},processResults:function(d){return{results:d};}}});
    $('.atn-ajax-corporate').select2({width:'100%',ajax:{url:window.ATN_QUOTATION.corporateLookup,dataType:'json',delay:250,data:function(p){return{q:p.term||''};},processResults:function(d){return{results:d};}}});
  }
});
})(window.jQuery);