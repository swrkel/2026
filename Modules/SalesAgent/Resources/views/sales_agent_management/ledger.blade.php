@extends('layouts.app')
@section('title', __('lang_v1.sales_agent_ledger'))

@section('content')

<section class="content-header">
    <h1>@lang('lang_v1.sales_agent_ledger')
        <small>{{ $sales_agent->name }}</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('salesagent.management.index') }}"><i class="fa fa-arrow-left"></i> @lang('lang_v1.back_to_list')</a></li>
    </ol>
</section>

<section class="content">
    {{-- Sales Agent Info --}}
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('lang_v1.sales_agent_details')</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>@lang('lang_v1.name'):</strong> {{ $sales_agent->name }}
                </div>
                <div class="col-md-3">
                    <strong>@lang('lang_v1.employment_grade'):</strong> {{ $sales_agent->employment_grade ?: '-' }}
                </div>
                <div class="col-md-3">
                    <strong>@lang('lang_v1.salary'):</strong> {{ number_format($sales_agent->salary, 2) }}
                </div>
                <div class="col-md-3">
                    <strong>@lang('lang_v1.commission'):</strong> {{ number_format($sales_agent->commission, 2) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Ledger Table (Placeholder - Basic structure for future integration) --}}
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('lang_v1.ledger_entries')</h3>
        </div>
        <div class="box-body">
            @php
                $commission_history = collect($sales_agent->commission_history ?? [])->sortBy('commission_date');
            @endphp
            @if($commission_history->count() > 0)
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.date_time')</th>
                        <th>@lang('lang_v1.description')</th>
                        <th>@lang('lang_v1.ref_bill_no')</th>
                        <th class="text-right">@lang('lang_v1.debit')</th>
                        <th class="text-right">@lang('lang_v1.credit')</th>
                        <th class="text-right">@lang('lang_v1.balance')</th>
                    </tr>
                </thead>
                <tbody>
                    @php $balance = 0; @endphp
                    @foreach($commission_history as $commission)
                    @php $balance += $commission['amount']; @endphp
                    <tr>
                        <td>
                            @if(!empty($commission['commission_date']))
                                {{ \Carbon\Carbon::parse($commission['commission_date'])->format('d/m/Y H:i') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @lang('lang_v1.commission_entry')
                            @if(!empty($commission['commission_for']))
                                - {{ $commission['commission_for'] }}
                            @endif
                            @if(!empty($commission['period_start']) && !empty($commission['period_end']))
                                ({{ \Carbon\Carbon::parse($commission['period_start'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($commission['period_end'])->format('d/m/Y') }})
                            @endif
                        </td>
                        <td>{{ $commission['ref_bill_no'] ?? '-' }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($commission['amount'], 2) }}</td>
                        <td class="text-right">{{ number_format($balance, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray">
                        <th colspan="4" class="text-right">@lang('sale.total'):</th>
                        <th class="text-right">{{ number_format($commission_history->sum('amount'), 2) }}</th>
                        <th class="text-right">{{ number_format($balance, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
            @else
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> @lang('lang_v1.no_ledger_entries')
            </div>
            @endif
            
            <p class="text-muted">
                <i class="fa fa-info-circle"></i> @lang('lang_v1.ledger_placeholder_note')
            </p>
        </div>
    </div>
</section>

@endsection
