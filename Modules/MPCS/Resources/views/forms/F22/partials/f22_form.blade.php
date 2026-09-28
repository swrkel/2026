<!-- Main content -->
<style>
    .pump-section table {
        display: block;
        overflow-x: auto;
    }

    .pumps-container {
        display: flex;
        gap: 10px;
        align-items: stretch;
    }

    .no-wrap {
        white-space: nowrap;
    }

    .column-50 {
        width: 25%;
    }

    .pump-section {
        flex: 1;
        border-right: 3px solid skyblue;
        padding-right: 10px;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .form-input {
        width: 50%;
        padding: 1px;
        box-sizing: border-box;
    }
</style>

<section class="content" style="padding: 10px">
    {!! Form::open([
        'action' => '\Modules\MPCS\Http\Controllers\F22FormController@saveF22Form',
        'method' => 'post',
        'id' => 'f22_form',
    ]) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                {{-- First Row: Business Location, Product Subcategory, Product, Form No --}}
                <div class="row">
                    <div class="col-md-3" id="location_filter">
                        <div class="form-group">
                            {!! Form::label('f22_location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('f22_location_id', $business_locations, $default_location_id ?? null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3" id="sub_category_filter">
                        <div class="form-group">
                            {!! Form::label('f22_sub_category_id', __('mpcs::lang.product_sub_category') . ':') !!}
                            {!! Form::select('f22_sub_category_id', $sub_categories, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3" id="product_filter">
                        <div class="form-group">
                            {!! Form::label('f22_product_id', __('mpcs::lang.product') . ':') !!}
                            {!! Form::select('f22_product_id', $products, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('type', __('mpcs::lang.form_no') . ':') !!}
                            {!! Form::text('F22_from_no', $F22_from_no, ['class' => 'form-control', 'readonly', 'id' => 'F22_from_no']) !!}
                        </div>
                    </div>
                </div>

                {{-- Second Row: Manager Name, Date, Search, and Buttons --}}
                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('manager_name', __('mpcs::lang.manager_name'), ['']) !!}
                            {!! Form::text('manager_name', null, ['class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('f22_date', __('report.date') . ':') !!}
                            {{--
                                IS2126: back to a TEXT field.

                                IS2122 made this type="date" to stop the ambiguous
                                value being posted. That fixed the date, but this
                                field already has a daterangepicker bound to it in
                                F22_stock_taking.blade.php, so the browser's own
                                calendar opened alongside it - two pickers at once,
                                which is the reported fault.

                                The field is a text input again, leaving the
                                JavaScript picker as the only calendar. The
                                ambiguity is instead removed where the value is
                                SENT: the script now converts it to Y-m-d using the
                                very format the picker wrote it in, so no guessing
                                is involved at either end.
                            --}}
                            <div class="dropdown">
                                {!! Form::text('form_22_date', @format_date(date('Y-m-d')), [
                                    'class' => 'form-control input_number customer_transaction_date',
                                    'id' => 'f22_date',
                                    'required',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('search', __('lang_v1.search') . ':') !!}
                            {!! Form::text('search', null, [
                                'class' => 'form-control',
                                'id' => 'form_22_table_search_input',
                                'placeholder' => 'Search...',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group" style="margin-top: 25px;">
                            <button type="button" name="submit_type" id="f22_print" value="print"
                                class="btn btn-primary btn-sm">@lang('mpcs::lang.print')</button>
                            <button type="button" name="submit_type" id="f22_save_and_print" value="save_and_print"
                                class="btn btn-primary btn-sm"
                                style="margin-left: 20px; background-color: #ff5733; border: none;">@lang('mpcs::lang.save_and_print')</button>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <div class="col-md-4"></div>
                    <div class="col-md-5">
                        <div class="text-center">
                            <h5 style="font-weight: bold;">
                                {{ request()->session()->get('business.name') }} <br>
                                <span class="f22_location_name">{{ $default_location_name ?: __('petro::lang.all') }}</span>
                            </h5>
                            <input type="hidden" name="f22_location_name" id="f22_location_name" value="{{ $default_location_name ?: 'All' }}">
                            {{-- Hidden field to carry approved_by into form data --}}
                            <input type="hidden" name="approved_by" id="f22_approved_by_value" value="">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center pull-left">
                            <h5 style="font-weight: bold;" class="text-red">
                                @lang('mpcs::lang.f22_form_no') : <span id="form_no1">{{ $F22_from_no }}</span>
                            </h5>
                        </div>
                    </div>
                </div>
                {!! Form::close() !!}

                <div class="row" style="margin-top: 20px;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="form_22_table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>@lang('mpcs::lang.index_no')</th>
                                    <th>@lang('mpcs::lang.code')</th>
                                    <th>@lang('mpcs::lang.product')</th>
                                    <th>@lang('mpcs::lang.current_stock')</th>
                                    <th>@lang('mpcs::lang.stock_count')</th>
                                    <th>@lang('mpcs::lang.unit_purchase_price')</th>
                                    <th>@lang('mpcs::lang.total_purchase_price')</th>
                                    <th>@lang('mpcs::lang.unit_sale_price')</th>
                                    <th>@lang('mpcs::lang.total_sale_price')</th>
                                    <th>@lang('mpcs::lang.qty_difference')</th>
                                </tr>
                            </thead>
                            <!-- Removed tfoot completely -->
                        </table>
                        <div id="form_22_custom_pagination_label" class="text-right font-weight-bold"
                            style="margin-top: 10px;"></div>
                        <input type="hidden" id="F22_form_no_input" name="form_no" value="">
                    </div>

                    <!-- Custom summary footer -->
                    <div class="custom-footer-summary" style="margin-top: 30px;">
                        <table class="table table-bordered">
                            <tr class="bg-gray">
                                <td class="text-red text-bold" colspan="5">@lang('mpcs::lang.total_this_page')</td>
                                <td class="text-red text-bold text-right" id="footer_total_purchase_price"></td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td class="text-red text-bold text-right" id="footer_total_sale_price"></td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr class="bg-gray">
                                <td class="text-red text-bold" colspan="5">@lang('mpcs::lang.total_previous_page')</td>
                                <td class="text-red text-bold text-right" id="pre_total_purchase_price"></td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td class="text-red text-bold text-right" id="pre_total_sale_price"></td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr class="bg-gray">
                                <td class="text-red text-bold" colspan="5">@lang('mpcs::lang.grand_total')</td>
                                <td class="text-red text-bold text-right" id="grand_total_purchase_price"></td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td class="text-red text-bold text-right" id="grand_total_sale_price"></td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="10">
                                    <h3 style="color:red;">Pumps & Meters</h3>
                                    <div class="pumps-container"></div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="10">@lang('mpcs::lang.confirm_f22')</td>
                            </tr>
                            <tr>
                                <td colspan="6">
                                    <h5 style="font-weight: bold;">@lang('mpcs::lang.checked_by'): ____________</h5>
                                </td>
                                <td colspan="4">
                                    <h5 style="font-weight: bold;">@lang('mpcs::lang.received_by'): ____________</h5>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6">
                                    <h5 style="font-weight: bold;">@lang('mpcs::lang.signature_of_manager'): ____________</h5>
                                </td>
                                <td colspan="4">
                                    <h5 style="font-weight: bold;">@lang('mpcs::lang.handed_over_by'): ____________</h5>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="10">
                                    <h5 style="font-weight: bold;">@lang('mpcs::lang.user'): {{ auth()->user()->username }}</h5>
                                </td>
                            </tr>
                            @if ($stock_taking_approve_permission)
                                <tr id="approval_row">
                                    <td colspan="10" class="text-center">
                                        <button type="button" class="btn btn-info" id="btn_click_to_approve"
                                            style="min-width: 160px; font-weight: bold;">
                                            @lang('mpcs::lang.click_to_approve')
                                        </button>

                                        <div id="approved_user_display"
                                            style="display:none; font-weight:bold; margin-top: 8px; font-size: 14px;">
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </div>

                </div>

                <!-- Hidden fields -->
                <input type="hidden" name="purchase_price1" id="purchase_price1" value="">
                <input type="hidden" name="sales_price1" id="sales_price1" value="">
                <input type="hidden" name="purchase_price2" id="purchase_price2" value="">
                <input type="hidden" name="sales_price2" id="sales_price2" value="">
                <input type="hidden" name="purchase_price3" id="purchase_price3" value="">
                <input type="hidden" name="sales_price3" id="sales_price3" value="">
            @endcomponent
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {

        $('#btn_click_to_approve').on('click', function() {

            let form_no = $('#F22_from_no').val();
            let location_id = $('#f22_location_id').val();

            $.ajax({
                url: "{{ action('\Modules\MPCS\Http\Controllers\F22FormController@approveF22') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    form_no: form_no,
                    location_id: location_id
                },
                success: function(response) {

                    if (response.success) {

                        $('#btn_click_to_approve').hide();

                        // Populate hidden field for save
                        $('#f22_approved_by_value').val(response.username);

                        $('#approved_user_display')
                            .html('<span style="color:green;">@lang("mpcs::lang.approved_by"): <strong>' + response.username + '</strong></span>')
                            .show();

                        // Enable buttons
                        $('#f22_print').prop('disabled', false);
                        $('#f22_save_and_print').prop('disabled', false);

                        toastr.success('@lang("mpcs::lang.approved_by"): ' + response.username);

                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Something went wrong.');
                }
            });
        });

    });
</script>