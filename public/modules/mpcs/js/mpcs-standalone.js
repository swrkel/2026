(function ($) {
    'use strict';

    window.MPCS = window.MPCS || {};
    window.moment_date_format = window.moment_date_format || 'YYYY-MM-DD';
    window.ranges = window.ranges || {
        Today: [moment(), moment()],
        Yesterday: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    };
    window.LANG = $.extend({
        clear: 'Clear',
        apply: 'Apply',
        custom_range: 'Custom Range',
        please_wait: 'Please wait',
        no_data: 'No data available'
    }, window.LANG || {});

    window.MPCS.formatNumber = function (value, decimals) {
        var number = parseFloat(String(value || 0).replace(/,/g, ''));
        if (isNaN(number)) number = 0;
        var fixed = typeof decimals === 'number' ? number.toFixed(decimals) : String(number);
        var parts = fixed.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    };

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken() } });

    function normaliseDateValue(value) {
        if (!value) return '';
        var raw = String(value).trim();
        if (!raw || raw === '0000-00-00' || raw === 'Invalid date') return '';
        if (raw.indexOf('/') >= 0) raw = raw.replace(/\//g, '-');
        var m = moment(raw, ['YYYY-MM-DD', 'DD-MM-YYYY', 'MM-DD-YYYY', 'YYYY-MM-DD HH:mm:ss'], true);
        return m.isValid() ? m.format('YYYY-MM-DD') : raw;
    }

    function initialiseDateControls(context) {
        context = context || document;

        if ($.fn.datepicker) {
            $(context).find('input.datepicker, input.date-picker, input#datepicker, input[name="datepicker"], input[name="opening_date"], input[name*="date"], input[id$="_date"], input[id*="datepicker"]').not('[type="time"]').each(function () {
                var $input = $(this);
                if ($input.attr('type') === 'date') return;
                if ($input.data('datepicker')) return;
                if ($input.val()) $input.val(normaliseDateValue($input.val()));
                $input.datepicker({
                    autoclose: true,
                    todayHighlight: true,
                    format: 'yyyy-mm-dd'
                });
            });
        }

        if ($.fn.daterangepicker) {
            $(context).find('input[id*="date_range"], input[id*="date_ranges"], input[name="date_range"], input[name="date_ranges"]').each(function () {
                var $input = $(this);
                if ($input.data('daterangepicker')) return;
                var current = $input.val();
                var start = moment();
                var end = moment();
                if (current && (current.indexOf(' - ') > -1 || current.indexOf(' ~ ') > -1)) {
                    var parts = current.indexOf(' - ') > -1 ? current.split(' - ') : current.split(' ~ ');
                    var s = moment(parts[0], ['YYYY-MM-DD', window.moment_date_format], true);
                    var e = moment(parts[1], ['YYYY-MM-DD', window.moment_date_format], true);
                    if (s.isValid() && e.isValid()) { start = s; end = e; }
                }
                $input.daterangepicker({
                    startDate: start,
                    endDate: end,
                    ranges: window.ranges,
                    autoUpdateInput: false,
                    locale: {
                        format: window.moment_date_format,
                        cancelLabel: window.LANG.clear,
                        applyLabel: window.LANG.apply,
                        customRangeLabel: window.LANG.custom_range
                    }
                });
                $input.off('apply.daterangepicker.mpcs cancel.daterangepicker.mpcs');
                $input.on('apply.daterangepicker.mpcs', function (ev, picker) {
                    $(this).val(picker.startDate.format(window.moment_date_format) + ' - ' + picker.endDate.format(window.moment_date_format)).trigger('change').trigger('input');
                    var table = $(this).data('table');
                    if (table && $.fn.DataTable && $.fn.DataTable.isDataTable(table)) $(table).DataTable().ajax.reload();
                });
                $input.on('cancel.daterangepicker.mpcs', function () {
                    $(this).val('').trigger('change');
                });
            });
        }
    }

    window.MPCS.initialiseDateControls = initialiseDateControls;

    $(document).on('click', '[data-mpcs-print]', function () { window.print(); });

    // Standalone modal loader used by all MPCS Add Settings / Select Category buttons.
    $(document).on('click', '.btn-modal, .btn-modals, [data-toggle="modal"][data-href]', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var href = $btn.data('href') || $btn.attr('href');
        var container = $btn.data('container') || $btn.attr('data-container') || '.modal-container';
        if (!href || href === '#') return;
        var $container = $(container);
        if (!$container.length) $container = $('<div class="modal-container"></div>').appendTo('body');
        $btn.prop('disabled', true).addClass('disabled');
        $.get(href).done(function (html) {
            $container.html(html);
            var $modal = $container.find('.modal').first();
            if (!$modal.length && $container.hasClass('modal')) $modal = $container;
            if (!$modal.length) {
                $container.wrapInner('<div class="modal fade" tabindex="-1" role="dialog"><div class="modal-dialog modal-lg"><div class="modal-content"></div></div></div>');
                $modal = $container.find('.modal').first();
            }
            initialiseDateControls($modal);
            $modal.modal({backdrop: 'static', keyboard: true, show: true});
        }).fail(function (xhr) {
            alert((xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'Unable to load this form. Please refresh the page and try again.');
        }).always(function () {
            $btn.prop('disabled', false).removeClass('disabled');
        });
    });

    // AJAX form submit fallback for MPCS modals/settings where original ERP JS is not present.
    $(document).on('submit', '.modal form', function (e) {
        var $form = $(this);
        if ($form.data('normal-submit') || $form.attr('method') === 'get') return true;
        e.preventDefault();
        var $submit = $form.find(':submit').prop('disabled', true);
        $.ajax({
            method: ($form.attr('method') || 'POST').toUpperCase(),
            url: $form.attr('action'),
            data: $form.serialize(),
            success: function (res) {
                if (res && res.success === false) { alert(res.msg || 'Unable to save.'); return; }
                $form.closest('.modal').modal('hide');
                window.location.reload();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg)) || 'Unable to save. Please check the fields and try again.';
                alert(msg);
            },
            complete: function () { $submit.prop('disabled', false); }
        });
        return false;
    });

    $(document).on('shown.bs.modal', '.modal', function () { initialiseDateControls(this); });

    $(document).on('click', '.sidebar-menu .treeview > a', function (e) {
        e.preventDefault();
        var $li = $(this).parent();
        $('.sidebar-menu .treeview').not($li).removeClass('active active-sub menu-open').children('.treeview-menu').slideUp(150);
        $li.toggleClass('active active-sub menu-open').children('.treeview-menu').slideToggle(150);
    });

    $(function () {
        initialiseDateControls(document);
        $('.sidebar-menu .treeview.active, .sidebar-menu .treeview.active-sub').addClass('menu-open').children('.treeview-menu').show();
        $('.mpcs-page-nav, .mpcs-inline-sidebar, .mpcs-content-sidebar, .mpcs-duplicated-sidebar').remove();
    });
})(jQuery);


