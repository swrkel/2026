@extends('layouts.app')
@section('title', __('mpcs::lang.F18_form'))

@section('content')
    <!-- Main content -->
    <section class="content" style="padding-top:0;">
        <div class="row" style="margin-top:0;">
            <div class="col-md-12" style="padding-top:0;">
                <div class="settlement_tabs" id="mpcs_f18_tabs" data-mpcs-tabs>
                    <ul class="nav nav-tabs no-print">
                        <li class="active">
                            <a href="#f18_form_tab" class="f18_form_tab" data-toggle="tab">
                                <i class="fa fa-file-text-o"></i>
                                <strong>@lang('mpcs::lang.F18_form')</strong>
                            </a>
                        </li>

                        <li>
                            <a href="#f18_prefix_numbers_tab" class="f18_prefix_numbers_tab" data-toggle="tab">
                                <i class="fa fa-list-ol"></i>
                                <strong>@lang('mpcs::lang.prefix_and_numbers')</strong>
                            </a>
                        </li>

                        <li>
                            <a href="#f18_list_tab" data-toggle="tab">
                                <i class="fa fa-list"></i>
                                <strong>List F 18 Forms</strong>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="f18_form_tab">
                            @include('mpcs::forms.F18.partials.f18_form')
                        </div>

                        <div class="tab-pane" id="f18_prefix_numbers_tab">
                            @include('mpcs::forms.F18.partials.prefix_numbers')
                        </div>

                        <div class="tab-pane" id="f18_list_tab">
                            @include('mpcs::forms.F18.partials.f18_list')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
@endsection

