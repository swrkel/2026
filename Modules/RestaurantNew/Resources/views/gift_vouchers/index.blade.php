@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::gift_voucher.title'))

@section('content')
<div class="restnew-page restnew-gift-vouchers">
    <div class="restnew-toolbar">
        <h3>{{ __('restaurantnew::gift_voucher.title') }}</h3>
        <a href="{{ route('restaurant-new.gift-vouchers.create') }}" class="btn btn-primary">{{ __('restaurantnew::gift_voucher.issue_new') }}</a>
    </div>

    <div class="restnew-card">
        <table class="table table-bordered table-striped restnew-datatable">
            <thead>
                <tr>
                    <th>{{ __('restaurantnew::gift_voucher.voucher_no') }}</th>
                    <th>{{ __('restaurantnew::gift_voucher.customer') }}</th>
                    <th>{{ __('restaurantnew::gift_voucher.issue_amount') }}</th>
                    <th>{{ __('restaurantnew::gift_voucher.balance') }}</th>
                    <th>{{ __('restaurantnew::gift_voucher.status') }}</th>
                    <th>{{ __('restaurantnew::gift_voucher.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vouchers as $voucher)
                    <tr>
                        <td>{{ $voucher->voucher_no }}</td>
                        <td>{{ $voucher->customer_name }}</td>
                        <td>{{ number_format($voucher->issue_amount, 4) }}</td>
                        <td>{{ number_format($voucher->balance_amount, 4) }}</td>
                        <td><span class="label label-info">{{ ucfirst($voucher->status) }}</span></td>
                        <td><a href="{{ route('restaurant-new.gift-vouchers.show', $voucher) }}" class="btn btn-xs btn-primary">{{ __('restaurantnew::gift_voucher.view') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">{{ __('restaurantnew::gift_voucher.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $vouchers->links() }}
    </div>
</div>
@endsection