/* IS1533 MPCS one-time stability pack: modal buttons, settings saves, date ranges, default dates, menu cleanup */
(function ($) {
    'use strict';
    if (!$) return;

    function notifyOk(msg){ if (window.toastr) toastr.success(msg || 'Saved successfully.'); else alert(msg || 'Saved successfully.'); }
    function notifyErr(msg){ if (window.toastr) toastr.error(msg || 'Unable to complete the request.'); else alert(msg || 'Unable to complete the request.'); }

    function initMpcsDates(ctx) {
        ctx = ctx || document;
        var today = moment ? moment().format('YYYY-MM-DD') : (new Date()).toISOString().slice(0,10);
        $(ctx).find('input[type="date"]').each(function(){ if (!this.value) this.value = today; });
        if ($.fn.datepicker) {
            $(ctx).find('input.datepicker,input.date-picker,input#datepicker,input[name="datepicker"],input[name*="date"]')
                .not('[type="date"]').each(function(){
                    var $i=$(this);
                    if (!$i.val()) $i.val(today);
                    if (!$i.data('datepicker')) $i.datepicker({autoclose:true,todayHighlight:true,format:'yyyy-mm-dd'});
                });
        }
        if ($.fn.daterangepicker) {
            $(ctx).find('input[name="date_range"],input[name="date_ranges"],input[id*="date_range"],input[id*="date_ranges"]').each(function(){
                var $i=$(this); if ($i.data('daterangepicker')) return;
                $i.daterangepicker({autoUpdateInput:false,ranges:window.ranges||{},locale:{format:window.moment_date_format||'YYYY-MM-DD',applyLabel:(window.LANG&&window.LANG.apply)||'Apply',cancelLabel:(window.LANG&&window.LANG.clear)||'Clear'}});
                $i.on('apply.daterangepicker',function(ev,p){
                    $(this).val(p.startDate.format(window.moment_date_format||'YYYY-MM-DD')+' - '+p.endDate.format(window.moment_date_format||'YYYY-MM-DD')).trigger('change');
                    $(this).closest('form').trigger('submit');
                    $('.dataTable').each(function(){ try { if ($.fn.DataTable.isDataTable(this)) $(this).DataTable().ajax.reload(); } catch(e){} });
                });
                $i.on('cancel.daterangepicker',function(){ $(this).val('').trigger('change'); });
            });
        }
    }

    $(document).ajaxComplete(function(){ initMpcsDates(document); });
    $(document).on('shown.bs.modal', '.modal', function(){ initMpcsDates(this); });

    $(document).on('submit', '#f25_settings_modal form, #f25_delivery_modal form, form[data-mpcs-ajax="true"]', function(e){
        e.preventDefault();
        var $form=$(this), $btn=$form.find(':submit,button[type="submit"],#add_16a_form_settings_submit_button').prop('disabled',true);
        $.ajax({url:$form.attr('action'), method:($form.attr('method')||'POST'), data:$form.serialize(), dataType:'json'})
            .done(function(res){ if(res && res.success===false){ notifyErr(res.msg); return; } notifyOk((res&&res.msg)||'Saved successfully.'); window.location.reload(); })
            .fail(function(xhr){ notifyErr((xhr.responseJSON&&(xhr.responseJSON.message||xhr.responseJSON.msg)) || xhr.responseText || 'Unable to save.'); })
            .always(function(){ $btn.prop('disabled',false); });
        return false;
    });

    $(document).on('click', '#add_16a_form_settings_submit_button', function(e){
        var $form=$('#add_16a_form_settings');
        if ($form.length) { e.preventDefault(); $form.trigger('submit'); return false; }
    });

    $(function(){
        initMpcsDates(document);
        // IS1540 root hardening: MPCS-wide tab coloring and cleanup of duplicate/in-page sidebars.
        $('.mpcs-content-wrapper .nav-tabs li a, .content .nav-tabs li a').each(function(i){
            var colors=['#0d6efd','#198754','#fd7e14','#6f42c1','#20c997','#dc3545','#0dcaf0'];
            $(this).css({'background-color': colors[i % colors.length], 'color':'#fff', 'border-radius':'4px 4px 0 0', 'margin-right':'3px'});
        });
        $('.mpcs-content-wrapper').find('ul.sidebar-menu, .mpcs-inline-sidebar, .mpcs-page-nav, .mpcs-duplicated-sidebar').remove();
        $('.main-sidebar').show();
        $('.sidebar-menu .treeview.active, .sidebar-menu .treeview.active-sub').addClass('menu-open').children('.treeview-menu').show();
    });
})(window.jQuery);


