

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

            <div class="row">
                 <div class="col-md-4 px-4" style="margin-left: 35px;">
                    <div class="form-group">
                        {!! Form::label('shift_id',  __('petro::lang.shift') . ':') !!}
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
    __('petro::lang.all_your_daily_collection')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="pump_operators_day_entries_table" style="width: 100%;">
            <thead>
                <tr>
                    <th class="notexport">@lang('messages.action')</th>
                    <th>@lang('petro::lang.date')</th>
                    <th>@lang('petro::lang.location')</th>

                    @if(empty(auth()->user()->pump_operator_id))
                    <th>@lang('petro::lang.settlement_no')</th>
                    @endif
                    <th>@lang('petro::lang.pump_operator')</th>
                    <th>@lang('petro::lang.shift')</th>
                    <th>@lang('petro::lang.pump')</th>
                    <th>@lang('petro::lang.starting_meter')</th>
                    <th>@lang('petro::lang.closing_meter')</th>
                    <th>@lang('petro::lang.test_qty')</th>
                    <th>@lang('petro::lang.sold_ltr')</th>
                    <th>@lang('petro::lang.amount')</th>
                    <th>@lang('petro::lang.short_amount')</th>

                </tr>
            </thead>

            <tfoot>
                <tr class="bg-gray font-17 footer-total">
                    <td colspan="@if(!empty(auth()->user()->pump_operator_id)) 8 @else 9 @endif" class="text-right"><strong>@lang('sale.total'):</strong></td>
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