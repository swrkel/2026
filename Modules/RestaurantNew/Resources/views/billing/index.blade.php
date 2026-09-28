@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::lang.billing'))

@section('content')
<div class="restaurantnew-page restaurantnew-billing-page">
    @include('restaurantnew::partials.toolbar', [
        'title' => __('restaurantnew::lang.billing'),
        'buttons' => [
            ['label' => __('restaurantnew::lang.new_bill'), 'url' => route('restaurantnew.billing.create'), 'class' => 'btn btn-primary']
        ]
    ])

    <div class="card pos-standard-card">
        <div class="card-body table-responsive">
            <table class="table table-sm table-striped restaurantnew-table">
                <thead>
                    <tr>
                        <th>@lang('restaurantnew::lang.bill_no')</th>
                        <th>@lang('restaurantnew::lang.date')</th>
                        <th class="text-right">@lang('restaurantnew::lang.grand_total')</th>
                        <th class="text-right">@lang('restaurantnew::lang.paid')</th>
                        <th class="text-right">@lang('restaurantnew::lang.balance')</th>
                        <th>@lang('restaurantnew::lang.payment_status')</th>
                        <th>@lang('restaurantnew::lang.status')</th>
                        <th class="text-right">@lang('restaurantnew::lang.action')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bills as $bill)
                        <tr>
                            <td>{{ $bill->bill_no }}</td>
                            <td>{{ optional($bill->bill_date)->format('Y-m-d H:i') }}</td>
                            <td class="text-right">{{ number_format($bill->grand_total, 4) }}</td>
                            <td class="text-right">{{ number_format($bill->paid_total, 4) }}</td>
                            <td class="text-right">{{ number_format($bill->balance_due, 4) }}</td>
                            <td><span class="badge badge-info">{{ ucfirst($bill->payment_status) }}</span></td>
                            <td><span class="badge badge-secondary">{{ ucfirst($bill->bill_status) }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('restaurantnew.billing.show', $bill->id) }}" class="btn btn-xs btn-outline-primary">@lang('restaurantnew::lang.view')</a>
                                <a href="{{ route('restaurantnew.billing.receipt', $bill->id) }}" class="btn btn-xs btn-outline-dark" target="_blank">@lang('restaurantnew::lang.receipt')</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">@lang('restaurantnew::lang.no_records_found')</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $bills->links() }}
        </div>
    </div>
</div>
@endsection
