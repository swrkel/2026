@extends('layouts.app')
@section('title', __('carrier.title'))
@section('content')
<section class="content-header stn-page-header">
    <h1>{{ __('carrier.title') }}</h1>
    <a href="{{ route('stock-transfer-new.carrier-invoices.create') }}" class="btn btn-primary pull-right">{{ __('carrier.new_invoice') }}</a>
</section>
<section class="content stn-carrier-page">
    <div class="box box-solid">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn-pos-table">
                <thead>
                    <tr>
                        <th>{{ __('carrier.invoice_no') }}</th>
                        <th>{{ __('carrier.transfer') }}</th>
                        <th>{{ __('carrier.carrier') }}</th>
                        <th>{{ __('carrier.invoice_date') }}</th>
                        <th class="text-right">{{ __('carrier.total') }}</th>
                        <th>{{ __('carrier.status') }}</th>
                        <th>{{ __('carrier.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_no }}</td>
                            <td>{{ $invoice->transfer_id }}</td>
                            <td>{{ $invoice->carrier_name }}</td>
                            <td>{{ optional($invoice->invoice_date)->format('Y-m-d') }}</td>
                            <td class="text-right">{{ number_format($invoice->total_amount, 4) }}</td>
                            <td><span class="label label-info">{{ ucfirst($invoice->status) }}</span></td>
                            <td>
                                @if($invoice->status === 'draft')
                                    <form method="POST" action="{{ route('stock-transfer-new.carrier-invoices.approve', $invoice) }}" class="stn-inline-form">@csrf<button class="btn btn-xs btn-success">{{ __('carrier.approve') }}</button></form>
                                    <form method="POST" action="{{ route('stock-transfer-new.carrier-invoices.cancel', $invoice) }}" class="stn-inline-form">@csrf<button class="btn btn-xs btn-danger">{{ __('carrier.cancel') }}</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">{{ __('carrier.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $invoices->links() }}
        </div>
    </div>
</section>
@endsection
