<!-- Main content -->
<style>
    footer.main-footer.no-print {
        margin-left: 0 !important;
    }
</style>
@if (auth()->user()->is_pump_operator)
    <div class="col-md-12">
        <a href="{{ action('Auth\PumpOperatorLoginController@logout') }}" class="btn btn-flat btn-lg pull-right"
            style=" background-color: orange; color: #fff; margin-left: 5px;">@lang('petrogeneral::lang.logout')</a>
        <span><a href="{{ action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorController@dashboard') }}"
                class="btn btn-flat btn-lg pull-right"
                style="color: #fff; background-color:#810040;">@lang('petrogeneral::lang.dashboard')</a></span>
    </div>
    <div class="clearfix"></div>
@endif

<section class="content" style="overflow: visible;">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    @foreach ($pumps as $pump)
                        @if ($pump->pump_operator_id && (empty($pump->assignment_status ?? $pump->status) || ($pump->assignment_status ?? $pump->status) == 'open'))
                            @php
                                $cardColor = '#f4b400'; // yellow: assigned, open shift
                                if (!empty($pump->is_confirmed)) {
                                    $cardColor = '#8F3A84'; // purple: received by pumper
                                }
                            @endphp
                            <div class="col-md-2 text-center"
                                style="background: {{ $cardColor }}; margin: 10px 10px 10px 20px; color: #fff; padding: 15px;">
                                <h3 style="padding: 0px 0px 6px 0px; margin: 10px 0;"> {{ $pump->pump_no }}</h3>
                                <h4 style="padding: 0px 0px 6px 0px; margin: 5px 0; font-size: 14px;">
                                    {{ $pump->pumper_name }}</h4>
                                <h5 style="padding: 0px 0px 6px 0px; margin: 5px 0; font-size: 13px;"> Shift No:
                                    {{ $pump->shift_number ?? ($pump->shift_id ?? 'N/A') }}</h5>
                            </div>
                        @elseif (!empty($pump->is_settled))
                            {{-- Blue: settlement completed --}}
                            <div class="col-md-2 text-center"
                                style="background: #337ab7; margin: 10px 10px 10px 20px; color: #fff; padding: 15px;">
                                <h3 style="padding: 0px 0px 6px 0px; margin: 10px 0;"> {{ $pump->pump_no }}</h3>
                                <h4 style="padding: 0px 0px 6px 0px; margin: 5px 0; font-size: 14px;">
                                    {{ $pump->pumper_name }}</h4>
                                <h5 style="padding: 0px 0px 6px 0px; margin: 5px 0; font-size: 13px;">
                                    {{ $pump->settlement_no }}</h5>
                            </div>
                        @else
                            {{-- Blue: unassigned --}}
                            <div class="col-md-2 text-center bg-primary"
                                style="margin: 10px 10px 10px 20px; color: #fff; padding: 0;">
                                <button type="button" class="btn  btn-primary btn-flat btn-modal"
                                    style="height: 140px; width:100%; background: transparent; border: 0px;"
                                    data-href="{{ action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorAssignmentController@getPumperAssignment', ['pump_id' => $pump->id, 'pump_operator_id' => auth()->user()->pump_operator_id ?? 0]) }}"
                                    data-container=".pump_operator_modal">
                                    <h3> {{ $pump->pump_no }}</h3>
                                </button>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', [
        'class' => 'box-primary',
        'title' => __('petrogeneral::lang.all_your_daily_collection'),
    ])

        @slot('tool')
            <div class="row">
                <div class="box-tools pull-right ">
                    @can('bulk_assign_pumps')
                        <button type="button" class="btn  btn-primary btn-modal"
                            data-href="{{ action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorAssignmentController@create') }}"
                            data-container=".pump_operator_modal">
                            <i class="fa fa-plus"></i> @lang('petrogeneral::lang.assign_pumps')</button>
                    @endcan
                </div>
            </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="list_daily_collection_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="notexport">@lang('messages.action')</th>
                        <th>@lang('petrogeneral::lang.date')</th>
                        <th>@lang('petrogeneral::lang.shift_number')</th>
                        <th>@lang('petrogeneral::lang.settlement_no')</th>
                        <!--<th>@lang('petrogeneral::lang.location')</th>-->
                        <th>@lang('petrogeneral::lang.pump_operator')</th>
                        <th>@lang('petrogeneral::lang.pump_no')</th>
                        <th>@lang('petrogeneral::lang.starting_meter')</th>
                        <th>@lang('petrogeneral::lang.closing_meter')</th>
                        <th>@lang('petrogeneral::lang.sold_ltr')</th>
                        <th>@lang('petrogeneral::lang.testing_ltr')</th>
                        <th>@lang('petrogeneral::lang.sold_amount')</th>

                    </tr>
                </thead>

                <tfoot>
                    <tr class="bg-gray font-17 footer-total text-center">
                        <!--<td colspan="7"><strong>@lang('sale.total'):</strong></td>-->
                        <!--<td><span class="display_currency" id="dc_footer_sold_fuel_qty" data-currency_symbol="false"></span></td>-->
                        <!--<td><span class="display_currency" id="dc_footer_testing_qty" data-currency_symbol="false"></span></td>-->
                        <!--<td><span class="display_currency" id="dc_footer_sold_fuel_amount" data-currency_symbol="false"></span></td>-->

                    </tr>
                </tfoot>
            </table>
        </div>
    @endcomponent

</section>
<!-- /.content -->
