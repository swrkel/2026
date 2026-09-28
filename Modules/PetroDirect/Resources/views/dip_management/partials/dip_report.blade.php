<!-- Main content -->

<section class="content">

    <div class="row">

        <div class="col-md-12">

            @component('components.filters', ['title' => __('report.filters')])

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}

                    {!! Form::select('report_location_id', $business_locations, null, ['class' => 'form-control

                    select2',

                    'placeholder' => __('petrodirect::lang.all'), 'id' => 'report_location_id', 'style' => 'width:100%']) !!}

                </div>

            </div>

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('tank_id', __('petrodirect::lang.tanks') . ':') !!}

                    {!! Form::select('report_tank_id', $tanks, null, ['class' => 'form-control select2', 'placeholder'

                    => __('petrodirect::lang.all'), 'id' => 'report_tank_id', 'style' => 'width:100%']) !!}

                </div>

            </div>

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('products', __('petrodirect::lang.products') . ':') !!}

                    {!! Form::select('report_product_id', $products, null, ['class' => 'form-control select2',

                    'placeholder'

                    => __('petrodirect::lang.all'), 'id' => 'report_product_id', 'style' => 'width:100%']) !!}

                </div>

            </div>

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('daily_report_date_range', __('report.date_range') . ':') !!}

                    {!! Form::text('report_date_range', @format_date('first day of this month') . ' ~ ' .

                    @format_date('last

                    day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>

                    'form-control', 'id' => 'report_date_range', 'readonly']) !!}

                </div>

            </div>

            @endcomponent

        </div>

    </div>



    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrodirect::lang.dip_report')])

    @slot('tool')

    <button type="button" id="addNewDipBtn" class="btn  btn-primary btn-modal pull-right"

    data-href="{{action('\Modules\PetroDirect\Http\Controllers\DipManagementController@addNewDip')}}"

    data-container=".dip_modal">

    <i class="fa fa-thermometer"></i> @lang('petrodirect::lang.add_dip')</button>

    

    @endslot

    <div class="col-md-12">

        <div class="row">

            <div class="col-md-5 text-red" style="margin-top: 14px;">

                <b>@lang('petrodirect::lang.date_range'): <span class="report_from_date"></span> @lang('petrodirect::lang.to') <span

                        class="report_to_date"></span> </b>

            </div>

            <div class="col-md-7">

                <div class="text-center pull-left">

                    <h5 style="font-weight: bold;">{{request()->session()->get('business.name')}} <br>

                        <span class="report_location_name">@lang('petrodirect::lang.all')</span></h5>

                </div>

            </div>

        </div>

        <div class="row" style="margin-top: 15px;">
            <!--<div class="col-md-3"><b>@lang('petrodirect::lang.tank'): <span class="report_tank"></span></b></div>-->
            <!--<div class="col-md-3"><b>@lang('petrodirect::lang.product'): <span class="report_product"></span></b></div>-->
            <!--<div class="col-md-2"><b>@lang('petrodirect::lang.total_loss'): <span class="report_total_loss"></span></b></div>-->
            <div class="col-md-2" style="margin-left: 21cm;color: green;font-weight: bold;font-size: x-large; white-space: nowrap;"><b>Excess:</b> <span class="report_total_excess"></span></div>
            <div class="col-md-2" style="margin-left: 21cm;color: red;font-weight: bold;font-size: x-large; white-space: nowrap;"><b>Shortage:</b> <span class="report_net_difference"></span></div>
        </div>


        <div class="row" style="margin-top: 20px;">

            <div class="table-responsive">

                <table class="table table-bordered table-striped" id="dip_report_table">

                    <thead>

                        <tr>
                            
                            <th>@lang('petrodirect::lang.action')</th>

                            <th>@lang('petrodirect::lang.add_dip_no')</th>
                            
                            <th>@lang('petrodirect::lang.date')</th>

                            <th>@lang('petrodirect::lang.daily_report_date')</th>

                            <th>@lang('petrodirect::lang.location')</th>

                            <th>@lang('petrodirect::lang.tank')</th>

                            <th>@lang('petrodirect::lang.product')</th>

                            <th>@lang('petrodirect::lang.dip_reading')</th>

                            <th>@lang('petrodirect::lang.qty_on_dip_reading')</th>

                            <th>@lang('petrodirect::lang.current_qty')</th>

                            <th>@lang('petrodirect::lang.differnece')</th>
                            
                            <th>@lang('petrodirect::lang.difference_value')</th>

                        </tr>

                    </thead>

                    
                    <tfoot>                        

                        <tr class="footer_total">

                            <td colspan="10" style="text-align: right; font-weight: bold;">@lang('petrodirect::lang.total')

                                :</td>

                            <td style="text-align: left; font-weight: bold;" class="difference_total display_currency"></td>

                            <td style="text-align: left; font-weight: bold;" class="difference_value_total display_currency final-total"></td>

                        </tr>                        

                    </tfoot>
                   

                </table>

            </div>

        </div>

    </div>

    @endcomponent



    <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel">

    </div>

</section>

<!-- /.content -->