@section('javascript')
    @include('mpcs::partials.safe_tabs')
    <script type="text/javascript">
        var f18CurrentUserId   = '{{ auth()->id() }}';
        var f18CurrentUserName = '{{ trim(auth()->user()->first_name . " " . auth()->user()->last_name) }}';
        var f18QtyPrecision    = {{ $qty_precision ?? 2 }};

        $(document).ready(function() {
            // Page-scoped F18 navigation. Prefix & Numbers and List F18 remain
            // usable even when the application's global Bootstrap tab binding is absent.
            var $f18Tabs = $('#mpcs_f18_tabs');

            function activateF18Tab(target, updateHash) {
                var $link = $f18Tabs.children('.nav-tabs').find('a[href="' + target + '"]').first();
                var $pane = $f18Tabs.children('.tab-content').children(target);

                if (!$link.length || !$pane.length) {
                    return false;
                }

                $f18Tabs.children('.nav-tabs').find('li').removeClass('active');
                $link.closest('li').addClass('active');
                $f18Tabs.children('.tab-content').children('.tab-pane').removeClass('active in').hide();
                $pane.addClass('active in').show();

                $f18Tabs.children('.nav-tabs').find('a').attr('aria-expanded', 'false');
                $link.attr('aria-expanded', 'true');

                if (target === '#f18_list_tab' && $.fn.dataTable && $.fn.dataTable.isDataTable('#f18-list-table')) {
                    $('#f18-list-table').DataTable().columns.adjust();
                }

                if (target === '#f18_prefix_numbers_tab' && $.fn.select2) {
                    $pane.find('.select2').each(function() {
                        $(this).trigger('change.select2');
                    });
                }

                $link.trigger('shown.bs.tab');

                if (updateHash && window.history && window.history.replaceState) {
                    window.history.replaceState(null, document.title, window.location.pathname + window.location.search + target);
                }

                return true;
            }

            $f18Tabs.children('.nav-tabs').find('a[data-toggle="tab"]')
                .off('click.f18SafeTabs')
                .on('click.f18SafeTabs', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    activateF18Tab($(this).attr('href'), true);
                    return false;
                });

            var initialF18Tab = window.location.hash || '#f18_form_tab';
            if (!activateF18Tab(initialF18Tab, false)) {
                activateF18Tab('#f18_form_tab', false);
            }

            // ─── Helpers ────────────────────────────────────────────────────────────
            function formatAmount(val, decimals) {
                decimals = decimals !== undefined ? decimals : 2;
                var n = parseFloat(val) || 0;
                return n.toLocaleString('en-US', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            }

            function formatQty(val) {
                return formatAmount(val, f18QtyPrecision);
            }

            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%',
                    minimumResultsForSearch: 0
                });
                $('#f18_category_id').select2('destroy').select2({
                    width: '100%',
                    minimumResultsForSearch: 0,
                    allowClear: true,
                    placeholder: "@lang('lang_v1.all')"
                });
                $('#f18_product_id').select2('destroy').select2({
                    width: '100%',
                    minimumResultsForSearch: 0,
                    allowClear: true,
                    placeholder: "@lang('lang_v1.all')"
                });
                $('#f18_sub_category_ids').select2('destroy').select2({
                    width: '100%',
                    minimumResultsForSearch: 0,
                    allowClear: true,
                    placeholder: "@lang('lang_v1.all')"
                });
            }

            // ─── F18 No: update dynamically when Transferred To changes ─────────────
            function updateFormNo() {
                var to_loc = $('#f18_to_location_id').val();
                var form_date = $('#f18_date').val();
                if (!to_loc) { $('#f18_form_no').val(''); return; }
                $.get("{{ route('F18.form_no') }}", { to_location_id: to_loc, form_date: form_date }, function(resp) {
                    $('#f18_form_no').val(resp.form_no || '');
                });
            }

            $('#f18_to_location_id').on('change', updateFormNo);

            // ─── Date Pickers (Same as F16A) ──────────────────────────────────────────
            if ($.fn.daterangepicker) {
                var drpOpts = {
                    singleDatePicker: true,
                    showDropdowns: true,
                    autoUpdateInput: true,
                    showCustomRangeLabel: true,
                    locale: { format: 'YYYY-MM-DD', customRangeLabel: 'Custom Range' },
                    ranges: {
                        'Today':     [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Custom Date Range': [moment().startOf('month'), moment().endOf('month')],
                    }
                };

                var drpCallback = function(start, end, label) {
                    if (label === 'Custom Date Range') {
                        var target = $(this.element).attr('id');
                        $('#target_custom_date_input').val(target);
                        
                        // Reset to previous value or today to avoid weird jumps
                        var prevDate = $(this.element).data('prev-date') || moment().format('YYYY-MM-DD');
                        $(this.element).val(prevDate);
                        $(this.element).data('daterangepicker').setStartDate(moment(prevDate));
                        $(this.element).data('custom-range-active', true);
                        
                        $('.custom_date_typing_modal').modal('show');
                    }
                };

                $('#f18_opening_date').daterangepicker(drpOpts, drpCallback);
                $('#f18_date').daterangepicker(drpOpts, drpCallback);

                $('#f18_opening_date, #f18_date').on('apply.daterangepicker', function(ev, picker) {
                    if ($(this).data('custom-range-active')) {
                        $(this).removeData('custom-range-active');
                        return;
                    }
                    var formatted = picker.startDate.format('YYYY-MM-DD');
                    $(this).val(formatted);
                    $(this).data('prev-date', formatted);
                    if ($(this).attr('id') === 'f18_date') {
                        updateFormNo();
                        // Sync date display below business name
                        $('#f18_form_date_display').text(formatted);
                    }
                });
                
                // Initialize values
                var today = moment().format('YYYY-MM-DD');
                $('#f18_opening_date, #f18_date').val(today);
                $('#f18_opening_date, #f18_date').data('prev-date', today);
                if ($('#f18_opening_date').data('daterangepicker')) {
                    $('#f18_opening_date, #f18_date').each(function() {
                        $(this).data('daterangepicker').setStartDate(moment(today));
                    });
                }
            }

            // ─── Custom Date Modal Submit ───────────────────────────────────────────
            $('#custom_date_apply_button').on('click', function() {
                var targetId = $('#target_custom_date_input').val();
                if (!targetId) return;

                var fromDateStr = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + 
                                  $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + 
                                  $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();

                var toDateStr   = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + 
                                  $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + 
                                  $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                var fromMoment = moment(fromDateStr, 'YYYY-MM-DD', true);
                var toMoment   = moment(toDateStr,   'YYYY-MM-DD', true);

                if (fromMoment.isValid() && toMoment.isValid()) {
                    var $el = $('#' + targetId);
                    var drp = $el.data('daterangepicker');

                    if (drp) {
                        if (drp.singleDatePicker) {
                            $el.val(fromDateStr);
                            $el.data('prev-date', fromDateStr);
                            drp.setStartDate(fromMoment);
                            drp.setEndDate(fromMoment);
                        } else {
                            var val = fromDateStr + ' - ' + toDateStr;
                            $el.val(val);
                            drp.setStartDate(fromMoment);
                            drp.setEndDate(toMoment);
                            
                            // Specific for f18_list_date_range
                            if (targetId === 'f18_list_date_range' && typeof updateDateLabel === 'function') {
                                updateDateLabel(fromMoment, toMoment);
                            }
                        }
                    }

                    if (targetId === 'f18_date') {
                        updateFormNo();
                        // Sync date display below business name
                        $('#f18_form_date_display').text(fromDateStr);
                    }
                    
                    $('.custom_date_typing_modal').modal('hide');
                } else {
                    alert("Please enter a valid date range.");
                }
            });

            // ─── Prefix & Numbers AJAX submit ────────────────────────────────────────
            $('#f18_prefix_numbers_form').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $btn = $('#f18_prefix_save_btn');
                $btn.attr('disabled', true);

                $.ajax({
                    method: $form.attr('method'),
                    url: $form.attr('action'),
                    data: $form.serialize(),
                    success: function(result) {
                        if (result && result.success) {
                            toastr.success(result.msg || 'Saved successfully');
                            window.location.reload();
                        } else {
                            toastr.error(result && result.msg ? result.msg : 'Save failed');
                        }
                    },
                    error: function() { toastr.error('Error saving prefix settings'); },
                    complete: function() { $btn.attr('disabled', false); }
                });
            });

            // ─── Cascading Product Filters ──────────────────────────────────────────
            function reloadF18Products() {
                var category_id = $('#f18_category_id').val();
                var sub_id = $('#f18_sub_category_ids').val();
                var sub_ids = sub_id ? [sub_id] : [];

                $.get("{{ route('F18.products') }}", {
                    category_id: category_id,
                    sub_category_ids: sub_ids
                }, function(products) {
                    var $select = $('#f18_product_id');
                    $select.empty().append($('<option>').val('').text("@lang('lang_v1.all')"));
                    $.each(products, function(_, p) {
                        $select.append($('<option>').val(p.id).text(p.name));
                    });
                    $select.trigger('change.select2');
                });
            }

            $('#f18_category_id').on('change', function() {
                var category_id = $(this).val();
                $.get('/mpcs/get-sub-categories', { category_id: category_id }, function(resp) {
                    var $sub = $('#f18_sub_category_ids');
                    $sub.empty().append($('<option>').val('').text("@lang('lang_v1.all')"));
                    $.each(resp.sub_categories || {}, function(id, name) {
                        $sub.append($('<option>').val(id).text(name));
                    });
                    $sub.val('').trigger('change.select2');
                }).always(reloadF18Products);
            });

            $('#f18_sub_category_ids').on('change', reloadF18Products);

            // ─── Table Data and Calculations ────────────────────────────────────────
            var f18Rows = [];
            var f18IsSubmitting = false;

            function recalcTotals() {
                var totals = { IP: 0, IS: 0, RP: 0, RS: 0 };
                f18Rows.forEach(function(r) {
                    totals.IP += (r.issued_purchase_unit_price * r.qty);
                    totals.IS += (r.issued_sale_unit_price * r.qty);
                    totals.RP += (r.received_purchase_unit_price * r.qty);
                    totals.RS += (r.received_sale_unit_price * r.qty);
                });
                $('#f18_total_issued_purchase').text(formatAmount(totals.IP));
                $('#f18_total_issued_sale').text(formatAmount(totals.IS));
                $('#f18_total_received_purchase').text(formatAmount(totals.RP));
                $('#f18_total_received_sale').text(formatAmount(totals.RS));
            }

            function renderTable() {
                var $tbody = $('#f18_table tbody');
                $tbody.empty();

                f18Rows.forEach(function(r, idx) {
                    var $tr = $('<tr>');
                    $tr.append($('<td>').css('text-align', 'center').text(idx + 1));
                    $tr.append($('<td>').text(r.product_name));
                    
                    var $qtyInput = $('<input type="number" class="form-control f18-qty-input" style="width:80px; text-align:right;">')
                        .val(r.qty)
                        .on('change', function() {
                            var newQty = parseFloat($(this).val()) || 0;
                            r.qty = newQty;
                            renderTable();
                            recalcTotals();
                        });
                    $tr.append($('<td>').append($qtyInput));

                    function priceCell(val) {
                        var $td = $('<td>').addClass('f18-amount').css('text-align', 'right');
                        if (r._loading) {
                            $td.html('<i class="fa fa-spinner fa-spin text-muted"></i>');
                        } else {
                            $td.text(formatAmount(val));
                        }
                        return $td;
                    }

                    $tr.append(priceCell(r.issued_purchase_unit_price));
                    $tr.append(priceCell(r.issued_purchase_unit_price * r.qty));
                    /*
                     * IS2013: Issued Location / Sale Price / Unit must show the
                     * unit SALE price.
                     *
                     * This deliberately rendered issued_purchase_unit_price here
                     * (the previous comment read "shows purchase price per client
                     * request"), which is why the Unit column under Sale Price
                     * repeated the purchase figure - 1,984.83 in the reported
                     * screenshot - while the Total beside it was already computed
                     * from issued_sale_unit_price. The row was internally
                     * inconsistent: unit x qty did not equal its own total.
                     *
                     * Now both cells come from issued_sale_unit_price, so the
                     * Issued and Received sections read the same way.
                     */
                    $tr.append(priceCell(r.issued_sale_unit_price));
                    $tr.append(priceCell(r.issued_sale_unit_price * r.qty));
                    $tr.append(priceCell(r.received_purchase_unit_price));
                    $tr.append(priceCell(r.received_purchase_unit_price * r.qty));
                    $tr.append(priceCell(r.received_sale_unit_price));
                    $tr.append(priceCell(r.received_sale_unit_price * r.qty));

                    $tr.append($('<td>')); // Office use

                    var $remove = $('<button type="button" class="btn btn-xs btn-danger no-print">&times;</button>')
                        .on('click', function() {
                            f18Rows.splice(idx, 1);
                            renderTable();
                            recalcTotals();
                        });
                    $tr.append($('<td class="no-print">').append($remove));

                    $tbody.append($tr);
                });
            }

            // ─── Add Row ───────────────────────────────────────────────────────────
            $('#f18_add_row').on('click', function() {
                var product_id = $('#f18_product_id').val();
                var product_name = $('#f18_product_id option:selected').text();
                var qty = parseFloat($('#f18_qty').val() || 0);

                if (!product_id || qty <= 0) {
                    toastr.warning('Please select product and enter quantity');
                    return;
                }

                var rowObj = {
                    product_id: product_id,
                    product_name: product_name,
                    qty: qty,
                    issued_purchase_unit_price: 0, issued_sale_unit_price: 0,
                    received_purchase_unit_price: 0, received_sale_unit_price: 0,
                    _loading: true
                };
                f18Rows.unshift(rowObj);
                renderTable();

                $('#f18_qty').val('').focus();

                $.get("{{ route('F18.product_prices') }}", { product_id: product_id }, function(resp) {
                    if (resp.success) {
                        rowObj.issued_purchase_unit_price = resp.issued_purchase_unit_price;
                        rowObj.issued_sale_unit_price = resp.issued_sale_unit_price;
                        rowObj.received_purchase_unit_price = resp.received_purchase_unit_price;
                        rowObj.received_sale_unit_price = resp.received_sale_unit_price;
                        rowObj._loading = false;
                        renderTable();
                        recalcTotals();
                    } else {
                        toastr.error('Could not fetch prices');
                        f18Rows.splice(f18Rows.indexOf(rowObj), 1);
                        renderTable();
                    }
                });
            });

            reloadF18Products();
            recalcTotals();

            // ─── Save / Save & Print ────────────────────────────────────────────────
            function submitF18Form(printAfterSave) {
                if (f18IsSubmitting) {
                    return;
                }

                var form_date = ($('#f18_date').val() || '').trim();
                var from_location_id = $('#f18_from_location_id').val();
                var to_location_id = $('#f18_to_location_id').val();

                var missing = [];
                if (!form_date) {
                    missing.push(@json(__('mpcs::lang.f18_validation_date')));
                }
                if (!from_location_id) {
                    missing.push(@json(__('mpcs::lang.f18_validation_from')));
                }
                if (!to_location_id) {
                    missing.push(@json(__('mpcs::lang.f18_validation_transferred_to')));
                }
                if (missing.length) {
                    toastr.warning(
                        @json(__('mpcs::lang.f18_validation_prefix')) + ' ' + missing.join(', ')
                    );
                    return;
                }

                if (!f18Rows.length) {
                    toastr.warning('Please add at least one row');
                    return;
                }

                var payloadRows = $.map(f18Rows, function(row) {
                    return {
                        product_id: row.product_id,
                        qty: row.qty,
                        issued_purchase_unit_price: row.issued_purchase_unit_price,
                        issued_sale_unit_price: row.issued_sale_unit_price,
                        received_purchase_unit_price: row.received_purchase_unit_price,
                        received_sale_unit_price: row.received_sale_unit_price
                    };
                });

                f18IsSubmitting = true;
                $('#f18_save_btn, #f18_save_print_btn').prop('disabled', true);

                $.ajax({
                    method: 'POST',
                    url: "{{ route('F18.store') }}",
                    data: {
                        _token: "{{ csrf_token() }}",
                        form_date: form_date,
                        from_location_id: from_location_id,
                        to_location_id: to_location_id,
                        rows: payloadRows
                    },
                    success: function(result) {
                        if (result && result.success) {
                            toastr.success(result.msg || 'Saved successfully');

                            if (result.current_form_no) {
                                $('#f18_form_no').val(result.current_form_no);
                            }
                            if (result.next_form_no) {
                                $('#f18_form_no').val(result.next_form_no);
                            }

                            if (printAfterSave && result.id) {
                                window.open('/mpcs/F18/' + result.id + '/print', '_blank');
                            }

                            f18Rows = [];
                            renderTable();
                            recalcTotals();
                            updateFormNo();
                        } else {
                            toastr.error(result && result.msg ? result.msg : 'Save failed');
                        }
                    },
                    error: function(xhr) {
                        var msg = 'Error saving F18 form';
                        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        toastr.error(msg);
                    },
                    complete: function() {
                        f18IsSubmitting = false;
                        $('#f18_save_btn, #f18_save_print_btn').prop('disabled', false);
                    }
                });
            }

            $('#f18_save_btn').on('click', function() {
                submitF18Form(false);
            });

            $('#f18_save_print_btn').on('click', function() {
                submitF18Form(true);
            });
        });
    </script>
@endsection
