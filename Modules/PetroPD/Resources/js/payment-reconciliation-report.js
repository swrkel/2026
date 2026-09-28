(function ($, window, document) {
    'use strict';
    var config = window.PetroPdPaymentReconciliationReport || {};
    var $table = $('#petropd_payment_reconciliation_table');
    if (!$table.length || !$.fn.DataTable) { return; }

    function integer(value) { var n = parseInt(value || 0, 10); return isNaN(n) ? 0 : n; }
    function setText(selector, value) { $(selector).text(value); }
    function updateSummary(s) {
        s = s || {};
        setText('#ppr_kpi_open_critical', integer(s.open_critical).toLocaleString());
        setText('#ppr_kpi_open_warnings', integer(s.open_warnings).toLocaleString());
        setText('#ppr_kpi_affected_shifts', integer(s.affected_shifts).toLocaleString());
        setText('#ppr_kpi_resolved', integer(s.resolved_events).toLocaleString());
        setText('#ppr_kpi_total', integer(s.total_events).toLocaleString());
        setText('#ppr_kpi_latest', s.latest_seen_at || '-');
    }
    function fiscalRange(offset) {
        var month = parseInt(config.financialYearStartMonth, 10) || 1;
        var today = moment();
        var year = (today.month() + 1 >= month ? today.year() : today.year() - 1) + (offset || 0);
        var start = moment({year: year, month: month - 1, day: 1}).startOf('day');
        return {start: start, end: start.clone().add(1, 'year').subtract(1, 'day').endOf('day')};
    }
    function applyPreset(value) {
        var now = moment(), start, end;
        if (value === 'this_year') { start = now.clone().startOf('year'); end = now.clone().endOf('year'); }
        else if (value === 'last_year') { start = now.clone().subtract(1, 'year').startOf('year'); end = now.clone().subtract(1, 'year').endOf('year'); }
        else if (value === 'this_fy') { var a = fiscalRange(0); start = a.start; end = a.end; }
        else if (value === 'last_fy') { var b = fiscalRange(-1); start = b.start; end = b.end; }
        else { return; }
        $('#ppr_start_date').val(start.format('YYYY-MM-DD'));
        $('#ppr_end_date').val(end.format('YYYY-MM-DD'));
    }
    function requestData(d) {
        d.date_basis = $('#ppr_date_basis').val(); d.start_date = $('#ppr_start_date').val(); d.end_date = $('#ppr_end_date').val();
        d.location_id = $('#ppr_location_id').val(); d.pump_operator_id = $('#ppr_pump_operator_id').val();
        d.settlement_no = $('#ppr_settlement_no').val(); d.shift_id = $('#ppr_shift_id').val(); d.pump_payment_id = $('#ppr_pump_payment_id').val();
        d.issue_type = $('#ppr_issue_type').val(); d.severity = $('#ppr_severity').val(); d.status = $('#ppr_status').val();
    }

    var table = $table.DataTable({
        processing: true, serverSide: true, responsive: false, scrollX: true, autoWidth: false,
        order: [[3, 'desc']], pageLength: 25, lengthMenu: [10, 25, 50, 75, 100],
        ajax: {url: config.url, data: requestData},
        dom: "<'row'<'col-sm-12'tr>><'row'<'col-sm-5'l><'col-sm-7'p>><'row'<'col-sm-12'i>>",
        buttons: [
            {extend:'csv', text:'<i class="fa fa-file-text-o"></i> CSV', className:'btn btn-default btn-sm', title:config.title, exportOptions:{columns:':visible:not(:first-child)'}},
            {extend:'excel', text:'<i class="fa fa-file-excel-o"></i> Excel', className:'btn btn-success btn-sm', title:config.title, exportOptions:{columns:':visible:not(:first-child)'}},
            {extend:'pdf', text:'<i class="fa fa-file-pdf-o"></i> PDF', className:'btn btn-danger btn-sm', title:config.title, orientation:'landscape', pageSize:'A3', exportOptions:{columns:':visible:not(:first-child)'}},
            {extend:'print', text:'<i class="fa fa-print"></i> Print', className:'btn btn-info btn-sm', title:config.title, exportOptions:{columns:':visible:not(:first-child)'}},
            {extend:'colvis', text:'<i class="fa fa-columns"></i> Column Visibility', className:'btn btn-default btn-sm', columns:':not(.always-visible)'}
        ],
        columns: [
            {data:'action', name:'action', orderable:false, searchable:false, className:'always-visible'},
            {data:'status_badge', name:'e.resolved_at'}, {data:'severity_badge', name:'e.severity'},
            {data:'last_seen_at', name:'e.last_seen_at'}, {data:'first_seen_at', name:'e.first_seen_at'},
            {data:'settlement_no', name:'e.settlement_no'}, {data:'shift_ids', name:'e.shift_ids'},
            {data:'location_name', name:'bl.name', defaultContent:'-'}, {data:'pump_operator_name', name:'po.name', defaultContent:'-'},
            {data:'pump_payment_id', name:'e.pump_payment_id'}, {data:'issue_label', name:'e.issue_type'},
            {data:'message', name:'e.message', orderable:false}, {data:'occurrence_count', name:'e.occurrence_count'},
            {data:'last_checked_at', name:'e.last_checked_at'}, {data:'resolved_by_name', name:'resolved_by_name', orderable:false},
            {data:'resolved_at', name:'e.resolved_at'}
        ],
        drawCallback: function () { this.api().columns.adjust(); }
    });
    table.buttons().container().appendTo('#ppr_buttons');
    $table.on('xhr.dt', function (e, settings, json) { if (json && json.summary) { updateSummary(json.summary); } });

    var timer;
    function reloadSoon(reset) { clearTimeout(timer); timer = setTimeout(function(){ table.ajax.reload(null, reset !== false); }, 220); }
    $('.ppr-filter').on('change', function(){ reloadSoon(true); });
    $('.ppr-filter-input').on('input', function(){ reloadSoon(true); });
    $('#ppr_date_preset').on('change', function(){ applyPreset(this.value); if (this.value !== 'custom') { reloadSoon(true); } });
    $('#ppr_start_date,#ppr_end_date').on('change', function(){ $('#ppr_date_preset').val('custom').trigger('change.select2'); reloadSoon(true); });
    $('#ppr_search').on('input', function(){ var value=this.value||''; clearTimeout(timer); timer=setTimeout(function(){ table.search(value).draw(); },250); });
    $('#ppr_reset_filters').on('click', function(){
        $('#ppr_search,#ppr_shift_id,#ppr_pump_payment_id').val(''); $('.ppr-filter').val('').trigger('change.select2');
        $('#ppr_date_basis').val('last_seen').trigger('change.select2'); $('#ppr_status').val('open').trigger('change.select2');
        $('#ppr_date_preset').val('this_year').trigger('change.select2'); applyPreset('this_year'); table.search('').draw();
    });
    $('.ppr-select2').select2({width:'100%'}); applyPreset($('#ppr_date_preset').val() || 'this_year');

    function route(template, id) { return (template || '').replace('__ID__', id); }
    function escapeHtml(value) { return $('<div>').text(value == null ? '' : value).html(); }
    function renderContext(context) {
        var json; try { json = JSON.stringify(context || {}, null, 2); } catch(e) { json = '{}'; }
        return '<pre class="ppr-context">' + escapeHtml(json) + '</pre>';
    }
    $(document).on('click', '.ppr-view-event', function(){
        var id=$(this).data('id'); $('#ppr_event_body').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div>'); $('#ppr_event_modal').modal('show');
        $.get(route(config.showUrl,id)).done(function(r){
            if (!r.success) { throw new Error(r.message || 'Unable to load event.'); }
            var e=r.event || {};
            var html='<div class="ppr-detail-grid">' +
                '<div><label>Status</label><strong>'+escapeHtml(e.status)+'</strong></div><div><label>Severity</label><strong>'+escapeHtml(e.severity)+'</strong></div>'+
                '<div><label>Settlement</label><strong>'+escapeHtml(e.settlement_no)+'</strong></div><div><label>Shift ID</label><strong>'+escapeHtml(e.shift_ids)+'</strong></div>'+
                '<div><label>Pump Operator</label><strong>'+escapeHtml(e.pump_operator)+'</strong></div><div><label>Pump Payment ID</label><strong>'+escapeHtml(e.pump_payment_id || '-')+'</strong></div>'+
                '<div><label>First Seen</label><strong>'+escapeHtml(e.first_seen_at)+'</strong></div><div><label>Last Seen</label><strong>'+escapeHtml(e.last_seen_at)+'</strong></div>'+
                '<div><label>Occurrences</label><strong>'+escapeHtml(e.occurrence_count)+'</strong></div><div><label>Last Checked</label><strong>'+escapeHtml(e.last_checked_at)+'</strong></div>'+
                '</div><div class="ppr-detail-section"><label>Issue</label><h4>'+escapeHtml(e.issue_label)+'</h4><p>'+escapeHtml(e.message)+'</p></div>'+
                '<div class="ppr-detail-section"><label>Resolution</label><p>'+escapeHtml(e.resolution_note || 'Not resolved.')+'</p></div>'+
                '<div class="ppr-detail-section"><label>Technical Evidence</label>'+renderContext(e.context)+'</div>';
            $('#ppr_event_body').html(html);
        }).fail(function(xhr){ $('#ppr_event_body').html('<div class="alert alert-danger">'+escapeHtml((xhr.responseJSON||{}).message || 'Unable to load event.')+'</div>'); });
    });
    $(document).on('click', '.ppr-recheck-event', function(){
        var $btn=$(this), id=$btn.data('id'); if ($btn.data('busy')) { return; }
        var execute=function(){
            $btn.data('busy',true).prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Rechecking');
            $.ajax({url:route(config.recheckUrl,id), method:'POST', data:{_token:config.csrfToken}})
                .done(function(r){
                    var icon=r.resolved?'success':'warning';
                    if (window.swal) { swal({title:r.resolved?'Resolved':'Still Open', text:r.message||'', icon:icon}); }
                    else { alert(r.message||'Recheck completed.'); }
                    table.ajax.reload(null,false);
                })
                .fail(function(xhr){ var msg=(xhr.responseJSON||{}).message||'Recheck failed.'; if(window.swal){swal('Recheck failed',msg,'error');}else{alert(msg);} })
                .always(function(){ $btn.data('busy',false).prop('disabled',false).html('<i class="fa fa-refresh"></i> Recheck'); });
        };
        if(window.swal){ swal({title:'Recheck this event?', text:'This reads the authoritative snapshot again. It will not edit any financial amount.', icon:'warning', buttons:true}).then(function(ok){if(ok){execute();}}); }
        else if(confirm('Recheck this event? No financial amount will be changed.')){ execute(); }
    });
})(jQuery, window, document);
