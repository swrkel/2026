@extends('customers::layouts.action', ['title' => 'Balance Details'])

@section('customer_action_body')
<div class="customer-balance-details-wrapper">
    <style>
        .customer-balance-details-wrapper .balance-table {
            width: 100% !important;
            margin-bottom: 0 !important;
            font-family: inherit !important;
            font-size: inherit !important;
        }

        .customer-balance-details-wrapper .balance-table td,
        .customer-balance-details-wrapper .balance-table th {
            vertical-align: middle !important;
            padding: 12px 14px !important;
        }

        .customer-balance-details-wrapper .balance-label {
            font-weight: 600 !important;
            color: #334155 !important;
        }

        .customer-balance-details-wrapper .balance-amount {
            text-align: right !important;
            font-weight: 600 !important;
            white-space: nowrap !important;
        }

        .customer-balance-details-wrapper .balance-total-row th {
            font-size: 15px !important;
            color: #dc2626 !important;
            background: #fff7f7 !important;
        }
    </style>

    <table class="table table-bordered table-striped balance-table">
        <tbody>
            <tr>
                <td class="balance-label">@lang('customers::lang.total_sale')</td>
                <td class="balance-amount">
                    <span class="display_currency" data-currency_symbol="true">
                        {{ @num_format($balance_details['total_sale'] ?? 0) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="balance-label">@lang('customers::lang.opening_balance')</td>
                <td class="balance-amount">
                    <span class="display_currency" data-currency_symbol="true">
                        {{ @num_format($balance_details['opening_balance'] ?? 0) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="balance-label">@lang('customers::lang.total_paid')</td>
                <td class="balance-amount">
                    <span class="display_currency" data-currency_symbol="true">
                        {{ @num_format($balance_details['total_paid'] ?? 0) }}
                    </span>
                </td>
            </tr>
            <tr class="balance-total-row">
                <th>@lang('customers::lang.balance_due')</th>
                <th class="balance-amount">
                    <span class="display_currency" data-currency_symbol="true">
                        {{ @num_format($balance_details['total_balance'] ?? 0) }}
                    </span>
                </th>
            </tr>
        </tbody>
    </table>
</div>
@endsection
