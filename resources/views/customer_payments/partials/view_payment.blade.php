<div class="row">
    <div class="col-sm-6">
        @if (!empty($parent_payment->contact))
            <b>{{ __('customer.customer') }}:</b>
            <br> {{ $parent_payment->contact->name }}
            <br>
        @endif
        {{ __('business.address') }}:
        <br>

        @if (!empty($parent_payment->contact))
            @if ($parent_payment->contact->landmark)
                {{ $parent_payment->contact->landmark }},
            @endif
            {{ $parent_payment->contact->city }}
            @if ($parent_payment->contact->state)
                {{ ',' . $parent_payment->contact->state }}
                <br>
            @endif
            @if ($parent_payment->contact->country)
                {{ $parent_payment->contact->country }}
                <br>
            @endif
            @if ($parent_payment->contact->mobile)
                {{ __('contact.mobile') }}: {{ $parent_payment->contact->mobile }}
            @endif
            @if ($parent_payment->contact->alternate_number)
                <br> {{ __('contact.alternate_contact_number') }}: {{ $parent_payment->contact->alternate_number }}
            @endif
            @if ($parent_payment->contact->landline)
                <br> {{ __('contact.landline') }}: {{ $parent_payment->contact->landline }}
            @endif
        @endif

    </div>
    <div class="col-sm-6">
        <b>@lang('lang_v1.location')</b> : {{ $location }}
        <br>

        <b>@lang('customer_payments.payment_ref_no')</b> : {{ $parent_payment->payment_ref_no }}
        <br>
        <b>@lang('messages.date'):</b> {{ @format_date($parent_payment->paid_on) }}
    </div>
</div>
<br>
<div class="row">
    <div class="col-md-6">
        @php
            $final_total = $parent_payment->amount;
            $total_interest = $parent_payment->total_interest ?? 0;
            $total_amount_payable = $final_total + $total_interest;
        @endphp
        <b>@lang('sale.amount')</b> : {{ number_format($final_total, $company->currency_precision) }}
        <br>
        @if (!empty($total_interest) && $total_interest > 0)
            <b>@lang('lang_v1.total_interest_paid')</b> : {{ number_format($total_interest, $company->currency_precision) }}
            <br>
            <b>@lang('lang_v1.total_amount_payable')</b> : {{ number_format($total_amount_payable, $company->currency_precision) }}
            <br>
        @endif
        <b>@lang('sale.payment_method')</b> : @php $paid_in_types = ['customer_page' => 'Customer Page', 'all_sale_page' => 'All Sale Page', 'settlement' => 'Settlement']; @endphp
        {{ $parent_payment->method }}
        <br>
        @if ($parent_payment->method == 'cheque')
            <b>@lang('sale.cheque_number')</b> : {{ $parent_payment->cheque_number }}
            <br>
            <b>@lang('sale.bank_name')</b> : {{ $parent_payment->bank_name }}
            <br>
            <b>@lang('sale.cheque_date')</b> : {{ $parent_payment->created_at->format('m/d/Y') }}
            <br>
        @endif
        </br>

        <div class="row">
            <div class="col-sm-4 text-right"><b>@lang('sale.bill_no')</b></div>
            <div class="col-sm-4 text-right"><b>@lang('lang_v1.interest')</b></div>
            <div class="col-sm-4 text-right"><b>@lang('sale.amount')</b></div>
        </div>


        @foreach ($child_payments as $payment_line)
            <div class="row">
                <div class="col-sm-4 text-right">
                    @if (!empty($payment_line->transaction) && in_array($payment_line->transaction->type, ['opening_balance', 'fleet_opening_balance']))
                        Opening balance
                    @else
                        {{ !empty($payment_line->transaction) && !empty($payment_line->transaction->invoice_no) ? $payment_line->transaction->invoice_no : $payment_line->payment_ref_no }}
                    @endif
                </div>
                <div class="col-sm-4 text-right">
                    {{ number_format($payment_line->interest_amount ?? 0, $company->currency_precision) }}</div>
                <div class="col-sm-4 text-right">
                    {{ number_format($payment_line->amount, $company->currency_precision) }}</div>
            </div>
        @endforeach

        <hr style="border-top: 2px solid #000;">
        <div class="row">
            <div class="col-sm-4 text-right"></div>
            <div class="col-sm-4 text-right text-danger">
                <b>{{ number_format($total_interest, $company->currency_precision) }}</b>
            </div>
            <div class="col-sm-4 text-right text-danger">
                <b>{{ number_format($final_total, $company->currency_precision) }}</b>
            </div>
        </div>
        @if (!empty($total_interest) && $total_interest > 0)
            <div class="row" style="font-size: 16px; margin-top: 10px;">
                <div class="col-sm-8 text-right"><b>@lang('lang_v1.total_amount_payable')</b></div>
                <div class="col-sm-4 text-right text-success">
                    <b>{{ number_format($total_amount_payable, $company->currency_precision) }}</b>
                </div>
            </div>
        @else
            <div class="row" style="font-size: 16px; margin-top: 10px;">
                <div class="col-sm-8 text-right"><b>@lang('sale.total_amount')</b></div>
                <div class="col-sm-4 text-right text-success">
                    <b>{{ number_format($final_total, $company->currency_precision) }}</b>
                </div>
            </div>
        @endif


    </div>
    <div class="col-md-6">
        <b>@lang('sale.payment_note')</b> : {{ $parent_payment->payment_note }}
        <br>
    </div>
</div>

