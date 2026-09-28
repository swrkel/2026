<!-- Main content -->
<section class="content">
    {!! Form::open(['action' => '\Modules\MPCS\Http\Controllers\F22FormController@saveF22Form', 'method' =>
    'post', 'id' =>
    'f22_form']) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

            <div class="col-md-3" id="location_filter">
                <div class="form-group">
                    {!! Form::label('f22_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('f22_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3" id="location_filter">
                <div class="form-group">
                    {!! Form::label('f22_product_id', __('mpcs::lang.product') . ':') !!}
                    {!! Form::select('f22_product_id', $products, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>


            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('type', __('mpcs::lang.form_no') . ':') !!}
                    {!! Form::text('F22_from_no', $F22_from_no, ['class' => 'form-control', 'readonly']) !!}
                </div>
            </div>


            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            <div class="row">
                <div class="col-md-3">
                    {!! Form::label('manager_name', __('mpcs::lang.manager_name'), ['']) !!}
                    {!! Form::text('manager_name', null, ['class' => 'form-control']) !!}
                </div>
                <div class="col-md-3 pull-right">
                    {{-- Save & Print: disabled until approved --}}
                    <button type="submit" name="submit_type" id="f22_save_and_print" value="save_and_print"
                        class="btn btn-primary pull-right"
                        style="margin-left: 20px" disabled>@lang('mpcs::lang.save_and_print')</button>
                    <button type="submit" name="submit_type" id="f22_print" value="print"
                        class="btn btn-primary pull-right" disabled>@lang('mpcs::lang.print')</button>
                </div>
            </div>
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-4"></div>
                    <div class="col-md-5">
                        <div class="text-center">
                            <h5 style="font-weight: bold;">{{request()->session()->get('business.name')}} <br>
                                <span class="f22_location_name">@lang('petro::lang.all')</span></h5>
                                <input type="hidden" name="f22_location_name" id="f22_location_name" value="All">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center pull-left">
                            <h5 style="font-weight: bold;" class="text-red">@lang('mpcs::lang.stock_taking_form')
                                @lang('mpcs::lang.form_no') : {{$F22_from_no}}</h5>
                        </div>
                    </div>
                </div>
                {!! Form::close() !!}
                <div class="row" style="margin-top: 20px;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="form_22_table">
                            <thead>
                                <tr>
                                    <th>@lang('mpcs::lang.index_no')</th>
                                    <th>@lang('mpcs::lang.code')</th>
                                    <th>@lang('mpcs::lang.book_no')</th>
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
                            <tfoot>
                                <tr class="bg-gray">
                                    <td class="text-red text-bold" colspan="7">@lang('mpcs::lang.total_this_page')</td>
                                    <td class="text-red text-bold text-right" id="footer_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold text-right" id="footer_total_sale_price"></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr class="bg-gray">
                                    <td class="text-red text-bold" colspan="7">@lang('mpcs::lang.total_previous_page')
                                    </td>
                                    <td class="text-red text-bold text-right" id="pre_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold text-right" id="pre_total_sale_price"></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr class="bg-gray">
                                    <td class="text-red text-bold" colspan="7">@lang('mpcs::lang.grand_total')</td>
                                    <td class="text-red text-bold text-right" id="grand_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold text-right" id="grand_total_sale_price"></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td colspan="11"> @lang('mpcs::lang.confirm_f22')</td>
                                </tr>
                                <tr>
                                    <td colspan="7"><h5 style="font-weight: bold; margin-bottom: 0px; ">
                                        @lang('mpcs::lang.checked_by'): ____________</h5></td>
                                        <td colspan="4"><h5 style="font-weight: bold; margin-bottom: 0px; ">
                                            @lang('mpcs::lang.received_by'): ____________</h5> <br></td>
                                </tr>
                                <tr>
                                    <td colspan="7"> <h5 style="font-weight: bold; margin-bottom: 0px; ">
                                        @lang('mpcs::lang.signature_of_manager'): ____________</h5></td>
                                        <td colspan="4">
                                            <h5 style="font-weight: bold; margin-bottom: 0px; ">
                                                @lang('mpcs::lang.handed_over_by'): ____________</h5>
                                        </td>
                                </tr>
                                <tr>
                                    <td colspan="11"> 
                                        <h5 style="font-weight: bold; margin-top: 10px; ">@lang('mpcs::lang.user'): {{auth()->user()->username }}</h5>
                                        
                                        @if(isset($f22_latest_signature) && $f22_latest_signature && $f22_latest_signature->signature_path && file_exists(public_path('uploads/' . $f22_latest_signature->signature_path)))
                                            <div style="margin-top: 15px;">
                                                <img src="{{ asset('uploads/' . $f22_latest_signature->signature_path) }}" style="max-width: 150px; max-height: 80px;" alt="Signature">
                                                <p style="margin-top: 5px; font-size:12px;"><strong>{{ __('membership::lang.authorized_signature') }}</strong></p>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                {{-- ===== APPROVED BY ROW ===== --}}
                                <tr id="f22_approve_row">
                                    <td colspan="11" style="text-align: center; padding: 15px;">
                                        @if(auth()->user()->hasPermissionTo('f22_approve_stock_taking') || auth()->user()->can('approve_f22_stock_taking') || auth()->user()->is_superadmin_default == 1)
                                            {{-- Approve button: visible only for authorized users --}}
                                            <button type="button" id="f22_approve_btn"
                                                class="btn btn-info"
                                                style="min-width: 160px; font-weight: bold;">
                                                @lang('mpcs::lang.click_to_approve')
                                            </button>
                                        @endif
                                        <div id="f22_approved_by_display" style="margin-top: 8px; font-weight: bold; font-size: 14px; display: none;">
                                            @lang('mpcs::lang.approved_by'): <span id="f22_approved_by_name"></span>
                                        </div>
                                        {{-- Hidden field sent with the form data --}}
                                        <input type="hidden" name="approved_by" id="f22_approved_by_hidden" value="">
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <input type="hidden" name="purchase_price1" id="purchase_price1" value="">
                <input type="hidden" name="sales_price1" id="sales_price1" value="">
                <input type="hidden" name="purchase_price2" id="purchase_price2" value="">
                <input type="hidden" name="sales_price2" id="sales_price2" value="">
                <input type="hidden" name="purchase_price3" id="purchase_price3" value="">
                <input type="hidden" name="sales_price3" id="sales_price3" value="">
            </div>

            @endcomponent
        </div>
    </div>
    
</section>
<!-- /.content -->

<script>
$(document).ready(function() {
    // ======================================================
    // Approve Button Logic
    // ======================================================
    $('#f22_approve_btn').on('click', function() {
        var $btn = $(this);
        $btn.attr('disabled', true).text('Approving...');

        $.ajax({
            method: 'POST',
            url: '/mpcs/approve',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
            },
            success: function(result) {
                if (result.success) {
                    // Show the approved by name
                    $('#f22_approved_by_name').text(result.username);
                    $('#f22_approved_by_display').show();
                    // Store in hidden field for form submission
                    $('#f22_approved_by_hidden').val(result.username);
                    // Hide the approve button
                    $btn.hide();
                    // Enable save buttons
                    $('#f22_save_and_print, #f22_print').prop('disabled', false);
                    toastr.success('Approved by: ' + result.username);
                } else {
                    $btn.attr('disabled', false).text('Click to Approve');
                    toastr.error(result.message || 'Approval failed. You may not have permission.');
                }
            },
            error: function() {
                $btn.attr('disabled', false).text('Click to Approve');
                toastr.error('Server error. Please try again.');
            }
        });
    });
});
</script>