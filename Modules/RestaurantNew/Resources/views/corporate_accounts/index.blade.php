@extends('restaurantnew::layouts.app')
@section('title', __('corporate.corporate_accounts'))
@section('content')
<div class="restnew-page restnew-corporate-page">
    <div class="restnew-toolbar">
        <h3>{{ __('corporate.corporate_accounts') }}</h3>
        <a href="{{ route('restaurant-new.corporate.create') }}" class="btn btn-primary">{{ __('corporate.new_account') }}</a>
    </div>
    <div class="restnew-card">
        <div class="table-responsive">
            <table class="table table-bordered table-striped restnew-datatable">
                <thead><tr><th>Code</th><th>Company</th><th>Contact</th><th>Credit Limit</th><th>Balance</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($accounts as $account)
                    <tr>
                        <td>{{ $account->account_code }}</td>
                        <td>{{ $account->company_name }}</td>
                        <td>{{ $account->contact_person }}<br><small>{{ $account->mobile }}</small></td>
                        <td class="text-right">{{ number_format($account->credit_limit, 4) }}</td>
                        <td class="text-right">{{ number_format($account->current_balance, 4) }}</td>
                        <td>{{ ucfirst($account->status) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $accounts->links() }}
    </div>
</div>
@endsection
