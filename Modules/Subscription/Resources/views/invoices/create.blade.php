@extends('layouts.app')

@section('title', __('subscription::lang.create_invoice'))

@section('content')
    <section class="content">
        @php
            $is_edit = !empty($invoice);
            $selected_customer = $is_edit ? $customers->firstWhere('id', $invoice->customer_id) : null;
            $selected_bank = $is_edit ? $banks->firstWhere('id', $invoice->bank_account_id) : null;
            $manual_items = $manual_items ?? collect();
            if ($manual_items->isEmpty()) {
                $manual_items = collect([(object) ['description' => '', 'cycle' => '', 'qty' => 1, 'price' => '', 'total' => 0]]);
            }
        @endphp

        <style>
            body {
                font-size: 12px;
            }

            .invoice-wrapper {
                background: #fff;
                padding: 20px;
                max-width: 920px;
                margin: auto;
                border: 1px solid #ddd;
            }

            .invoice-banner {
                padding: 20px;
                text-align: center;
                color: red;
                font-size: 16px;
                margin-bottom: 8px;
            }

            .invoice-banner img {
                max-height: 110px;
                max-width: 100%;
            }

            .divider {
                border-top: 2px solid #1aa3a3;
                margin-bottom: 10px;
            }

            .invoice-title {
                text-align: center;
                font-weight: bold;
                margin: 8px 0 12px;
            }

            .invoice-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                table-layout: fixed;
            }

            .invoice-table th {
                background: #0b1d59;
                color: #fff;
                border: 1px solid #000;
                padding: 6px;
                text-align: center;
            }

            .invoice-table td {
                border: 1px solid #000;
                padding: 4px;
                vertical-align: top;
            }

            .invoice-input {
                border: none;
                border-bottom: 1px dotted #000;
                background: transparent;
                padding: 0;
                font-size: 12px;
                min-height: 20px;
                width: 100%;
                display: inline-block;
            }

            .invoice-text {
                border-bottom: 1px dotted #000;
                padding-bottom: 2px;
                min-width: 120px;
                display: inline-block;
                width: 100%;
            }

            .invoice-textarea {
                border: 1px solid #ddd;
                min-height: 54px;
                padding: 6px;
                resize: vertical;
            }

            .select2-container {
                z-index: 9999 !important;
            }

            .select2-dropdown {
                z-index: 9999 !important;
            }

            .select2-container--default .select2-selection--single {
                border: none;
                border-bottom: 1px dotted #000;
                height: 28px;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 28px;
            }

            .total-box {
                width: 280px;
                float: right;
                margin-top: 10px;
            }

            .total-box table {
                width: 100%;
                border-collapse: collapse;
            }

            .total-box th {
                background: #0b1d59;
                color: #fff;
                border: 1px solid #000;
                padding: 6px;
                text-align: right;
            }

            .thank-you {
                font-family: Brush Script MT, Brush Script Std, cursive;
                font-size: 30px;
                color: #0b1d59;
                text-align: right;
                margin-top: 20px;
            }

            .small-text {
                font-size: 10px;
                margin-top: 10px;
            }

            .manual-section td {
                background: #f7f7f7;
                font-weight: bold;
            }

            @media print {
                @page {
                    size: A4 portrait;
                    margin: 8mm;
                }

                body {
                    margin: 0;
                }

                .no-print {
                    display: none !important;
                }

                .invoice-wrapper {
                    max-width: 100%;
                    margin: auto !important;
                    border: none !important;
                    padding: 0;
                }
            }
        </style>

        <div class="invoice-wrapper">
            <form id="invoice_form" method="POST"
                action="{{ $is_edit ? route('subscription.invoices.update', $invoice->id) : route('subscription.invoices.store') }}">
                @csrf
                @if ($is_edit)
                    @method('PUT')
                @endif

                <div class="invoice-banner">
                    @if ($banner_image)
                        <img src="{{ $banner_image }}" class="img-thumbnail">
                    @else
                        Upload Banner to show here
                    @endif
                </div>

                <div class="divider"></div>
                <div class="invoice-title">INVOICE</div>

                <div class="row invoice-header">
                    <div class="col-xs-7">
                        <p>
                            <strong>Customer Name:</strong>
                            <select name="customer_id" id="customer_id" class="invoice-input select2 form-control">
                                <option value="">Select Customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        data-code="{{ $customer->contact_id }}"
                                        data-address="{{ trim(($customer->address_line_1 ?? '') . ' ' . ($customer->landmark ?? '')) }}"
                                        {{ (string) old('customer_id', optional($invoice)->customer_id) === (string) $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </p>

                        <p>
                            <strong>Customer Code:</strong>
                            <input type="text" name="customer_code" id="customer_code_input" class="invoice-input"
                                value="{{ old('customer_code', optional($invoice)->customer_code ?? optional($selected_customer)->contact_id) }}"
                                readonly>
                        </p>

                        <p>
                            <strong>Address:</strong>
                            <textarea name="customer_address" id="customer_address_input" class="form-control invoice-textarea"
                                rows="2"
                                placeholder="Enter customer address">{{ old('customer_address', optional($invoice)->customer_address ?? trim((optional($selected_customer)->address_line_1 ?? '') . ' ' . (optional($selected_customer)->landmark ?? ''))) }}</textarea>
                        </p>

                        <p>
                            <strong>Subscription Period:</strong>
                            <input type="text" name="period_from" class="invoice-input datepicker" id="period_from"
                                value="{{ old('period_from', optional($invoice)->from_date) }}" placeholder="From">
                            to
                            <input type="text" name="period_to" class="invoice-input datepicker" id="period_to"
                                value="{{ old('period_to', optional($invoice)->to_date) }}" placeholder="To">
                        </p>
                    </div>

                    <div class="col-xs-5 text-right">
                        <p><strong>INVOICE NO:</strong> {{ $invoice_no }}</p>
                        <p><strong>Date:</strong> {{ date('Y-m-d') }}</p>
                        <input type="hidden" name="invoice_no" value="{{ $invoice_no }}">
                    </div>
                </div>

                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="38%">Description</th>
                            <th width="14%">Cycle</th>
                            <th width="10%">Qty</th>
                            <th width="15%">Price</th>
                            <th width="18%">Total</th>
                        </tr>
                    </thead>
                    <tbody id="invoice-body">
                        @foreach ($subscription_settings as $index => $setting)
                            @php
                                $selected_item = $system_items->firstWhere('setting_id', $setting->id);
                                $selected_description = old("items.$index.description", optional($selected_item)->description);
                                $selected_cycle = old("items.$index.cycle", optional($selected_item)->cycle ?? $setting->subscription_cycle);
                                $selected_qty = old("items.$index.qty", optional($selected_item)->qty ?? 1);
                                $selected_price = old("items.$index.price", optional($selected_item)->price);
                                $selected_setting_id = old("items.$index.setting_id", optional($selected_item)->setting_id);
                                $line_total = (float) $selected_qty * (float) ($selected_price ?? 0);
                            @endphp
                            <tr class="system-row">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <select name="items[{{ $index }}][description]"
                                        class="form-control subscription-setting select2">
                                        <option value="">Select Subscription</option>
                                        @foreach ($subscription_settings as $s)
                                            <option value="{{ $s->subscription_code }}"
                                                data-price="{{ $s->subscription_amount }}"
                                                data-cycle="{{ $s->subscription_cycle }}"
                                                data-setting-id="{{ $s->id }}"
                                                {{ $selected_description === $s->subscription_code ? 'selected' : '' }}>
                                                {{ $s->subscription_code }} - {{ ucfirst($s->subscription_cycle) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="items[{{ $index }}][setting_id]"
                                        class="setting-id-input"
                                        value="{{ $selected_setting_id }}">
                                </td>
                                <td>
                                    <span class="cycle-text">{{ $selected_cycle ?: '-' }}</span>
                                    <input type="hidden" name="items[{{ $index }}][cycle]" class="cycle-input"
                                        value="{{ $selected_cycle }}">
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][qty]" class="form-control qty"
                                        min="1" step="0.01" value="{{ $selected_qty }}">
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][price]" class="form-control price"
                                        step="0.01" value="{{ $selected_price }}">
                                </td>
                                <td class="text-right line-total">{{ number_format($line_total, 2, '.', '') }}</td>
                            </tr>
                        @endforeach

                        <tr class="separator-row">
                            <td colspan="6" style="height: 18px;"></td>
                        </tr>

                        <tr class="manual-section">
                            <td colspan="6">Manual Items</td>
                        </tr>

                        @foreach ($manual_items as $manual_index => $manual_item)
                            @php
                                $manual_qty = old("manual_items.$manual_index.qty", $manual_item->qty ?? 1);
                                $manual_price = old("manual_items.$manual_index.price", $manual_item->price ?? '');
                                $manual_total = (float) $manual_qty * (float) ($manual_price ?? 0);
                            @endphp
                            <tr class="manual-row">
                                <td class="row-number">{{ $manual_index + 1 }}</td>
                                <td>
                                    <input type="text" name="manual_items[{{ $manual_index }}][description]"
                                        class="form-control"
                                        value="{{ old("manual_items.$manual_index.description", $manual_item->description ?? '') }}"
                                        placeholder="Enter description manually">
                                </td>
                                <td>
                                    <input type="text" name="manual_items[{{ $manual_index }}][cycle]"
                                        class="form-control cycle-manual"
                                        value="{{ old("manual_items.$manual_index.cycle", $manual_item->cycle ?? '') }}"
                                        placeholder="Optional">
                                </td>
                                <td>
                                    <input type="number" name="manual_items[{{ $manual_index }}][qty]"
                                        class="form-control qty" min="1" step="0.01"
                                        value="{{ $manual_qty }}">
                                </td>
                                <td>
                                    <input type="number" name="manual_items[{{ $manual_index }}][price]"
                                        class="form-control price" step="0.01"
                                        value="{{ $manual_price }}">
                                </td>
                                <td class="text-right line-total">{{ number_format($manual_total, 2, '.', '') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <button type="button" id="add-manual-row" class="btn btn-sm btn-info no-print">
                    + Add Manual Row
                </button>

                <div class="total-box">
                    <table>
                        <tr>
                            <th>Total</th>
                            <th width="40%"><span id="grand_total">0.00</span></th>
                        </tr>
                    </table>
                    <input type="hidden" name="grand_total" id="grand_total_input"
                        value="{{ old('grand_total', optional($invoice)->total) }}">
                </div>

                <div style="clear:both;"></div>

                <div class="row footer-section">
                    <div class="col-xs-6">
                        <p><strong>Payment Details</strong></p>
                        <textarea name="payment_details" class="form-control" rows="2"
                            placeholder="Enter payment details (Ref no, cheque no, transaction ID, etc.)">{{ old('payment_details', optional($invoice)->payment_details) }}</textarea>
                        <p>
                            Method:
                            <select name="payment_method" class="form-control">
                                <option value="Cash" {{ old('payment_method', optional($invoice)->payment_method) === 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option value="Bank" {{ old('payment_method', optional($invoice)->payment_method) === 'Bank' ? 'selected' : '' }}>Bank</option>
                            </select>
                        </p>
                        <p>Paid Amount: __________</p>
                        <p>Balance Amount: __________</p>
                        <p>
                            <strong>Payment Terms:</strong>
                            <select name="payment_term_id" id="payment_term_id" class="form-control select2"
                                {{ $payment_terms->count() == 1 ? 'readonly disabled' : '' }}>
                                @foreach ($payment_terms as $t)
                                    <option value="{{ $t->id }}"
                                        {{ (string) old('payment_term_id', optional($invoice)->payment_term_id ?? optional($payment_terms->first())->id) === (string) $t->id ? 'selected' : '' }}>
                                        {{ $t->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($payment_terms->count() == 1)
                                <input type="hidden" name="payment_term_id" value="{{ $payment_terms->first()->id }}">
                            @endif
                        </p>
                    </div>

                    <div class="col-xs-6">
                        <p><strong>Bank Details</strong></p>
                        <select id="bank_id" name="bank_account_id" class="form-control">
                            <option value="">Select bank</option>
                            @foreach ($banks as $b)
                                <option value="{{ $b->id }}"
                                    data-ac-name="{{ $b->ac_name }}"
                                    data-ac="{{ $b->ac_no }}"
                                    data-branch="{{ $b->branch }}"
                                    {{ (string) old('bank_account_id', optional($invoice)->bank_account_id) === (string) $b->id ? 'selected' : '' }}>
                                    {{ $b->bank }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="banner_id" value="{{ $banner->id ?? '' }}">
                        <p>AC Name: <span id="bank_ac_name">{{ old('bank_ac_name', optional($selected_bank)->ac_name ?? '-') }}</span></p>
                        <p>AC No: <span id="bank_ac">{{ old('bank_ac', optional($selected_bank)->ac_no ?? '-') }}</span></p>
                        <p>Branch: <span id="bank_branch">{{ old('bank_branch', optional($selected_bank)->branch ?? '-') }}</span></p>
                        <div class="thank-you">Thank You!</div>

                        <input type="hidden" name="bank_name" id="bank_name_input"
                            value="{{ old('bank_name', optional($selected_bank)->bank) }}">
                        <input type="hidden" name="bank_ac_name" id="bank_ac_name_input"
                            value="{{ old('bank_ac_name', optional($selected_bank)->ac_name) }}">
                        <input type="hidden" name="bank_ac" id="bank_ac_input"
                            value="{{ old('bank_ac', optional($selected_bank)->ac_no) }}">
                        <input type="hidden" name="bank_branch" id="bank_branch_input"
                            value="{{ old('bank_branch', optional($selected_bank)->branch) }}">
                    </div>
                </div>

                <div class="small-text">
                    Computer Generated Invoice. Signature not required.<br>
                    * All sales are final. The amount paid amount for the computer software / service will not be refunded
                </div>

                <div class="text-center no-print" style="margin-top:10px;">
                    <button type="button" id="save_invoice_btn" class="btn btn-primary btn-sm">
                        {{ $is_edit ? 'Update' : 'Save' }}
                    </button>
                    <button type="submit" formaction="{{ route('subscription.invoices.pdf') }}" formtarget="_blank"
                        class="btn btn-success btn-sm">
                        PDF
                    </button>
                    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
                        Print
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection

@section('javascript')
    <script>
        function initSelect2() {
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('.invoice-wrapper'),
                minimumResultsForSearch: 0
            });
        }

        function updateCustomerDetails() {
            let opt = $('#customer_id').find(':selected');
            let code = opt.data('code') || '';
            let address = opt.data('address') || '';

            $('#customer_code_input').val(code);

            if (!$('#customer_address_input').val().trim() || $('#customer_id').data('autofill-address') === 1) {
                $('#customer_address_input').val(address);
            }

            $('#customer_id').data('autofill-address', 1);
        }

        function updateBankDetails() {
            let o = $('#bank_id').find(':selected');
            let acName = o.data('ac-name') || '';
            let ac = o.data('ac') || '';
            let branch = o.data('branch') || '';
            let bankName = o.text().trim();

            $('#bank_ac_name').text(acName || '-');
            $('#bank_ac').text(ac || '-');
            $('#bank_branch').text(branch || '-');

            $('#bank_name_input').val(o.val() ? bankName : '');
            $('#bank_ac_name_input').val(acName);
            $('#bank_ac_input').val(ac);
            $('#bank_branch_input').val(branch);
        }

        function calculate() {
            let total = 0;

            $('.line-total').each(function() {
                total += parseFloat($(this).text()) || 0;
            });

            $('#grand_total').text(total.toFixed(2));
            $('#grand_total_input').val(total.toFixed(2));
        }

        function updateManualRowNumbers() {
            $('.manual-row').each(function(index) {
                $(this).find('.row-number').text(index + 1);
            });
        }

        $(document).ready(function() {
            initSelect2();

            $('.datepicker').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd'
            });

            $('#customer_id').data('autofill-address', 0);
            $('#customer_id').on('change', updateCustomerDetails);
            $('#customer_address_input').on('input', function() {
                $('#customer_id').data('autofill-address', 0);
            });

            $('#bank_id').on('change', updateBankDetails);

            $(document).on('change', '.subscription-setting', function() {
                let row = $(this).closest('tr');
                let selected = $(this).find(':selected');
                let cycle = selected.data('cycle') || '-';
                let price = selected.data('price') || 0;
                let settingId = selected.data('setting-id') || '';

                row.find('.cycle-text').text(cycle);
                row.find('.cycle-input').val(cycle === '-' ? '' : cycle);
                row.find('.setting-id-input').val(settingId);
                row.find('.price').val(price).trigger('input');
            });

            $(document).on('input', '.qty, .price', function() {
                let row = $(this).closest('tr');
                let qty = parseFloat(row.find('.qty').val()) || 0;
                let price = parseFloat(row.find('.price').val()) || 0;

                row.find('.line-total').text((qty * price).toFixed(2));
                calculate();
            });

            $('#add-manual-row').click(function() {
                let manualIndex = $('.manual-row').length;
                let row = `
                    <tr class="manual-row">
                        <td class="row-number"></td>
                        <td>
                            <input type="text" name="manual_items[${manualIndex}][description]" class="form-control" placeholder="Enter description manually">
                        </td>
                        <td>
                            <input type="text" name="manual_items[${manualIndex}][cycle]" class="form-control cycle-manual" placeholder="Optional">
                        </td>
                        <td>
                            <input type="number" name="manual_items[${manualIndex}][qty]" class="form-control qty" min="1" step="0.01" value="1">
                        </td>
                        <td>
                            <input type="number" name="manual_items[${manualIndex}][price]" class="form-control price" step="0.01">
                        </td>
                        <td class="text-right line-total">0.00</td>
                    </tr>
                `;

                $('#invoice-body').append(row);
                updateManualRowNumbers();
            });

            $('#save_invoice_btn').on('click', function(e) {
                e.preventDefault();

                let form = $('#invoice_form');
                let submitBtn = $(this);
                let originalText = submitBtn.html();
                let formData = new FormData(form[0]);

                submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg || 'Invoice saved successfully');
                            window.location.href = response.redirect || "{{ route('subscription.invoices.index') }}";
                        } else {
                            toastr.error(response.msg || 'Unexpected response from server');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            $.each(xhr.responseJSON.errors, function(key, value) {
                                toastr.error(value[0]);
                            });
                        } else if (xhr.responseJSON?.msg) {
                            toastr.error(xhr.responseJSON.msg);
                        } else {
                            toastr.error('Server returned non-JSON response');
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            $('.subscription-setting').trigger('change');
            updateCustomerDetails();
            updateBankDetails();
            updateManualRowNumbers();
            calculate();
        });
    </script>
@endsection
