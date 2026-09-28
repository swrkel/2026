{{--
    IS2102: Payment Options for Manage New.

    REWRITTEN to follow the OLD Manage page (manage_sections/section_07.blade.php)
    rather than the layout I built earlier. That earlier version introduced a
    combobox, repeated headings and an apply-to-all button - none of which exist
    on the Manage page, and which left users unable to find the controls they
    already knew. Familiar beats clever here.

    What follows the Manage page exactly:
      - one Bootstrap accordion per business location
      - table-condensed table-striped, same columns, same order
      - Form::select for Enable, checkboxes for the operations
      - identical field names, so the existing save handler is untouched:
            default_payment_accounts[<location>][name][<i>]
                                    [is_enabled][<i>]
                                    [is_purchase_enabled][<i>]  ... and so on

    What is DIFFERENT, and deliberately so:
      - a visible location selector with an "All" option, because Manage New is
        per business and a business can have many locations
      - it is its own section rather than buried under Other Permissions
--}}

@php
    $__pmpLocations = $business_locations ?? collect();
    $__pmpSelected  = request('pmp_location', 'all');

    // IS2318: never render technical/form keys as payment methods.
    $__pmpNonPaymentKeys = ['location_id', 'business_id', 'id', '_token', '_method'];
@endphp

@if($__pmpLocations->count() > 0)

