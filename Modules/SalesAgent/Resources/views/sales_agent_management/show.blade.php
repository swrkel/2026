{{-- View Sales Agent Modal Content --}}
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title">@lang('lang_v1.view_sales_agent'): {{ $sales_agent->name }}</h4>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6">
            <table class="table table-bordered">
                <tr>
                    <th>@lang('lang_v1.sales_agent_name')</th>
                    <td>{{ $sales_agent->name }}</td>
                </tr>
                <tr>
                    <th>@lang('lang_v1.joined_date')</th>
                    <td>{{ optional($sales_agent->joined_date)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>@lang('purchase.business_location')</th>
                    <td>{{ $sales_agent->location ? $sales_agent->location->name : '-' }}</td>
                </tr>
                <tr>
                    <th>@lang('lang_v1.employment_grade')</th>
                    <td>{{ $sales_agent->employment_grade ?: '-' }}</td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <table class="table table-bordered">
                <tr>
                    <th>@lang('lang_v1.salary')</th>
                    <td class="text-right">{{ number_format($sales_agent->salary, 2) }}</td>
                </tr>
                <tr>
                    <th>@lang('lang_v1.commission')</th>
                    <td class="text-right">{{ number_format($sales_agent->commission, 2) }}</td>
                </tr>
                <tr>
                    <th>@lang('lang_v1.linked_user')</th>
                    <td>{{ $sales_agent->user ? $sales_agent->user->first_name . ' ' . $sales_agent->user->last_name : '-' }}</td>
                </tr>
                <tr>
                    <th>@lang('lang_v1.added_by')</th>
                    <td>{{ $sales_agent->createdBy ? $sales_agent->createdBy->first_name . ' ' . $sales_agent->createdBy->last_name : '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    @php
        $commission_history = collect($sales_agent->commission_history ?? []);
    @endphp

    @if($commission_history->count() > 0)
    <hr>
    <h4>@lang('lang_v1.commission_history')</h4>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>@lang('lang_v1.date_time')</th>
                <th>@lang('lang_v1.period')</th>
                <th>@lang('lang_v1.commission_for')</th>
                <th>@lang('lang_v1.ref_bill_no')</th>
                <th class="text-right">@lang('lang_v1.amount')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commission_history as $commission)
            <tr>
                <td>
                    @if(!empty($commission['commission_date']))
                        {{ \Carbon\Carbon::parse($commission['commission_date'])->format('d/m/Y H:i') }}
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if(!empty($commission['period_start']) && !empty($commission['period_end']))
                        {{ \Carbon\Carbon::parse($commission['period_start'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($commission['period_end'])->format('d/m/Y') }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $commission['commission_for'] ?? '-' }}</td>
                <td>{{ $commission['ref_bill_no'] ?? '-' }}</td>
                <td class="text-right">{{ number_format($commission['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">@lang('sale.total'):</th>
                <th class="text-right">{{ number_format($commission_history->sum('amount'), 2) }}</th>
            </tr>
        </tfoot>
    </table>
    @endif
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
</div>