/* IS1579 final MPCS safeguards: settings saves, product/category loading, current dates, tab colours */
(function ($) {
    'use strict';
    if (!$) return;

    function ok(msg){ if (window.toastr) toastr.success(msg || 'Saved successfully.'); else alert(msg || 'Saved successfully.'); }
    function err(msg){ if (window.toastr) toastr.error(msg || 'Unable to complete.'); else alert(msg || 'Unable to complete.'); }
    function today(){ return (window.moment ? moment().format('YYYY-MM-DD') : (new Date()).toISOString().slice(0,10)); }

    function applyMpcsUiFixes(ctx) {
        ctx = ctx || document;
        $(ctx).find('input[type="date"], input#form_16a_date').each(function(){ if (!this.value) this.value = today(); });
        $('.mpcs-content-wrapper').children('ul.sidebar-menu').remove();
        $('.mpcs-content-wrapper .content').children('ul.sidebar-menu, ul.treeview-menu').remove();
        $('.mpcs-content-wrapper .nav-tabs > li > a').each(function(i){
            var colors=['#0d6efd','#198754','#fd7e14','#6f42c1','#20c997','#dc3545','#0dcaf0'];
            $(this).css({'background-color':colors[i%colors.length],'color':'#fff','border-radius':'4px 4px 0 0','margin-right':'3px'});
        });
    }

    $(document).on('shown.bs.modal ajaxComplete ready', function(){ applyMpcsUiFixes(document); });
    $(function(){ applyMpcsUiFixes(document); });

    // Reliable modal submit for settings forms that previously saved only after refresh or did not close.
    $(document).on('submit', '#add_15_form_settings, #add_9c_form_settings, #add_16a_form_settings, #f25_settings_modal form', function(e){
        e.preventDefault();
        var $form=$(this), $btn=$form.find(':submit,button[type="submit"]').prop('disabled',true);
        $.ajax({url:$form.attr('action'), method:($form.attr('method')||'POST'), data:$form.serialize(), dataType:'json'})
            .done(function(res){
                if (res && (res.success===false || res.success===0)) { err(res.msg || res.message); return; }
                ok((res && (res.msg || res.message)) || 'Saved successfully.');
                $form.closest('.modal').modal('hide');
                $('.dataTable').each(function(){ try { if ($.fn.DataTable.isDataTable(this)) $(this).DataTable().ajax.reload(null, false); } catch(e){} });
                setTimeout(function(){ window.location.reload(); }, 500);
            })
            .fail(function(xhr){ err((xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message || xhr.responseJSON.error)) || 'Unable to save. Please check the form and try again.'); })
            .always(function(){ $btn.prop('disabled',false); });
        return false;
    });

    // F15 product category selector fallback if page-specific script was not attached after cache/minify.
    $(document).on('click', '#select_product_categories_btn', function(e){
        if ($('#f15_categories_modal').is(':visible')) return;
        if (typeof window.loadCategoriesModal === 'function') return;
        e.preventDefault();
        $.get($(this).data('href') || (window.location.origin + '/mpcs/f15-categories'))
            .done(function(res){
                var selected=(res.selected_ids||[]).map(function(x){return parseInt(x,10);});
                var all=res.all_categories||[];
                var html='<div class="modal fade" id="f15_categories_modal_fallback"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4>Select Product Categories</h4></div><div class="modal-body"><select id="f15_fallback_categories" multiple class="form-control" style="height:360px">';
                $.each(all,function(_,c){ html+='<option value="'+c.id+'" '+(selected.indexOf(parseInt(c.id,10))>=0?'selected':'')+'>'+c.name+'</option>'; });
                html+='</select></div><div class="modal-footer"><button class="btn btn-primary" id="f15_fallback_save">Save</button><button class="btn btn-default" data-dismiss="modal">Close</button></div></div></div></div>';
                $('#f15_categories_modal_fallback').remove(); $('body').append(html); $('#f15_categories_modal_fallback').modal('show');
            }).fail(function(){ err('Unable to load product categories.'); });
    });
    $(document).on('click','#f15_fallback_save',function(){
        $.post(window.location.origin + '/mpcs/f15-categories/save', {_token:$('meta[name="csrf-token"]').attr('content'), category_ids:$('#f15_fallback_categories').val()||[]})
            .done(function(res){ ok((res&&res.msg)||'Saved successfully.'); $('#f15_categories_modal_fallback').modal('hide'); setTimeout(function(){window.location.reload();},500); })
            .fail(function(){ err('Unable to save product categories.'); });
    });
})(window.jQuery);

