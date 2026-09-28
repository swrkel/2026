

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

            <div class="row">
                 <div class="col-md-4 px-4" style="margin-left: 35px;">
                    <div class="form-group">
                        {!! Form::label('shift_id',  __('petrogeneral::lang.shift') . ':') !!}
                        <select class="form-control select2" style = 'width:100%' id="shift_id">
                            <option value="">@lang('messages.all')</option>
                            @foreach($shifts as $shift)
                                <option value="{{$shift->id}}">{{$shift->name." (".@format_date($shift->shift_date)}} to {{!empty($shift->closed_time) ? @format_datetime($shift->closed_time) : 'Open'}} )</option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </div>


            @endcomponent
        </div>
    </div>


    <div class="row" id="pumper_day_entry_summary">

    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('petrogeneral::lang.all_your_daily_collection')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="pump_operators_day_entries_table" style="width: 100%;">
            <thead>
                <tr>
                    <th class="notexport">@lang('messages.action')</th>
                    <th>@lang('petrogeneral::lang.date')</th>
                    <th>@lang('petrogeneral::lang.location')</th>

                    @if(empty(auth()->user()->pump_operator_id))
                    <th>@lang('petrogeneral::lang.settlement_no')</th>
                    @endif
                    <th>@lang('petrogeneral::lang.pump_operator')</th>
                    <th>@lang('petrogeneral::lang.shift')</th>
                    <th>@lang('petrogeneral::lang.pump')</th>
                    <th>@lang('petrogeneral::lang.starting_meter')</th>
                    <th>@lang('petrogeneral::lang.closing_meter')</th>
                    <th>@lang('petrogeneral::lang.test_qty')</th>
                    <th>@lang('petrogeneral::lang.sold_ltr')</th>
                    <th>@lang('petrogeneral::lang.amount')</th>
                    <th>@lang('petrogeneral::lang.short_amount')</th>

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