@if (!empty($customer_change_logs) && $customer_change_logs->count() > 0)
    <hr style="margin-top: 20px; margin-bottom: 20px; border-top: 2px solid #ddd;">
    <div class="row">
        <div class="col-md-12">
            <h4 style="margin-top: 0; margin-bottom: 20px; color: #3c8dbc; font-weight: 600; font-size: 14px;">
                <i class="fa fa-history"></i> Customer Change History
            </h4>
            <div class="table-responsive">
                <table class="table table-hover" style="background-color: #fff; border: 1px solid #ddd;">
                    <thead
                        style="background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%); border-bottom: 2px solid #dee2e6;">
                        <tr>
                            <th style="width: 22%; padding: 12px; font-weight: 600; color: #495057; font-size: 13px;">
                                <i class="fa fa-clock-o"></i> Date & Time
                            </th>
                            <th style="padding: 12px; font-weight: 600; color: #495057; font-size: 13px;">
                                <i class="fa fa-exchange"></i> Change Details
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customer_change_logs as $log)
                            <tr style="border-bottom: 1px solid #e9ecef;">
                                <td style="vertical-align: middle; padding: 15px; color: #495057; font-size: 13px;">
                                    <div style="line-height: 1.8;">
                                        <div style="margin-bottom: 4px;">
                                            <i class="fa fa-calendar" style="color: #6c757d; width: 16px;"></i>
                                            <span
                                                style="font-weight: 500;">{{ $log->created_at->format('d/m/Y') }}</span>
                                        </div>
                                        <div>
                                            <i class="fa fa-clock-o" style="color: #6c757d; width: 16px;"></i>
                                            <span
                                                style="font-weight: 400;">{{ $log->created_at->format('H:i:s') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td style="vertical-align: middle; padding: 15px;">
                                    @php
                                        $properties = $log->properties;
                                        $old_customer = null;
                                        $new_customer = null;
                                        $user = null;
                                        $raw_message = null;

                                        $extractFromStructured = function ($data) use (
                                            &$old_customer,
                                            &$new_customer,
                                            &$user,
                                        ) {
                                            if (isset($data['old_customer_name'], $data['new_customer_name'])) {
                                                $old_customer = trim($data['old_customer_name']);
                                                $new_customer = trim($data['new_customer_name']);
                                                $user = isset($data['changed_by']) ? trim($data['changed_by']) : null;
                                                return true;
                                            }

                                            return false;
                                        };

                                        if (is_array($properties)) {
                                            if (!$extractFromStructured($properties)) {
                                                $raw_message = json_encode($properties);
                                            }
                                        } elseif (is_object($properties) && method_exists($properties, 'toArray')) {
                                            $properties_array = $properties->toArray();
                                            if (!$extractFromStructured($properties_array)) {
                                                $raw_message = json_encode($properties_array);
                                            }
                                        } elseif (is_string($properties)) {
                                            $raw_message = $properties;
                                        } elseif (!empty($properties)) {
                                            $raw_message = json_encode($properties);
                                        }

                                        if (
                                            is_null($raw_message) &&
                                            (is_null($old_customer) || is_null($new_customer))
                                        ) {
                                            $raw_message = json_encode($properties);
                                        }

                                        if (
                                            !empty($raw_message) &&
                                            (is_null($old_customer) || is_null($new_customer))
                                        ) {
                                            $normalized_message = trim($raw_message, '"[]');

                                            $new_pattern =
                                                '/Changed customer\s+(.*?)\s+\(old customer name\)\s+and to Customer\s+(.*?)\s+\(new customer name\),\s+by the User\s+(.*)$/i';
                                            $old_pattern =
                                                '/from (.+?) \(ID: (\d+)\) to (.+?) \(ID: (\d+)\) by User (.+?)(?:\s*["\]]+)?$/';

                                            if (preg_match($new_pattern, $normalized_message, $matches_new)) {
                                                $old_customer = trim($matches_new[1]);
                                                $new_customer = trim($matches_new[2]);
                                                $user = preg_replace('/["\]\s]+$/', '', trim($matches_new[3]));
                                            } elseif (
                                                preg_match($old_pattern, $normalized_message, $matches_old) &&
                                                count($matches_old) >= 6
                                            ) {
                                                $old_customer = trim($matches_old[1]);
                                                $new_customer = trim($matches_old[3]);
                                                $user = preg_replace('/["\]\s]+$/', '', trim($matches_old[5]));
                                            }
                                        }

                                        $fallback_message = isset($normalized_message)
                                            ? $normalized_message
                                            : $raw_message ?? '';
                                    @endphp
                                    @if (!empty($old_customer) && !empty($new_customer) && !empty($user))
                                        <div
                                            style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                                            <span
                                                style="background-color: #f39c12; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 13px; font-weight: 600; line-height: 1.5;">
                                                {{ $old_customer }}
                                            </span>
                                            <i class="fa fa-long-arrow-right"
                                                style="color: #00c0ef; font-size: 15px;"></i>
                                            <span
                                                style="background-color: #00a65a; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 13px; font-weight: 600; line-height: 1.5;">
                                                {{ $new_customer }}
                                            </span>
                                        </div>
                                        <div
                                            style="margin-top: 8px; padding: 8px 12px; background-color: #f8f9fa; border-left: 3px solid #3c8dbc; border-radius: 3px;">
                                            <span style="color: #495057; font-size: 13px; font-weight: 400;">
                                                <i class="fa fa-user" style="color: #3c8dbc; margin-right: 5px;"></i>
                                                Changed by: <strong
                                                    style="color: #212529; font-weight: 600;">{{ $user }}</strong>
                                            </span>
                                        </div>
                                    @else
                                        <span style="color: #495057; font-size: 13px;">{{ $fallback_message }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
