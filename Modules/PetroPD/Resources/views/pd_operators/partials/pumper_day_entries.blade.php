@include('petropd::partials.pumper_dashboard_design_tweaks')


<!-- Main content -->
<section class="content">
<style>
.petropd-bottom-scroll-fix{overflow-x:auto !important; -webkit-overflow-scrolling:touch !important; padding-bottom:12px !important;}
.petropd-bottom-scroll-fix table{min-width:1250px !important;}
.petropd-bottom-scroll-fix th{white-space:normal !important; line-height:1.15 !important; text-align:center !important;}
.petropd-bottom-scroll-fix td{white-space:nowrap !important;}
</style>
    <style>
        #pumper_day_entries .petropd-day-entry-filter-row,
        .petropd-standalone-day-entries .petropd-day-entry-filter-row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-left: -8px;
            margin-right: -8px;
        }

        #pumper_day_entries .petropd-day-entry-filter-col,
        .petropd-standalone-day-entries .petropd-day-entry-filter-col {
            padding-left: 8px;
            padding-right: 8px;
        }

        #pumper_day_entries .petropd-day-entry-filter-row .form-group,
        .petropd-standalone-day-entries .petropd-day-entry-filter-row .form-group {
            margin-bottom: 8px;
        }

        #pumper_day_entries #day_entries_date_range,
        #pumper_day_entries .petropd-day-entry-filter-row .select2-container,
        .petropd-standalone-day-entries #day_entries_date_range,
        .petropd-standalone-day-entries .petropd-day-entry-filter-row .select2-container {
            width: 100% !important;
        }

        @media (max-width: 991px) {
            #pumper_day_entries .petropd-day-entry-filter-col,
            .petropd-standalone-day-entries .petropd-day-entry-filter-col {
                margin-bottom: 8px;
            }
        }
    </style>

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters'), 'id' => 'pumper_days'])
                @if(empty(auth()->user()->pump_operator_id))
                    <div class="row petropd-day-entry-filter-row">
                        <div class="col-md-4 col-sm-12 petropd-day-entry-filter-col">
                            <div class="form-group">
                                {!! Form::label('day_entries_date_range', __('report.date_range') . ':') !!}
                                {!! Form::text(
                                    'day_entries_date_range',
                                    @format_date(date('Y-m-d')) . ' - ' . @format_date(date('Y-m-d')),
                                    [
                                        'class' => 'form-control',
                                        'id' => 'day_entries_date_range',
                                        'placeholder' => __('lang_v1.select_a_date_range'),
                                        'readonly' => true,
                                    ]
                                ) !!}
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-12 petropd-day-entry-filter-col">
                            <div class="form-group">
                                {!! Form::label('day_entries_pump_operator_id', __('petropd::lang.pump_operator') . ':') !!}
                                <select class="form-control select2" id="day_entries_pump_operator_id" style="width:100%;">
                                    <option value="">@lang('petropd::lang.all')</option>
                                    @foreach(($close_shift_active_pump_operators ?? $pump_operators ?? collect()) as $operatorId => $operatorName)
                                        <option value="{{ $operatorId }}">{{ $operatorName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-12 petropd-day-entry-filter-col">
                            <div class="form-group">
                                {!! Form::label('day_entries_shift_id', __('petropd::lang.shift_number') . ':') !!}
                                <select class="form-control select2" id="day_entries_shift_id" style="width:100%;">
                                    <option value="">@lang('petropd::lang.all')</option>
                                </select>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="row">
                        <div class="col-md-4 px-4" style="margin-left:35px;">
                            <div class="form-group">
                                {!! Form::label('day_entries_shift_id', __('petropd::lang.shift') . ':') !!}
                                <select class="form-control select2" style="width:100%" id="day_entries_shift_id">
                                    <option value="">@lang('messages.all')</option>
                                    @foreach($shifts as $shift)
                                        @php
                                            $displayShiftDate = $shift->shift_date ?? $shift->date ?? $shift->created_at ?? null;
                                            $displayShiftNo = $shift->shift_number ?? $shift->shift_no ?? $shift->id;
                                        @endphp
                                        <option value="{{ $shift->id }}" @if(!empty($selected_shift_id) && $selected_shift_id == $shift->id) selected @endif>{{ $shift->name }} - Shift {{ $displayShiftNo }} ({{ !empty($displayShiftDate) ? @format_date($displayShiftDate) : '-' }} to {{ !empty($shift->closed_time) ? @format_datetime($shift->closed_time) : 'Open' }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endif
            @endcomponent
        </div>
    </div>


    <div class="row" id="pumper_day_entry_summary">

    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('petropd::lang.all_your_daily_collection')])
    <div class="table-responsive petropd-bottom-scroll-fix">
        <table class="table table-bordered table-striped" id="pump_operators_day_entries_table" style="width: 100%;">
            <thead>
                <tr>
                    <th class="notexport">@lang('messages.action')</th>
                    <th>@lang('petropd::lang.date')</th>
                    <th>@lang('petropd::lang.location')</th>

                    @if(empty(auth()->user()->pump_operator_id))
                    <th>@lang('petropd::lang.settlement_no')</th>
                    @endif
                    <th>@lang('petropd::lang.pump_operator')</th>
                    <th>@lang('petropd::lang.shift')</th>
                    <th>@lang('petropd::lang.pump')</th>
                    <th>@lang('petropd::lang.starting_meter')</th>
                    <th>@lang('petropd::lang.closing_meter')</th>
                    <th>@lang('petropd::lang.test_qty')</th>
                    <th>@lang('petropd::lang.sold_ltr')</th>
                    <th>@lang('petropd::lang.amount')</th>
                    <th>@lang('petropd::lang.short_amount')</th>

                </tr>
            </thead>

            <tfoot>
                <tr class="bg-gray font-17 footer-total">
                    <td colspan="@if(!empty(auth()->user()->pump_operator_id)) 9 @else 10 @endif" class="text-right"><strong>@lang('sale.total'):</strong></td>
                    <td><span class="display_currency" id="footer_sold_ltr" data-currency_symbol="false"></span></td>
                    <td><span class="display_currency" id="footer_sold_amount" data-currency_symbol="true"></span></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->
