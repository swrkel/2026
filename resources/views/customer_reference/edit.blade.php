<div class="modal-dialog" role="document">
    <div class="modal-content">
        @php
            $business = \App\Business::find(request()->session()->get('business.id'));
            $currencyDecimal = $business->currency_precision;
        @endphp

        {!! Form::open(['url' => action('CustomerReferenceController@update', $customer_reference->id), 'method' => 'put', 'id' =>
        'customer_reference_add_form' ]) !!}

        <div class="modal-header">
            <input type="hidden" id="currencyPrecision" value="{{$currencyDecimal}}">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'lang_v1.edit_customer_reference' )</h4>
        </div>

        <div class="modal-body">
            <div class="colr-md-4">
                <div class="form-group">
                    {!! Form::label('date', __( 'lang_v1.date' ) . ':*') !!}
                    {!! Form::text('date', null, ['class' => 'form-control reference_date', 'required',
                    'placeholder' => __(
                    'lang_v1.date' ) ]); !!}
                </div>
            </div>
            <div class="colr-md-4">
                <div class="form-group">
                    {!! Form::label('contact_id', __( 'lang_v1.customer' ) . ':*') !!}
                    {{-- Modified by Engr. Alex -- task 7882: Issue 1 - add plus button to Customer dropdown to quickly add new customer --}}
                    <div class="input-group">
                        {!! Form::select('contact_id', $contacts, $customer_reference->contact_id , ['class' => 'form-control select2 contact_reference',
                        'placeholder' => __( 'lang_v1.please_select' ), 'style' => 'width: 100%;']); !!}
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-default bg-white btn-flat btn-modal cr_add_new_customer"
                                data-href="{{ action('ContactController@create', ['quick_add' => 1, 'type' => 'customer']) }}"
                                data-container=".cr_contact_modal_edit"
                                data-target-field="contact_id" title="Add New Customer">
                                <i class="fa fa-plus-circle text-primary fa-lg"></i>
                            </button>
                        </span>
                    </div>
                </div>
            </div>
            {{-- Modified by Engr. Alex -- task 7882: Issue 1 - Sub Customer dropdown loads customer list with plus button --}}
            <div class="colr-md-4">
                <div class="form-group">
                    {!! Form::label('sub_customer_id', 'Sub Customer:') !!}
                    <div class="input-group">
                        {!! Form::select('sub_customer_id', $contacts, $customer_reference->sub_customer_id, ['class' => 'form-control select2 sub_customer_reference',
                        'placeholder' => __( 'lang_v1.please_select' ), 'style' => 'width: 100%;', 'id' => 'sub_customer_id']); !!}
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-default bg-white btn-flat btn-modal cr_add_new_customer"
                                data-href="{{ action('ContactController@create', ['quick_add' => 1, 'type' => 'customer']) }}"
                                data-container=".cr_contact_modal_edit"
                                data-target-field="sub_customer_id" title="Add New Customer">
                                <i class="fa fa-plus-circle text-primary fa-lg"></i>
                            </button>
                        </span>
                    </div>
                </div>
            </div>
            <div class="colr-md-4">
                <div class="form-group">
                    {!! Form::label('reference', __( 'lang_v1.reference' ) . ':*') !!}  @if(!empty($help_explanations['customer_reference'])) @show_tooltip($help_explanations['customer_reference']) @endif
                    {!! Form::text('reference', $customer_reference->reference, ['class' => 'form-control', 'placeholder' => __(
                    'lang_v1.reference' ) ]); !!}
                </div>
                <input type="hidden" name="barcode_src" value="{{$customer_reference->barcode_src}}" id="barcode_src">
            </div>
            <div class="colr-md-4">
                <div class="form-group">
                    {!! Form::label('openingBalance', __( 'lang_v1.openingBalance' ) . ':*') !!}
                    {!! Form::number('openingBalance', $customer_reference->opening_balance, ['class' => 'form-control', 'placeholder' => __(
                    'lang_v1.openingBalance'),'step'=>'0.01' ]); !!}
                </div>
            </div>
            <div class="col-md-12">
                {!! Form::label('barcode', __( 'lang_v1.barcode' ) ) !!}
                <div class="col-md-12 barcode-image">
                    <img style="max-width: 97%;" src="{{$customer_reference->barcode_src}}" alt="barcode">
                </div>
            </div>

            <div class="clearfix"></div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
                <button type="button" class="btn btn-default add_reference_btn" data-dismiss="modal">@lang(
                    'messages.close' )</button>
            </div>

            {!! Form::close() !!}

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

    {{-- Modified by Engr. Alex -- task 7882: Issue 1 - cr_contact_modal_edit is defined at page level in index.blade.php --}}

    <script>
        $('#openingBalance').change(function(){
            decimalValue = $('#currencyPrecision').val();
            this.value = parseFloat(this.value).toFixed(decimalValue);
        });

        $('.reference_date').datepicker('setDate', "{{\Carbon::parse($customer_reference->date)->format('m-d-Y')}}");

        $('.contact_reference').select2({
            width: '100%',
            placeholder: "Please select",
            allowClear: true
        });
        // Modified by Engr. Alex -- task 7882: Issue 1 - init Sub Customer select2
        $('.sub_customer_reference').select2({
            width: '100%',
            placeholder: "Please select",
            allowClear: true
        });

        // Modified by Engr. Alex -- task 7882: Issue 1 - track which field triggered the add customer modal
        var cr_edit_target_field = 'contact_id';
        $(document).on('click', '.cr_add_new_customer', function() {
            cr_edit_target_field = $(this).data('target-field');
        });

        // Modified by Engr. Alex -- task 7882: Issue 1 - after contact modal loaded, intercept form submit
        $(document).on('shown.bs.modal', '.cr_contact_modal_edit', function() {
            $(document).off('submit.cr_quick_add_edit').on('submit.cr_quick_add_edit', 'form#quick_add_contact', function(e) {
                e.preventDefault();
                var form = this;
                $.ajax({
                    method: 'POST',
                    url: $(form).attr('action'),
                    dataType: 'json',
                    data: $(form).serialize(),
                    success: function(result) {
                        if (result.success == true) {
                            var name = result.data.name;
                            if (result.data.supplier_business_name) {
                                name += ' ' + result.data.supplier_business_name;
                            }
                            $('select#contact_id').append($('<option>', { value: result.data.id, text: name }));
                            $('select#sub_customer_id').append($('<option>', { value: result.data.id, text: name }));
                            $('select#' + cr_edit_target_field).val(result.data.id).trigger('change');
                            $('.cr_contact_modal_edit').modal('hide');
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });
        });

        $('#reference').change(function(){
            $.ajax({
                method: 'get',
                url: '/get-customer-reference/barcode',
                data: {
                    customer_id : $('#contact_id').val(),
                    reference : $('#reference').val()
                 },
                success: function(result) {
                    if(result.success == 1){
                        $('.barcode-image').empty().append(result.html);
                        $('#barcode_src').val(result.src);
                    }

                },
            });
        })
    </script>
