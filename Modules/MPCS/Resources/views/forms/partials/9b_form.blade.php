<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

            <div class="col-md-3" id="location_filter">
                <div class="form-group">
                    {!! Form::label('f15a9ab_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('f15a9ab_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('form_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('form_15a9ab_date_range', @format_date('first day of this month') . ' ~ ' . @format_date('last
                    day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                    'form-control', 'id' => '9b_date_range', 'readonly']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('type', __('mpcs::lang.from_no') . ':') !!}
                    {!! Form::text('F15a9ab_from_no', $F15a9ab_from_no, ['class' => 'form-control', 'readonly']) !!}
                </div>
            </div>


            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-4 text-red" style="margin-top: 14px;">
                        <b>@lang('petro::lang.date_range'): <span class="15a9ab_from_date"></span> @lang('petro::lang.to') <span class="15a9ab_to_date"></span> </b>
                    </div>
                    <div class="col-md-5">
                        <div class="text-center">
                            <h5 style="font-weight: bold;">{{request()->session()->get('business.name')}} <br>
                                <span class="f15a9ab_location_name">@lang('petro::lang.all')</span>
                            </h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center pull-left">
                            <h5 style="font-weight: bold;" class="text-red">@lang('mpcs::lang.9b_form') @lang('mpcs::lang.form_no') : {{$F15a9ab_from_no}}</h5>
                        </div>
                    </div>
                </div>
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <h4>@lang('mpcs::lang.sales_status_section')</h4>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="form_9b_table">
                                        <thead>
                                            <tr>
                                                <th class="align-middle text-center">@lang('mpcs::lang.description')</th>
                                                <td class="align-middle text-center" >Total Sales</td>
                                                <td class="align-middle text-center" >Card Sales</td>
                                                <td class="align-middle text-center" >Cash Sales</td>
                                                <td class="align-middle text-center" > Empty Barrels </td>
                                                <td class="align-middle text-center" > Others </td>
                                                <td class="align-middle text-center" >Total</td>
                                                <td class="align-middle text-center" >Official use only</td>
                                                <td class="align-middle text-center" >After check</td>

                                            </tr>
                                              
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th>Cash</th>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                               
                                               
                                                <td rowspan="2">................. Receive date</td>
                                                <th>Excess Cash</th>
                                            </tr>
                                            <tr>
                                                <th>Deposit/Credit Sales</th>
                                                  <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td>Per date ...................</td>
                                            </tr>
                                            <tr>
                                                <th>MPCS Branch</th>
                                                  <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <th>Total Sales</th>
                                                  <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td>..........</td>
                                                <td>Per date ......................</td>
                                            </tr>
                                             <tr>
                                                <th>Total Sales upto previous day</th>
                                                  <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td>..........</td>
                                                <td>Per date ......................</td>
                                            </tr>
                                              <tr>
                                                <th>Total Sales as of today</th>
                                                  <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <th></th>
                                                 <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td>Given</td>
                                                <td>Today ......................</td>
                                            </tr>
                                            <tr>
                                                <th></th>
                                                 <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td>...............</td>
                                                <th>Excess Stamp</th>
                                            </tr>
                                            <tr>
                                                <th></th>
                                                 <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td>Checked</td>
                                                <td>Per date ................</td>
                                            </tr>
                                            <tr>
                                                <th></th>
                                                 <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td>..........</td>
                                                <td>Per date ......................</td>
                                            </tr>
                                            <tr>
                                                <th></th>
                                                 <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td>Approved</td>
                                                <td>Today ......................</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
        <div class="col-md-12 row">
            <!-- Left Side - Receipts Table and Numbered Items -->
            <div class="col-md-6">
                
                
                <!-- Receipts Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="form_15a9ab_receipts_table">
                        <thead>
                            <tr>
                                <th colspan="4" class="text-center">
                                    <h4>Receipts</h4>
                                </th>
                            </tr>
                            <tr>
                                <th>Previous Day</th>
                        <th>Description</th>
                        <th>Today</th>
                        <th>Total as of Today</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td></td>
                                <td>Cash Sales</td>                                
                                <td></td>
                                <td></td>
                                
                            </tr>
                            <tr>
                                <td></td>
                                <td>Card Sales</td>                                
                                <td></td>
                                <td></td>
                                
                            </tr>
                            <tr>
                                <td></td>
                                <td>Transport</td>                              
                                <td></td>
                                <td></td>
                                
                            </tr>
                            <tr>
                                <td></td>
                                <td>Empty Cans</td>
                                <td></td>
                                <td></td>
                                
                            </tr>
                            <tr>
                                <td></td>
                                <td>Other</td>
                                <td></td>
                                <td></td>
                                
                            </tr>
                            <tr>
                                <td></td>
                                <td>Total</td>
                                <td></td>
                                <td></td>
                                
                            </tr>
                            <tr>
                                <td></td>
                                <td>Credit Sales</td>
                                <td></td>
                                <td></td>
                                
                            </tr>
                            <tr>
                                <td></td>
                                <td>Changes</td>
                                <td></td>
                                <td></td>
                                
                            </tr>
                            <tr>
                                <td></td>
                                <td>MPCS Brances</td>
                                <td></td>
                                <td></td>
                                
                            </tr>
                        </tbody>
                    </table>
                </div>
        <!-- Numbered Items -->
            <div class="form-group" style="margin-bottom: 20px;">
                <div style="margin-bottom: 10px;">
                    1. Cash bill number ................<br>
                    2. Deposit bill number ................<br>
                    3. Credit bill number ................<br>
                    4. MPCS Branches ................<br>
                    Date ..............
                </div>
            </div>
    </div>

    <!-- Right Side - Payments Table and Signature Area -->
    <div class="col-md-6">
        <!-- Payments Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="form_15a9ab_payments_table">
                <thead>
                    <tr>
                        <th colspan="4" class="text-center">
                            <h4>Payments</h4>
                        </th>
                    </tr>
                    <tr>
                        <th>Previous Day</th>
                        <th colspan="2">Description</th>
                        <th>Today</th>
                        <th>Total as of Today</th>
                    </tr>
                </thead>
                 <tbody>
            <!-- Row 1 -->
            <tr>
                <td class="text-right"></td>
                <td class="text-left" >Cahser Receipt No</td>
                 <td class="text-left" >Cash</td>
                <td class="text-center"></td>
                <td class="text-center"></td>
            </tr>
            
            <!-- Row 2 -->
            <tr>
                <td class="text-right"></td>
                <td class="text-center">...........</td>
                 <td class="text-left" >Cheque/Card</td>
                <td class="text-center"></td>
                <td class="text-center"></td>
            </tr>
            
            <!-- Empty Rows -->
            <tr>
                <td></td>
                <td></td>
                 <td class="text-center" ></td>
                <td></td>
                <td class="text-center"></td>
            </tr>
            
            <tr>
                <td></td>
                <td></td>
                 <td class="text-center" ></td>
                <td></td>
                <td class="text-center"></td>
            </tr>
            
            <!-- Summary Rows -->
            <tr class="font-weight-bold">
                <td class="text-right"></td>
                <td class="text-center"></td>
                 <td class="text-right" >Total</td>
                <td class="text-center"></td>
                <td class="text-center"></td>
            </tr>
            
            <tr class="font-weight-bold">
                <td class="text-right"></td>
                <td class="text-center"></td>
                 <td class="text-right" >Balance in Hand</td>
                <td class="text-center"></td>
                <td class="text-center"></td>
            </tr>
            
            <tr class="font-weight-bold bg-light">
                <td class="text-right"></td>
                 <td class="text-center" ></td>
                <td class="text-right">Grand Total</td>
                <td></td>
                <td class="text-center"></td>
            </tr>
            
            <!-- Empty Footer Rows -->
            <tr>
                <td></td>
                <td></td>
                 <td class="text-center" ></td>
                <td></td>
                <td class="text-center"></td>
            </tr>
            
            <tr>
                <td></td>
                <td></td>
                 <td class="text-center" ></td>
                <td></td>
                <td class="text-center"></td>
            </tr>
            
         
        </tbody>
            </table>
        </div>
<div   class="mt-3 mb-4">
    <!-- Content will be dynamically inserted here -->
   
       <span id="textdetail"></span>
   
</div>
        <!-- Signature Area -->
        <div class="signature-area" style="margin-top: 20px;">
            <div class="row">
                <div class="col-md-6">
                    <div class="signature-line" style="border-top: 1px solid #000; margin-top: 50px; padding-top: 5px;">
                        Casher Signature
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="signature-line" style="border-top: 1px solid #000; margin-top: 50px; padding-top: 5px;">
                        Store Keeper Signature
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                        </div>
                    </div>
                </div>

            </div>
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->