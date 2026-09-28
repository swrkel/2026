@php
    $customer_statement_report = $customer_statement_report ?? [];
    $colspan = 0;
@endphp

<section class="content">
    <div class="row">
        <div class="col-md-12">
            {!! Form::open([
                'method' => 'post',
                'id' => 'customer_statement_font_form',
                'url' => url('/customers/customer-statement/font-settings'),
            ]) !!}
            @csrf

            <div style="position: relative; margin-bottom: 20px;">
                <button type="submit"
                    style="
        position: absolute;
        top: 0;
        right: 0;
        background: #2874a6;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    ">
                    Save Font Settings
                </button>
            </div>


            <div id="report_div">
                <div id="print_header_div">
                    <style>
                        .font-size-control {
                            position: absolute;
                            right: -80px;
                            top: 0;
                            width: 60px;
                            height: 25px;
                            border: 1px solid #2874a6;
                            text-align: center;
                            background: white;
                            color: #333;
                        }

                        .font-control-wrapper {
                            position: relative;
                            display: inline-block;
                            margin-right: 90px;
                        }

                        .bg_color {
                            background: #8F3A84 !important;
                            font-size: 20px;
                            color: #fff !important;
                            print-color-adjust: exact;
                        }

                        .text-center {
                            text-align: center;
                        }

                        #customer_statement_table th {
                            background: #8F3A84 !important;
                            color: #fff !important;
                            print-color-adjust: exact;
                        }

                        .uppercase {
                            text-transform: uppercase;
                        }

                        table {
                            width: 100%;
                            border-collapse: collapse;
                        }

                        table,
                        th,
                        td {
                            border: 1px solid #ddd;
                        }

                        th,
                        td {
                            padding: 8px;
                            text-align: left;
                            color: black;
                        }

                        /* Fix for placeholder visibility */
                        .placeholder-text {
                            color: #333 !important;
                            font-weight: normal;
                            min-height: 20px;
                            display: inline-block;
                        }

                        /* Fix for customer info boxes alignment */
                        .customer-info-container {
                            display: flex;
                            flex-direction: column;
                            gap: 8px;
                            margin-top: 10px;
                        }

                        .customer-info-line {
                            display: block;
                            line-height: 1.4;
                        }

                        .customer-label {
                            font-weight: bold;
                            color: #8F3A84;
                            margin-right: 5px;
                        }
                    </style>

                    {{-- Simplified header with placeholders --}}
                    <table style="width: 100%">
                        <tr>
                            <td class="text-center" width="100%">
                                <p class="text-center uppercase">
                                <div class="font-control-wrapper">
                                    <strong id="statement_title" class="placeholder-text">CUSTOMER STATEMENT<br>
                                        <span id="business_name" class="placeholder-text">Business Name Example</span>
                                    </strong>
                                    <input type="number" name="statement_title_size" class="font-size-control"
                                        value="{{ $customer_statement_report['statement_title_size'] ?? 20 }}">
                                </div>
                                <br>
                                <div class="font-control-wrapper">
                                    <span id="business_address" class="placeholder-text">City, State</span>
                                    <input type="number" name="business_address_size" class="font-size-control"
                                        value="{{ $customer_statement_report['business_address_size'] ?? 16 }}">
                                </div>
                                <br>
                                <div class="font-control-wrapper">
                                    <span id="business_mobile" class="placeholder-text">+1234567890</span>
                                    <input type="number" name="business_mobile_size" class="font-size-control"
                                        value="{{ $customer_statement_report['business_mobile_size'] ?? 16 }}">
                                </div>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center" colspan="2">
                                <div class="font-control-wrapper">
                                    <p class="text-center" style="color: #8F3A84 !important;print-color-adjust: exact;">
                                        <strong id="date_range" class="placeholder-text">Date Range From 01 Jan 2024 to
                                            31 Dec 2024</strong>
                                    </p>
                                    <input type="number" name="date_range_size" class="font-size-control"
                                        value="{{ $customer_statement_report['date_range_size'] ?? 16 }}">
                                </div>
                            </td>
                        </tr>
                    </table>

                    <table style="width: 100%">
                        <tr>
                            <td>
                                <div style="float: left">
                                    <h4 class="modal-title" id="modalTitle">
                                        <div class="font-control-wrapper">
                                            <b id="invoice_no_label" class="placeholder-text">Invoice No:</b>
                                            <span class="placeholder-text">STM-001</span>
                                            <input type="number" name="invoice_no_size" class="font-size-control"
                                                value="{{ $customer_statement_report['invoice_no_size'] ?? 16 }}">
                                        </div>
                                    </h4>

                                    <div class="customer-info-container">
                                        <div class="font-control-wrapper">
                                            <p class="bg_color"
                                                style="width: fit-content; padding: 5px 10px; margin-bottom: 10px;"
                                                id="customer_label" class="placeholder-text">Customer:</p>
                                            <input type="number" name="customer_label_size" class="font-size-control"
                                                value="{{ $customer_statement_report['customer_label_size'] ?? 16 }}">
                                        </div>

                                        <div class="font-control-wrapper">
                                            <p class="customer-info-line">
                                                <strong id="customer_name" class="placeholder-text">Customer Name
                                                    Example</strong>
                                            </p>
                                            <input type="number" name="customer_name_size" class="font-size-control"
                                                value="{{ $customer_statement_report['customer_name_size'] ?? 16 }}">
                                        </div>

                                        <div class="font-control-wrapper">
                                            <span class="customer-info-line" id="customer_address"
                                                class="placeholder-text">Customer Address Example</span>
                                            <input type="number" name="customer_address_size" class="font-size-control"
                                                value="{{ $customer_statement_report['customer_address_size'] ?? 14 }}">
                                        </div>

                                        <div class="font-control-wrapper">
                                            <span class="customer-info-line">
                                                <span class="customer-label">Email:</span>
                                                <span id="customer_email"
                                                    class="placeholder-text">customer@example.com</span>
                                            </span>
                                            <input type="number" name="customer_email_size" class="font-size-control"
                                                value="{{ $customer_statement_report['customer_email_size'] ?? 14 }}">
                                        </div>

                                        <div class="font-control-wrapper">
                                            <span class="customer-info-line">
                                                <span class="customer-label">Mobile:</span>
                                                <span id="customer_mobile" class="placeholder-text">+1234567890</span>
                                            </span>
                                            <input type="number" name="customer_mobile_size" class="font-size-control"
                                                value="{{ $customer_statement_report['customer_mobile_size'] ?? 14 }}">
                                        </div>

                                        <div class="font-control-wrapper">
                                            <span class="customer-info-line">
                                                <span class="customer-label">Tax No:</span>
                                                <span id="customer_tax" class="placeholder-text">TX123456</span>
                                            </span>
                                            <input type="number" name="customer_tax_size" class="font-size-control"
                                                value="{{ $customer_statement_report['customer_tax_size'] ?? 14 }}">
                                        </div>

                                        <div class="font-control-wrapper">
                                            <span class="customer-info-line">
                                                <strong class="customer-label">Printed On:</strong>
                                                <span id="printed_on_value" class="placeholder-text">01 Jan 2024
                                                    12:00</span>
                                            </span>
                                            <input type="number" name="printed_on_size" class="font-size-control"
                                                value="{{ $customer_statement_report['printed_on_size'] ?? 14 }}">
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td
                                style="text-align: right; font-size: 20px; color: #FF0000 !important;print-color-adjust: exact;">
                                <div class="font-control-wrapper">
                                    <span id="copy_label" class="placeholder-text">Copy - 1</span>
                                    <input type="number" name="copy_label_size" class="font-size-control"
                                        value="{{ $customer_statement_report['copy_label_size'] ?? 20 }}">
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <table class="table table-bordered table-striped" id="customer_statement_table">
                            <thead>
                                <tr>
                                    @php
                                        $columns = [
                                            'date' => 'Date',
                                            'location' => 'Location',
                                            'invoice_no' => 'Invoice No',
                                            'route' => 'Route',
                                            'vehicle' => 'Vehicle',
                                            'customer_reference' => 'Customer Reference',
                                            'customer_po' => 'Customer PO No',
                                            'voucher_date' => 'Voucher Order Date',
                                            'product' => 'Product',
                                            'qty' => 'Qty',
                                            'unit_price' => 'Unit Price',
                                            'invoice_amount' => 'Invoice Amount',
                                            'due_amount' => 'Due Amount',
                                        ];
                                        $colspan = count($columns);
                                    @endphp

                                    @foreach ($columns as $key => $label)
                                        <th>
                                            <div class="font-control-wrapper">
                                                <span id="{{ $key }}_header"
                                                    class="placeholder-text">{{ $label }}</span>
                                                <input type="number" name="{{ $key }}_size"
                                                    class="font-size-control"
                                                    value="{{ $customer_statement_report[$key . '_size'] ?? 14 }}">
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                <tr>
                                    <td colspan="{{ $colspan - 1 }}">
                                        <div class="font-control-wrapper">
                                            <span id="beginning_balance_label" class="placeholder-text">Beginning
                                                Balance</span>
                                            <input type="number" name="beginning_balance_size"
                                                class="font-size-control"
                                                value="{{ $customer_statement_report['beginning_balance_size'] ?? 14 }}">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-control-wrapper">
                                            <span id="beginning_balance_value"
                                                class="placeholder-text">1,000.00</span>
                                            <input type="number" name="beginning_balance_value_size"
                                                class="font-size-control"
                                                value="{{ $customer_statement_report['beginning_balance_value_size'] ?? 14 }}">
                                        </div>
                                    </td>
                                </tr>

                                {{-- Sample table rows with placeholder data --}}
                                @for ($i = 1; $i <= 1; $i++)
                                    <tr>
                                        @foreach ($columns as $key => $label)
                                            <td>
                                                <div class="font-control-wrapper">
                                                    <span class="table_data placeholder-text">
                                                        @if ($key == 'date')
                                                            2024-01-{{ $i }}
                                                        @elseif($key == 'invoice_amount' || $key == 'due_amount')
                                                            {{ number_format($i * 100, 2) }}
                                                        @elseif($key == 'qty')
                                                            {{ $i }}
                                                        @elseif($key == 'unit_price')
                                                            50.00
                                                        @else
                                                            Sample {{ $label }} {{ $i }}
                                                        @endif
                                                    </span>
                                                    <input type="number" name="table_data_size"
                                                        class="font-size-control"
                                                        value="{{ $customer_statement_report['table_data_size'] ?? 12 }}">
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endfor

                                <tr>
                                    <th colspan="{{ $colspan - 2 }}"></th>
                                    <th>
                                        <div class="font-control-wrapper">
                                            <span id="balance_label" class="placeholder-text">Balance</span>
                                            <input type="number" name="balance_label_size" class="font-size-control"
                                                value="{{ $customer_statement_report['balance_label_size'] ?? 14 }}">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="font-control-wrapper">
                                            <span id="balance_value" class="placeholder-text">1,500.00</span>
                                            <input type="number" name="balance_value_size" class="font-size-control"
                                                value="{{ $customer_statement_report['balance_value_size'] ?? 14 }}">
                                        </div>
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <hr>

                <div class="col-xs-12 text-center">
                    <div class="font-control-wrapper">
                        <p id="statement_note" class="placeholder-text">Thank you for your business! This is a sample
                            statement note.</p>
                        <input type="number" name="statement_note_size" class="font-size-control"
                            value="{{ $customer_statement_report['statement_note_size'] ?? 14 }}">
                    </div>
                </div>

                <div class="row" style="height: 100px !important;"></div>

                <table width="100%">
                    <tr>
                        <th class="width-50">
                            <div class="font-control-wrapper">
                                <strong id="signature_label" class="placeholder-text">Signature
                                    :...............................................</strong>
                                <input type="number" name="signature_size" class="font-size-control"
                                    value="{{ $customer_statement_report['signature_size'] ?? 14 }}">
                            </div>
                        </th>
                        <th class="width-50">
                            <div class="font-control-wrapper">
                                <strong id="total_label" class="placeholder-text">Total: 1,500.00</strong>
                                <input type="number" name="total_size" class="font-size-control"
                                    value="{{ $customer_statement_report['total_size'] ?? 14 }}">
                            </div>
                        </th>
                    </tr>
                </table>

                <div class="row" style="height: 100px !important;"></div>
            </div>
        </div>
        {!! Form::close() !!}
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        function updateFontSize(element, size) {
            if (!element) return;
            element.style.fontSize = size + 'px';
            element.style.color = '#333';
        }

        const fontControls = document.querySelectorAll('.font-size-control');

        fontControls.forEach(control => {
            control.addEventListener('input', function() {
                const wrapper = this.parentElement;

                // Try to find the element with an ID inside the wrapper
                let target = wrapper.querySelector('[id]');

                // If no ID, fallback to table_data inside wrapper (for table rows)
                if (!target) {
                    target = wrapper.querySelector('.table_data');
                }

                // Apply font size
                if (target) {
                    if (target.classList.contains('table_data')) {
                        wrapper.querySelectorAll('.table_data').forEach(el => {
                            updateFontSize(el, this.value);
                        });
                    } else {
                        updateFontSize(target, this.value);
                    }
                }
            });

            // Initialize font sizes on page load
            const wrapper = control.parentElement;
            let target = wrapper.querySelector('[id]') || wrapper.querySelector('.table_data');

            if (target) {
                if (target.classList.contains('table_data')) {
                    wrapper.querySelectorAll('.table_data').forEach(el => {
                        updateFontSize(el, control.value);
                    });
                } else {
                    updateFontSize(target, control.value);
                }
            }
        });
    });
</script>
