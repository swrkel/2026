{{--
    Add / edit a daily credit sale.

    Header above - customer, order, vehicle - and product lines below. Several
    products on one sale, which is why the lines are their own rows rather than
    columns on the header.

    A new vehicle can be added without leaving the form, as 8047 asks.
--}}

<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => $row ? route('sw.daily-credit-sales.update', $row->id) : route('sw.daily-credit-sales.store'),
            'method' => $row ? 'put' : 'post',
            'id' => 'sw_credit_sale_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">
                {{ $row ? __('sw::lang.edit_credit_sale') : __('sw::lang.add_credit_sale') }}
            </h4>
        </div>

        <div class="modal-body">

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('pump_operator_id', __('sw::lang.pump_operator') . ':*') !!}
                        {!! Form::select('pump_operator_id', $operators, $row->pump_operator_id ?? null, [
                            'class' => 'form-control select2', 'id' => 'sw_csf_operator', 'required',
                            'placeholder' => __('messages.please_select'), 'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('sw_shift_id', __('sw::lang.shift_no') . ':*') !!}
                        <select name="sw_shift_id" id="sw_csf_shift" class="form-control" required>
                            @if ($row)
                                <option value="{{ $row->sw_shift_id }}" selected>{{ $row->sw_shift_no }}</option>
                            @else
                                <option value="">{{ __('sw::lang.choose_an_operator_first') }}</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('sale_date', __('sw::lang.date') . ':*') !!}
                        {!! Form::date('sale_date',
                            $row && $row->sale_date ? \Carbon\Carbon::parse($row->sale_date)->format('Y-m-d') : date('Y-m-d'),
                            ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-5">
                    <div class="form-group">
                        {!! Form::label('contact_id', __('sw::lang.customer') . ':*') !!}
                        <select name="contact_id" id="sw_csf_customer" class="form-control select2"
                            required style="width:100%">
                            <option value="">{{ __('messages.please_select') }}</option>
                            @if ($row)
                                <option value="{{ $row->contact_id }}" selected>{{ $row->customer_name }}</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('order_no', __('sw::lang.order_no') . ':') !!}
                        {!! Form::text('order_no', $row->order_no ?? null, ['class' => 'form-control', 'maxlength' => 191]) !!}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('order_date', __('sw::lang.order_date') . ':') !!}
                        {!! Form::date('order_date',
                            $row && $row->order_date ? \Carbon\Carbon::parse($row->order_date)->format('Y-m-d') : null,
                            ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>

            {{-- The customer's position, captured when the sale is saved.
                 Shown here so the operator can see it before allowing credit. --}}
            <div class="row" id="sw_csf_customer_info" style="display:none">
                <div class="col-md-12">
                    <div class="alert alert-info" style="font-size:13px;padding:8px 12px">
                        <strong>@lang('sw::lang.outstanding'):</strong>
                        <span id="sw_csf_outstanding">0.00</span>
                        &nbsp;&nbsp;
                        <strong>@lang('sw::lang.limit'):</strong>
                        <span id="sw_csf_limit">—</span>
                        <span id="sw_csf_over_limit" class="text-danger" style="display:none">
                            &nbsp;<i class="fa fa-exclamation-triangle"></i>
                            @lang('sw::lang.over_credit_limit')
                        </span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        {!! Form::label('customer_reference_id', __('sw::lang.vehicle_no') . ':') !!}
                        <select name="customer_reference_id" id="sw_csf_vehicle" class="form-control"
                            style="width:100%">
                            <option value="">{{ __('sw::lang.choose_a_customer_first') }}</option>
                        </select>
                        {!! Form::hidden('vehicle_no', $row->vehicle_no ?? null, ['id' => 'sw_csf_vehicle_text']) !!}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-default btn-block" id="sw_csf_add_vehicle">
                            <i class="fa fa-plus"></i> @lang('sw::lang.add_vehicle')
                        </button>
                    </div>
                </div>
            </div>

            <hr>

            <h5><strong>@lang('sw::lang.products')</strong></h5>

            <div class="table-responsive">
                <table class="table table-condensed" id="sw_csf_lines">
                    <thead>
                        <tr>
                            <th style="width:29.6%">@lang('sw::lang.product')</th>
                            {{-- IS2245: QTY was 12%; increase it exactly 20% -> 14.4%. --}}
                            <th class="sw-csf-qty-col" style="width:14.4%">@lang('sw::lang.qty')</th>
                            <th style="width:15%">@lang('sw::lang.unit_price')</th>
                            <th style="width:15%">@lang('sw::lang.unit_discount')</th>
                            <th style="width:13%" class="text-right sw-csf-two-line">
                                <span>Amount Before</span><br><span>@lang('sw::lang.discount')</span>
                            </th>
                            <th style="width:13%" class="text-right sw-csf-two-line">
                                <span>@lang('sw::lang.credit_sales')</span><br><span>@lang('sw::lang.amount')</span>
                            </th>
                            <th style="width:1%"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-right">@lang('sale.total'):</th>
                            <th class="text-right"><span id="sw_csf_total_before">0.00</span></th>
                            <th class="text-right"><span id="sw_csf_total_after">0.00</span></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="button" class="btn btn-success btn-sm" id="sw_csf_add_line">
                <i class="fa fa-plus"></i> @lang('sw::lang.add_product')
            </button>

            <div class="form-group" style="margin-top:15px">
                {!! Form::label('note', __('sw::lang.note') . ':') !!}
                {!! Form::textarea('note', $row->note ?? null, ['class' => 'form-control', 'rows' => 2]) !!}
            </div>

        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div>
</div>

<style>
/* IS2245: keep the two requested long headings on exactly two readable lines. */
#sw_csf_lines th.sw-csf-two-line { line-height:1.15; white-space:nowrap; vertical-align:middle; }
#sw_csf_lines th.sw-csf-qty-col { min-width:84px; }

/* IS2204: keep the dynamic Product dropdown anchored to the Add Credit Sale
   modal and give a proper scrollable list instead of a detached/clipped popup. */
#sw_credit_sale_form .select2-container { width: 100% !important; }
.sw_credit_sale_modal .select2-dropdown { z-index: 1065; }
.sw_credit_sale_modal .select2-results__options {
    max-height: 260px !important;
    overflow-y: auto !important;
}
.sw_credit_sale_modal .select2-results__option {
    white-space: normal;
    line-height: 1.25;
    padding: 6px 8px;
}
</style>

<script>
$(function () {

    var swCsfProducts = {!! json_encode($products) !!};
    var swCsfLineIndex = 0;

    /*
     | S-697: type and search, and the price fills itself.
     |
     | Bound with delegation because the lines are built as the user adds them -
     | a handler attached at load would not reach a row created afterwards.
    */
    $(document).on('change', '.sw-csf-product', function () {
        var price = $(this).find('option:selected').data('price');
        var $row = $(this).closest('tr');

        if (price !== undefined && price !== '') {
            $row.find('.sw-csf-price').val(parseFloat(price).toFixed(2)).trigger('input');
        }
    });

    function swCsfInitProductSearch($scope) {
        var $sel = ($scope || $(document)).find('.sw-csf-product:not(.sw-csf-product-init)');
        if (!$sel.length || !$.fn.select2) { return; }

        $sel.each(function () {
            var $product = $(this);
            var $parent = $product.closest('.modal-content');
            if (!$parent.length) { $parent = $product.closest('.modal'); }
            if (!$parent.length) { $parent = $(document.body); }

            // The SW tab-wide Select2 helper also watches dynamically-added
            // selects.  Product rows have their own initializer because they
            // need a modal-local dropdown; double-initialising Select2 is what
            // made the product list jump above / away from the field.
            if ($product.hasClass('select2-hidden-accessible')) {
                $product.select2('destroy');
            }

            $product.addClass('sw-csf-product-init').select2({
                dropdownParent: $parent,
                width: '100%',
                minimumResultsForSearch: 0,
                dropdownAutoWidth: false,
                placeholder: '{{ __('messages.please_select') }}'
            });
        });
    }

    $(document).on('sw:line-added', function (e, $row) { swCsfInitProductSearch($row); });

    function swCsfProductOptions(selected) {
        var html = '<option value="">{{ __('messages.please_select') }}</option>';
        // S-697: each option carries its price, so choosing a product can
        // fill the unit price without another request.
        $.each(swCsfProducts, function (id, p) {
            var name = (p && p.name !== undefined) ? p.name : p;
            var price = (p && p.price !== undefined) ? p.price : '';
            html += '<option value="' + id + '" data-price="' + price + '"' +
                    (String(selected) === String(id) ? ' selected' : '') +
                    '>' + $('<div>').text(name).html() + '</option>';
        });
        return html;
    }

    function swCsfAddLine(line) {
        var i = swCsfLineIndex++;
        var row = $(
            '<tr>' +
            '<td><select name="lines[' + i + '][product_id]" class="form-control input-sm sw-csf-product sw-s2" required>' +
                swCsfProductOptions(line ? line.product_id : '') + '</select></td>' +
            '<td><input type="number" step="0.0001" min="0.0001" name="lines[' + i + '][quantity]" ' +
                'class="form-control input-sm sw-csf-qty" required value="' + (line ? line.quantity : '') + '"></td>' +
            '<td><input type="number" step="0.01" min="0" name="lines[' + i + '][unit_price]" ' +
                'class="form-control input-sm sw-csf-price" required value="' + (line ? line.unit_price : '') + '"></td>' +
            '<td><input type="number" step="0.01" min="0" name="lines[' + i + '][unit_discount]" ' +
                'class="form-control input-sm sw-csf-disc" value="' + (line ? line.unit_discount : '0') + '"></td>' +
            '<td class="text-right sw-csf-before">0.00</td>' +
            '<td class="text-right sw-csf-after">0.00</td>' +
            '<td><button type="button" class="btn btn-danger btn-xs sw-csf-remove">' +
                '<i class="fa fa-times"></i></button></td>' +
            '</tr>'
        );
        var $row = $(row);
        $('#sw_csf_lines tbody').append($row);

        // So search attaches to the row just created, rather than rescanning
        // every row each time one is added.
        $(document).trigger('sw:line-added', [$row]);
        swCsfRecalc();
    }

    /*
     | 8047: after discount = [qty * unit price] - [qty * unit discount].
     |
     | The discount is per UNIT. Clamped to the unit price, because a discount
     | larger than the price is a typo rather than an intention, and a negative
     | line would quietly reduce the sale total.
    */
    function swCsfRecalc() {
        var totalBefore = 0, totalAfter = 0;

        $('#sw_csf_lines tbody tr').each(function () {
            var $r = $(this);
            var qty = parseFloat($r.find('.sw-csf-qty').val()) || 0;
            var price = parseFloat($r.find('.sw-csf-price').val()) || 0;
            var disc = parseFloat($r.find('.sw-csf-disc').val()) || 0;

            if (disc > price) { disc = price; }

            var before = qty * price;
            var after = before - (qty * disc);

            $r.find('.sw-csf-before').text(before.toFixed(2));
            $r.find('.sw-csf-after').text(after.toFixed(2));

            totalBefore += before;
            totalAfter += after;
        });

        $('#sw_csf_total_before').text(totalBefore.toFixed(2));
        $('#sw_csf_total_after').text(totalAfter.toFixed(2));
    }

    $('#sw_csf_add_line').on('click', function () { swCsfAddLine(null); });

    $('#sw_csf_lines').on('click', '.sw-csf-remove', function () {
        $(this).closest('tr').remove();
        swCsfRecalc();
    });

    $('#sw_csf_lines').on('input change', 'input, select', swCsfRecalc);

    // Operator first, then their open shifts.
    $('#sw_csf_operator').on('change', function () {
        var $shift = $('#sw_csf_shift');
        $shift.prop('disabled', true).empty().append('<option value="">{{ __('sw::lang.loading') }}</option>');

        if (!$(this).val()) {
            $shift.prop('disabled', false).empty()
                .append('<option value="">{{ __('sw::lang.choose_an_operator_first') }}</option>');
            return;
        }

        $.get('{{ route('sw.operators.shifts') }}', {
            pump_operator_id: $(this).val(),
            location_id: $('#sw_cs_location_id').val()
        }, function (rows) {
            $shift.empty();
            if (!rows.length) {
                $shift.append('<option value="">{{ __('sw::lang.no_open_shift') }}</option>');
            } else {
                $shift.append('<option value="">{{ __('messages.please_select') }}</option>');
                $.each(rows, function (i, r) { $shift.append($('<option>').val(r.id).text(r.label)); });
            }
            $shift.prop('disabled', false);
        });
    });

    // Customer search, and their position when one is chosen.
    $('#sw_csf_customer').select2({
        ajax: {
            url: '{{ route('sw.customers.search') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data }; }
        },
        minimumInputLength: 1,
        placeholder: '{{ __('sw::lang.type_to_search_customers') }}'
    });

    $('#sw_csf_customer').on('change', function () {
        var id = $(this).val();
        var $veh = $('#sw_csf_vehicle');

        if (!id) {
            $('#sw_csf_customer_info').hide();
            $veh.empty().append('<option value="">{{ __('sw::lang.choose_a_customer_first') }}</option>');
            return;
        }

        $.get('{{ url('sw/customers') }}/' + id + '/info', function (res) {
            if (!res.found) { return; }

            $('#sw_csf_outstanding').text(Number(res.outstanding).toFixed(2));
            $('#sw_csf_limit').text(
                res.credit_limit > 0 ? Number(res.credit_limit).toFixed(2) : '{{ __('sw::lang.no_limit') }}'
            );
            $('#sw_csf_over_limit').toggle(
                res.credit_limit > 0 && Number(res.outstanding) >= Number(res.credit_limit)
            );
            $('#sw_csf_customer_info').show();

            $veh.empty().append('<option value="">{{ __('messages.please_select') }}</option>');
            $.each(res.references || [], function (i, r) {
                $veh.append($('<option>').val(r.id).text(r.reference));
            });
        });
    });

    // Keep the vehicle TEXT alongside the id: the id links to Customer
    // References, the text survives that reference being renamed or removed.
    $('#sw_csf_vehicle').on('change', function () {
        $('#sw_csf_vehicle_text').val($('#sw_csf_vehicle option:selected').text());
    });

    // Add a vehicle without leaving the form, as 8047 asks.
    $('#sw_csf_add_vehicle').on('click', function () {
        var contactId = $('#sw_csf_customer').val();
        if (!contactId) {
            alert('{{ __('sw::lang.choose_a_customer_first') }}');
            return;
        }

        var reference = prompt('{{ __('sw::lang.enter_vehicle_no') }}');
        if (!reference) { return; }

        $.post('{{ route('sw.customer-references.store') }}', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            contact_id: contactId,
            reference: reference
        }, function (res) {
            var $veh = $('#sw_csf_vehicle');
            if (!$veh.find('option[value="' + res.id + '"]').length) {
                $veh.append($('<option>').val(res.id).text(res.reference));
            }
            $veh.val(res.id).trigger('change');
        }).fail(function () {
            alert('{{ __('sw::lang.could_not_add_vehicle') }}');
        });
    });

    // Existing lines when editing, or one empty line to start.
    @if ($lines->count())
        @foreach ($lines as $line)
            swCsfAddLine({
                product_id: {{ $line->product_id }},
                quantity: {{ $line->quantity }},
                unit_price: {{ $line->unit_price }},
                unit_discount: {{ $line->unit_discount }}
            });
        @endforeach
        $('#sw_csf_customer').trigger('change');
    @else
        swCsfAddLine(null);
    @endif

});
</script>
