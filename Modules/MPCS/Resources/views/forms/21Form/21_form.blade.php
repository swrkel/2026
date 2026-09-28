<!-- Main content -->
<section class="content">
    @php
        $formNumber = $form_number ?? null;
        if (is_null($formNumber)) {
            $formNumber = Modules\MPCS\Entities\MpcsFormSetting::where(
                'business_id',
                request()->session()->get('business.id'),
            )->value('F21_form_sn');
        }
    @endphp

    {!! Form::open(['id' => 'f21_form']) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('form_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text(
                            'form_21_date_range',
                            \Carbon\Carbon::today()->format('Y-m-d'),
                            [
                                'placeholder' => __('lang_v1.select_a_date_range'),
                                'class' => 'form-control',
                                'id' => 'form_21_date_range',
                                'readonly',
                            ],
                        ) !!}
                    </div>
                </div>

                <div class="col-md-3" id="location_filter">
                    <div class="form-group">
                        @php
                            $default_location =
                                $business_locations->count() == 1 ? $business_locations->keys()->first() : null;
                        @endphp

                        {!! Form::label('form_21_location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('form_21_location_id', $business_locations, $default_location, [
                            'class' => 'form-control select2',
                            'id' => 'form_21_location_id',
                            'style' => 'width:100%',
                            'placeholder' => count($business_locations) > 1 ? __('lang_v1.all') : null,
                        ]) !!}

                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('form_21_category_id', 'Product Category:') !!}
                        {!! Form::select('form_21_category_id', $categories, null, [
                            'class' => 'form-control select2',
                            'id' => 'form_21_category_id',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('form_21_sub_category_id', 'Product Sub category:') !!}
                        {!! Form::select('form_21_sub_category_id', $sub_categories, null, [
                            'class' => 'form-control select2',
                            'id' => 'form_21_sub_category_id',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3" id="location_filter">
                    <div class="form-group">
                        {!! Form::label('form_21_product_id', __('mpcs::lang.product') . ':') !!}
                        {!! Form::select('form_21_product_id', $products, null, [
                            'class' => 'form-control select2',
                            'id' => 'form_21_product_id',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3" id="location_filter">
                    <div class="form-group">
                        {!! Form::label('f21_transaction_type', __('mpcs::lang.transaction_type') . ':') !!}
                        {!! Form::select('f21_transaction_type', $transactionTypes, null, [
                            'class' => 'form-control select2',
                            'id' => 'f21_transaction_type',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="col-md-12">
                    {{-- IS2349 #1: remove the redundant header row above the F21 details.
                         Keep the existing print action by moving it into the form-information row. --}}
                    <div class="row f21-form-info-row">
                        <div class="col-md-3 text-red">
                            <h5 style="font-weight: bold;" class="text-center">@lang('mpcs::lang.filling_station'): _________________</h5>
                            <input type="hidden" name="manager_name" value="" />
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h5 style="font-weight: bold;">@lang('mpcs::lang.date') : <span id="f21_date_from">-</span></h5>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h5 style="font-weight: bold;" id="fn" class="text-red">Form Number: <span
                                        id="form_no">{{ $formNumber ?? '-' }} </span></h5>
                                <input type="hidden" id="formnumber" value="{{ $formNumber ?? '-' }}">
                            </div>
                        </div>
                        <div class="col-md-3 text-right no-print">
                            <button type="button" name="submit_type" id="f21_print" value="print"
                                class="btn btn-primary">@lang('mpcs::lang.print')</button>
                        </div>
                    </div><br>

                    <div class="row" style="margin-top: 20px;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="form_f21_list_table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>@lang('mpcs::lang.date')</th>
                                        <th>@lang('mpcs::lang.book_no')</th>
                                        <th>@lang('mpcs::lang.transaction_type')</th>
                                        <th>@lang('mpcs::lang.product_code')</th>
                                        <th style="min-width:180px;">@lang('mpcs::lang.product_name')</th>
                                        <th>@lang('mpcs::lang.starting_qty')</th>
                                        <th>@lang('mpcs::lang.received_qty')</th>
                                        <th>@lang('mpcs::lang.sold_qty')</th>
                                        <th>@lang('mpcs::lang.balance_qty')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray footer-total">
                                        <td colspan="6" class="text-right">@lang('sale.total'):</td>
                                        <td id="footer_total_received"></td>
                                        <td id="footer_total_sold"></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->