/* IS1584 – MPCS one-time root fix: settings save, category selector, F18 live table, F22 products/link accounts, F25 date, UI cleanup */
(function ($) {
    'use strict';
    if (!$) return;

    function token(){ return $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val() || ''; }
    function ok(msg){ if (window.toastr) toastr.success(msg || 'Saved successfully.'); else alert(msg || 'Saved successfully.'); }
    function fail(msg){ if (window.toastr) toastr.error(msg || 'Unable to save. Please check and try again.'); else alert(msg || 'Unable to save. Please check and try again.'); }
    function today(){ return (window.moment ? moment().format('YYYY-MM-DD') : (new Date()).toISOString().slice(0,10)); }

    function reloadTables(){
        $('.dataTable').each(function(){
            try { if ($.fn.DataTable && $.fn.DataTable.isDataTable(this)) { $(this).DataTable().ajax.reload(null, false); } } catch(e){}
        });
    }

    function initMpcsRootUi(ctx) {
        ctx = ctx || document;
        // Current date on F16A and other empty date sections.
        $(ctx).find('input[type="date"], input#form_16a_date, input[name="date"], input[name="form_date"]').each(function(){ if (!this.value) this.value = today(); });

        // Remove duplicated/inline sidebars and old page nav blocks from all MPCS pages.
        $('.mpcs-inline-sidebar, .mpcs-page-nav, .mpcs-content-sidebar, .mpcs-duplicated-sidebar').remove();
        $('.content ul.sidebar-menu, .content .treeview-menu, .main-content-inner ul.sidebar-menu').remove();

        // Hide the old extra F14 date picker that is shown above the form.
        if (location.pathname.toLowerCase().indexOf('/mpcs/f14') !== -1) {
            $('#form_9a_date').closest('.form-group, .col-md-3, .col-md-4, .col-md-6').hide();
            $('input[name="datepicker"][id="form_9a_date"]').closest('.form-group, .col-md-3, .col-md-4, .col-md-6').hide();
        }

        // System standard colored tabs for MPCS.
        var colors = ['#0d6efd','#198754','#fd7e14','#6f42c1','#20c997','#dc3545','#0dcaf0','#795548'];
        $('.settlement_tabs .nav-tabs > li > a, .mpcs-content-wrapper .nav-tabs > li > a, .content .nav-tabs > li > a').each(function(i){
            $(this).css({'background-color': colors[i % colors.length], 'color':'#fff', 'border-radius':'4px 4px 0 0', 'margin-right':'3px', 'border':'0'});
        });

        // Ensure select2 dropdowns work even after modal load.
        if ($.fn.select2) { $(ctx).find('select.select2').each(function(){ try { if (!$(this).data('select2')) $(this).select2({width:'100%'}); } catch(e){} }); }
    }

    $(document).ajaxComplete(function(){ initMpcsRootUi(document); });
    $(document).on('shown.bs.modal', '.modal', function(){ initMpcsRootUi(this); });
    $(function(){ initMpcsRootUi(document); });

    // One handler for all MPCS setting saves that were not saving/refreshing correctly.
    $(document).on('submit', '#add_15_form_settings, #add_9c_form_settings, #add_16a_form_settings, #add_20_form_settings, #f25_settings_modal form, form[action*="store-15-form"], form[action*="store-9c"], form[action*="store-16a"], form[action*="store-20-form"], form[action*="F25/settings"]', function(e){
        var $form = $(this);
        if ($form.data('normal-submit')) return true;
        e.preventDefault();
        var $btn = $form.find(':submit, button[type="submit"]').prop('disabled', true);
        $.ajax({
            url: $form.attr('action'),
            method: ($form.attr('method') || 'POST'),
            data: $form.serialize(),
            dataType: 'json'
        }).done(function(res){
            if (res && (res.success === false || res.success === 0)) { fail(res.msg || res.message); return; }
            ok((res && (res.msg || res.message)) || 'Saved successfully.');
            $form.closest('.modal').modal('hide');
            reloadTables();
            setTimeout(function(){ window.location.reload(); }, 400);
        }).fail(function(xhr){
            fail((xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message || xhr.responseJSON.error)) || 'Unable to save.');
        }).always(function(){ $btn.prop('disabled', false); });
        return false;
    });

    // F15 category selector reliable modal.
    $(document).off('click.is1584f15').on('click.is1584f15', '#select_product_categories_btn, #selected_product_categories_btn', function(e){
        e.preventDefault();
        var href = $(this).data('href') || '/mpcs/f15-categories';
        $.get(href).done(function(res){
            var selected = (res.selected_ids || []).map(function(x){ return String(x); });
            var cats = res.all_categories || [];
            var html = '<div class="modal fade" id="is1584_f15_categories_modal"><div class="modal-dialog modal-lg"><div class="modal-content">'
                + '<div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4>Select the product categories to show</h4></div>'
                + '<div class="modal-body"><select id="is1584_f15_categories" class="form-control" multiple style="height:380px;width:100%">';
            $.each(cats, function(_, c){ html += '<option value="'+c.id+'" '+(selected.indexOf(String(c.id)) >= 0 ? 'selected' : '')+'>'+c.name+'</option>'; });
            html += '</select></div><div class="modal-footer"><button type="button" class="btn btn-primary" id="is1584_save_f15_categories">Save</button><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div></div></div></div>';
            $('#is1584_f15_categories_modal').remove();
            $('body').append(html);
            if ($.fn.select2) $('#is1584_f15_categories').select2({width:'100%'});
            $('#is1584_f15_categories_modal').modal('show');
        }).fail(function(){ fail('Unable to load product categories.'); });
    });
    $(document).off('click.is1584f15save').on('click.is1584f15save', '#is1584_save_f15_categories', function(){
        $.post('/mpcs/f15-categories/save', {_token: token(), category_ids: $('#is1584_f15_categories').val() || []})
            .done(function(res){ if (res && res.success === false) { fail(res.msg); return; } ok((res && res.msg) || 'Saved successfully.'); $('#is1584_f15_categories_modal').modal('hide'); reloadTables(); })
            .fail(function(xhr){ fail((xhr.responseJSON && xhr.responseJSON.msg) || 'Unable to save categories.'); });
    });

    // F18 prefix values: save and show in table immediately without full refresh.
    $(document).on('submit', 'form[action*="F18"][action*="prefix"], form#f18_prefix_numbers_form', function(e){
        var $form = $(this); if ($form.data('normal-submit')) return true;
        e.preventDefault();
        $.ajax({url:$form.attr('action'), method:($form.attr('method')||'POST'), data:$form.serialize(), dataType:'json'})
            .done(function(res){ if (res && res.success === false) { fail(res.msg); return; } ok((res && res.msg) || 'Saved successfully.'); reloadTables(); })
            .fail(function(){ fail('Unable to save prefix and numbers.'); });
        return false;
    });

    // F22 link accounts and stock taking: prevent silent non-save and reload the list after save.
    $(document).on('submit', 'form[action*="F22"][action*="link"], form#f22_link_accounts_form, form[action*="f22-link"]', function(e){
        var $form = $(this); if ($form.data('normal-submit')) return true;
        e.preventDefault();
        $.ajax({url:$form.attr('action'), method:($form.attr('method')||'POST'), data:$form.serialize(), dataType:'json'})
            .done(function(res){ if (res && res.success === false) { fail(res.msg); return; } ok((res && res.msg) || 'Saved successfully.'); reloadTables(); })
            .fail(function(xhr){ fail((xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message)) || 'Unable to save linked accounts.'); });
        return false;
    });

    // F25 settings opening date: preserve selected opening date after save.
    $(document).on('change', '#f25_settings_tab input[name="opening_date"], #f25_settings_modal input[name="opening_date"]', function(){ localStorage.setItem('mpcs_f25_opening_date', this.value || ''); });
    $(function(){
        var d = localStorage.getItem('mpcs_f25_opening_date');
        if (d) $('#f25_settings_tab input[name="opening_date"], #f25_settings_modal input[name="opening_date"]').val(d);
    });
})(window.jQuery);