<div class="card text-left" style="border:1px solid #D9D8D8;margin-bottom:30px;">
    <div class="card-header text-center">
        <h4>@lang('lang_v1.payment_methods')</h4>
        <hr>
    </div>

    <div class="card-body">

        {{-- Location selector. Visible rather than hidden, and defaulting to All
             so the screen opens showing everything, as the Manage page does. --}}
        <div class="row" style="margin-bottom:18px;">
            <div class="col-sm-6">
                <label class="search_label" for="pmp_location_filter">
                    @lang('business.business_location')
                </label>
                <select id="pmp_location_filter" class="form-control input-sm">
                    <option value="all" @selected($__pmpSelected === 'all')>
                        @lang('lang_v1.all') @lang('business.business_locations')
                    </option>
                    @foreach($__pmpLocations as $bl)
                        <option value="{{ $bl->id }}" @selected((string) $__pmpSelected === (string) $bl->id)>
                            {{ $bl->name }}
                        </option>
                    @endforeach
                </select>
                <p class="help-block" style="margin-top:6px;">
                    Choose a location to work on one at a time, or leave it on All.
                </p>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">

            @foreach($__pmpLocations as $business_location)
                @php
                    $default_payment_accounts = json_decode($business_location->default_payment_accounts);
                    $default_payment_accounts = is_object($default_payment_accounts) ? $default_payment_accounts : (object) [];
                @endphp

                <div class="accordion md-accordion pmp-location-block"
                     data-location="{{ $business_location->id }}"
                     id="pmpAccordion{{ $business_location->id }}" role="tablist" aria-multiselectable="true">
                    <div class="card">
                        <div class="card-header" role="tab" id="pmpHeading{{ $business_location->id }}">
                            <a class="collapsed" data-toggle="collapse"
                               data-parent="#pmpAccordion{{ $business_location->id }}"
                               href="#pmpCollapse{{ $business_location->id }}" aria-expanded="false"
                               aria-controls="pmpCollapse{{ $business_location->id }}">
                                <h5 class="mb-0 text-black">
                                    <label class="search_label">{{ $business_location->name }}</label>
                                    <i class="fa fa-angle-down rotate-icon pull-right"></i>
                                </h5>
                            </a>
                        </div>

                        <div id="pmpCollapse{{ $business_location->id }}" class="collapse" role="tabpanel"
                             aria-labelledby="pmpHeading{{ $business_location->id }}"
                             data-parent="#pmpAccordion{{ $business_location->id }}">
                            <div class="card-body" style="margin-bottom:10px;">
                                <hr>

                                <div class="table-responsive">
                                <table class="table table-condensed table-striped">
                                    <thead>
                                    <tr>
                                        <th class="text-center">@lang('lang_v1.payment_method')</th>
                                        <th class="text-center">@lang('lang_v1.enable')</th>
                                        <th class="text-center">@lang('superadmin::lang.purchases')</th>
                                        <th class="text-center">@lang('superadmin::lang.sales')</th>
                                        <th class="text-center">@lang('superadmin::lang.expenses')</th>
                                        <th class="text-center">@lang('superadmin::lang.purchase_return')</th>
                                        <th class="text-center">@lang('superadmin::lang.sales_return')</th>
                                        <th class="text-center">@lang('lang_v1.default_account_groups')</th>
                                        <th class="text-center">@lang('messages.action')</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php $i = 0; @endphp
                                    @foreach($default_payment_accounts as $key => $value)
                                        @php
                                            $__pmpTechnicalKey = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', (string) $key), '_'));
                                        @endphp
                                        @continue(in_array($__pmpTechnicalKey, $__pmpNonPaymentKeys, true))
                                        @php
                                            $n = 'default_payment_accounts[' . $business_location->id . ']';
                                            $v = is_object($value) ? $value : (object) [];
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>

                                            <td class="text-center">
                                                <input type="hidden" data-method-key="{{ $key }}" name="{{ $n }}[name][{{ $i }}]" value="{{ $key }}">
                                                <input type="hidden" name="{{ $n }}[is_custom][{{ $i }}]" value="{{ (int) ($v->is_custom ?? 0) }}">
                                                <select name="{{ $n }}[is_enabled][{{ $i }}]" class="form-control input-sm" required>
                                                    <option value="0" @selected((int) ($v->is_enabled ?? 0) === 0)>Not Active</option>
                                                    <option value="1" @selected((int) ($v->is_enabled ?? 0) === 1)>Active</option>
                                                </select>
                                            </td>

                                            {{-- IS2318: each related page must obey its own explicit flag.
                                                 Missing legacy/custom flags are OFF, never implicitly ON.
                                                 System methods are completed by PaymentMethodDefaultsService. --}}
                                            @foreach([
                                                'is_purchase_enabled',
                                                'is_sale_enabled',
                                                'is_expense_enabled',
                                                'is_purchase_return_enabled',
                                                'is_sale_return_enabled',
                                            ] as $flag)
                                                <td class="text-center">
                                                    {{-- The hidden field guarantees a 0 when the box is
                                                         unticked. Without it an unticked box posts nothing
                                                         and the save keeps the previous value. --}}
                                                    <input type="hidden" name="{{ $n }}[{{ $flag }}][{{ $i }}]" value="0">
                                                    <input type="checkbox" name="{{ $n }}[{{ $flag }}][{{ $i }}]"
                                                        value="1" @checked((int) ($v->$flag ?? 0) === 1)>
                                                </td>
                                            @endforeach

                                            <td class="text-center">
                                                <select name="{{ $n }}[account][{{ $i }}]" class="form-control input-sm pmp-account-select" style="width:100%">
                                                    <option value="">@lang('superadmin::lang.please_select')</option>
                                                    @foreach(($account_groups ?? []) as $gid => $gname)
                                                        <option value="{{ $gid }}"
                                                            @selected((string) ($v->account ?? '') === (string) $gid)>{{ $gname }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                @if((int) ($v->is_custom ?? 0) === 1)
                                                    <button type="button" class="btn btn-danger btn-xs pmp-remove-method"
                                                        title="@lang('messages.delete')">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                        @php $i++; @endphp
                                    @endforeach

                                    {{-- Add a payment method.

                                         A method is a KEY in this location's
                                         default_payment_accounts. There is no
                                         methods table - Util::payment_types()
                                         reads the keys and makes the label from
                                         the key itself. So this adds a row and
                                         the existing save stores it. --}}
                                    <tr class="pmp-add-row">
                                        <td colspan="9">
                                            <button type="button" class="btn btn-success btn-sm pmp-add-method"
                                                data-location="{{ $business_location->id }}"
                                                data-name="default_payment_accounts[{{ $business_location->id }}]">
                                                <i class="fa fa-plus"></i> @lang('messages.add')
                                            </button>
                                            <span class="text-muted" style="margin-left:10px;font-size:12px">
                                                @lang('superadmin::lang.add_payment_method_help')
                                            </span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            </div>
        </div>
    </div>
</div>


{{-- Add a payment method.

     One modal for the whole page rather than one per location - the button
     that opened it is remembered, so the row lands in the right table. --}}
<div class="modal fade" id="pmp_add_method_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title">@lang('superadmin::lang.add_payment_method')</h4>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="pmp_method_name">
                        @lang('superadmin::lang.payment_method_name')
                    </label>
                    <input type="text" id="pmp_method_name" class="form-control"
                        maxlength="60" autocomplete="off"
                        placeholder="@lang('superadmin::lang.payment_method_placeholder')">

                    {{-- The key is shown as it is typed. A user naming
                         something "Mobile Wallet" should see it will be stored
                         as mobile_wallet before they commit to it. --}}
                    <span class="help-block" style="margin-bottom:0">
                        <span id="pmp_method_key" style="font-family:monospace;color:#3c8dbc"></span>
                    </span>

                    <div id="pmp_method_error" class="text-danger" style="display:none;margin-top:6px"></div>
                </div>

                <div class="text-muted" style="font-size:12px;border-top:1px solid #eee;padding-top:10px">
                    <i class="fa fa-info-circle"></i>
                    @lang('superadmin::lang.payment_method_starts_unticked')
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="pmp_method_confirm">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    @lang('messages.cancel')
                </button>
            </div>

        </div>
    </div>
</div>

@push('javascript')
<script type="text/javascript">
$(function () {
    /*
     | Location selector. Shows or hides whole location blocks; it does not
     | reload, so nothing typed elsewhere on the page is lost.
    */
    $('#pmp_location_filter').on('change', function () {
        var val = $(this).val();
        $('.pmp-location-block').each(function () {
            $(this).toggle(val === 'all' || $(this).data('location') == val);
        });

        // Opening the chosen location saves a click when working on one site.
        if (val !== 'all') {
            $('.pmp-location-block[data-location="' + val + '"] .collapse').collapse('show');
        }
    });
});
</script>
@endpush

@endif


@push('javascript')
<script>
$(function () {
    /*
     | Type-and-filter on the account group dropdowns.
     |
     | A business with forty account groups is a list nobody wants to scroll.
     |
     | dropdownParent matters: these selects sit inside a Bootstrap accordion,
     | and without it select2 renders its search box at the end of the document
     | where the accordion's own styling cannot reach it - the field appears but
     | typing into it does nothing visible.
    */
    function pmpInitAccountSelects(scope) {
        $(scope || document).find('.pmp-account-select').each(function () {
            var $s = $(this);
            if ($s.hasClass('select2-hidden-accessible')) { return; }

            $s.select2({
                width: '100%',
                dropdownParent: $s.closest('.panel-collapse').length
                    ? $s.closest('.panel-collapse')
                    : $(document.body),
                placeholder: '{{ __('messages.please_select') }}',
                allowClear: false
            });
        });
    }

    pmpInitAccountSelects();

    // Accordion panels are hidden until opened, and select2 sizes itself wrong
    // when initialised on something with no width. So panels are done again as
    // they open.
    $(document).on('shown.bs.collapse', '.panel-collapse', function () {
        pmpInitAccountSelects(this);
    });
});
</script>
@endpush


@push('javascript')
<script>
$(function () {

    /*
     | Names core checks by string. A second method called "cash" would behave
     | one way in the payment screens and another in the code that reads these,
     | so they are refused rather than allowed to collide.
    */
    var PMP_RESERVED = ['cash', 'credit_sale', 'own_cards', 'card', 'cheque',
                        'direct_bank_deposit', 'bank_transfer', 'pre_payments', 'bank',
                        'custom_pay_1', 'custom_pay_2', 'custom_pay_3', 'advance', 'other',
                        'location_id', 'business_id', 'id', '_token', '_method'];

    function pmpKeyFrom(name) {
        return String(name).toLowerCase().trim()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function pmpLabelFrom(key) {
        return key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
    }

    var pmpTarget = null;

    // The button opens the modal; the modal does the work.
    $(document).on('click', '.pmp-add-method', function () {
        pmpTarget = $(this);

        $('#pmp_method_name').val('');
        $('#pmp_method_key').text('');
        $('#pmp_method_error').hide().text('');
        $('#pmp_add_method_modal').modal('show');

        // Focus after the modal has finished animating, or it is lost.
        setTimeout(function () { $('#pmp_method_name').focus(); }, 300);
    });

    // The key is shown as it is typed, so it is clear what will be created
    // before anything is.
    $(document).on('input', '#pmp_method_name', function () {
        var key = pmpKeyFrom($(this).val());
        $('#pmp_method_key').text(key ? key : '');
        $('#pmp_method_error').hide();
    });

    $(document).on('keypress', '#pmp_method_name', function (e) {
        if (e.which === 13) { e.preventDefault(); $('#pmp_method_confirm').click(); }
    });

    $(document).on('click', '#pmp_method_confirm', function () {
        if (!pmpTarget) { return; }

        var $btn = pmpTarget;
        var $tbody = $btn.closest('tbody');
        var fieldName = $btn.data('name');
        var name = $('#pmp_method_name').val();
        var key = pmpKeyFrom(name);

        function pmpFail(msg) {
            $('#pmp_method_error').text(msg).show();
            $('#pmp_method_name').focus();
        }

        if (!key) {
            pmpFail('{{ __('superadmin::lang.payment_method_name_invalid') }}');
            return;
        }

        if (PMP_RESERVED.indexOf(key) !== -1) {
            pmpFail('{{ __('superadmin::lang.payment_method_reserved') }}');
            return;
        }

        // A duplicate would silently overwrite the existing method's account.
        var clash = false;
        $tbody.find('input[type="hidden"]').each(function () {
            var n = $(this).attr('name') || '';
            if (n.indexOf('[name][') !== -1 && $(this).val() === key) { clash = true; }
        });
        if (clash) {
            pmpFail('{{ __('superadmin::lang.payment_method_exists') }}');
            return;
        }

        $('#pmp_add_method_modal').modal('hide');

        // Never use row count as the next index. If a custom row was removed,
        // row count can reuse an existing index and PHP will overwrite one method
        // with another on submit. Always allocate max(existing index) + 1.
        var i = 0;
        // Read every submitted method-name input, including rows that were
        // already present when the page loaded. Earlier code only scanned rows
        // added during this browser session, so the first custom method reused
        // index 0 and could overwrite Cash (or another existing method) in PHP.
        $tbody.find('input[type="hidden"]').each(function () {
            var n = $(this).attr('name') || '';
            var m = n.match(/\[name\]\[(\d+)\]$/);
            if (m) {
                i = Math.max(i, parseInt(m[1], 10) + 1);
            }
        });

        var accountOptions = $tbody.find('.pmp-account-select').first().html() || '';

        var html = '<tr>'
            + '<td>' + $('<div>').text(pmpLabelFrom(key)).html()
            + '<input type="hidden" data-method-key="' + key + '" '
            + 'name="' + fieldName + '[name][' + i + ']" value="' + key + '">'
            + '<input type="hidden" name="' + fieldName + '[is_custom][' + i + ']" value="1"></td>'
            + '<td class="text-center"><select name="' + fieldName + '[is_enabled][' + i + ']" '
            + 'class="form-control input-sm" required>'
            + '<option value="1">Active</option>'
            + '<option value="0">Not Active</option></select></td>';

        // The five transaction types, unticked - a new method should not
        // silently become available everywhere the moment it is created.
        ['is_purchase_enabled', 'is_sale_enabled', 'is_expense_enabled',
         'is_purchase_return_enabled', 'is_sale_return_enabled'].forEach(function (flag) {
            /*
             | The hidden 0 before each box is not optional.
             |
             | An unticked checkbox posts nothing at all, and the save would
             | then keep whatever was there before - so a box the user
             | deliberately cleared would come back ticked. The existing rows do
             | the same thing for the same reason.
            */
            html += '<td class="text-center">'
                 + '<input type="hidden" name="' + fieldName + '[' + flag + '][' + i + ']" value="0">'
                 + '<input type="checkbox" name="' + fieldName + '[' + flag + '][' + i + ']" value="1">'
                 + '</td>';
        });

        html += '<td><select name="' + fieldName + '[account][' + i + ']" '
             + 'class="form-control input-sm pmp-account-select" style="width:100%">'
             + accountOptions + '</select></td>'
             + '<td class="text-center"><button type="button" class="btn btn-danger btn-xs pmp-remove-method">'
             + '<i class="fa fa-times"></i></button></td>'
             + '</tr>';

        var $row = $(html);
        $btn.closest('tr.pmp-add-row').before($row);

        // Do not inherit the first existing method's selected account when the
        // option markup is cloned. A new method must start with Please Select.
        $row.find('.pmp-account-select').val('');

        // The new account dropdown needs select2 like the others.
        $row.find('.pmp-account-select').select2({
            width: '100%',
            dropdownParent: $row.closest('.panel-collapse').length
                ? $row.closest('.panel-collapse')
                : $(document.body)
        });
    });

    /*
     | IS2318: custom methods may be removed from Payment Options. The save path
     | now replaces the location list exactly, so a removed row is deleted from
     | default_payment_accounts and can no longer leak into Purchase/Expense
     | payment method dropdowns. System methods have no remove button.
    */
    $(document).on('click', '.pmp-remove-method', function () {
        $(this).closest('tr').remove();
    });

});
</script>
@endpush
