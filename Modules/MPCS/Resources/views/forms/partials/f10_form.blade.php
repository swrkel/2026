<style>
    #form_f10_print_area {
        font-family: "Calibri", sans-serif;
        font-size: 12px;
        position: relative;
    }

    #form_f10_print_area table {
        border-collapse: collapse !important;
        width: 100%;
    }

    #form_f10_print_area .table {
        border: none;
    }

    #form_f10_print_area table.table-bordered {
        border: 1.5px solid #000 !important;
    }

    #form_f10_print_area .table-bordered th,
    #form_f10_print_area .table-bordered td {
        border: 0.5px solid #000 !important;
    }

    .compact-dropdown .form-control,
    .compact-dropdown .select2-selection {
        height: 27px !important;
        min-height: 27px !important;
        padding: 3px 8px !important;
        font-size: 12px;
    }

    .compact-dropdown {
        width: 70%;
    }

    .payment_input {
        border: none;
        width: 100%;
        text-align: right;
    }

    .main-footer {
        display: none !important;
    }

    .only-print {
        display: none;
    }


    /* IS1634-014: reduce F10 receipt header field widths */
    #form_f10_print_area .f10-header-row {
        display:flex;
        align-items:center;
        flex-wrap:wrap;
        margin-bottom:10px;
    }
    #form_f10_print_area .f10-header-left {
        width:42%;
        max-width:42%;
        flex:0 0 42%;
        font-size:14px;
    }
    #form_f10_print_area .f10-header-right {
        width:35%;
        max-width:35%;
        flex:0 0 35%;
        margin-left:auto;
        font-size:14px;
        text-align:left;
    }
    #form_f10_print_area .f10-header-label {
        display:inline-block;
        width:115px;
        white-space:nowrap;
    }
    #form_f10_print_area .f10-header-input {
        border:none;
        background:transparent;
        width:190px !important;
        max-width:190px !important;
    }
    /*
     * IS2015: centre the F 10 Number and Document No values.
     *
     * Both sit in a 190px field but rendered hard against its left edge, which
     * is what the arrows in the ticket point at. Applied as its own class rather
     * than to .f10-header-input so the Date & Time field beside them keeps its
     * existing alignment.
     */
    #form_f10_print_area .f10-header-input.f10-header-centered {
        text-align: center;
    }
    #form_f10_print_area .f10-short-input {
        border:none;
        background:transparent;
        width:140px !important;
        max-width:140px !important;
        outline:none;
    }
    #form_f10_print_area .f10-for-input {
        border:none;
        background:transparent;
        width:120px !important;
        max-width:120px !important;
        outline:none;
    }

    /* IS1627 F10 compact receipt */
    .f10-receipt-table th:first-child, .f10-receipt-table td:first-child { width: 55%; }
    .f10-receipt-table th:nth-child(2), .f10-receipt-table td:nth-child(2) { width: 20%; }
    .f10-receipt-table th:nth-child(3), .f10-receipt-table td:nth-child(3) { width: 25%; }
    @media print {
        .no-print, .main-header, .main-sidebar, .main-footer, .breadcrumb, .print-signatures { display:none !important; }
        section.content, #form_f10_print_area { margin:0 !important; padding:0 !important; }
        #form_f10_print_area { position:absolute !important; top:0 !important; left:0 !important; width:100% !important; }
    }
</style>

@php
    $can_cash_given = auth()->check() && auth()->user()->can('f10_form_cash_given_button');
    $can_received_amount = auth()->check() && auth()->user()->can('f10_form_cash_received_button');
@endphp


<section class="content" style="padding:10px; background-color:#fff; position:relative;">

    <div class="row no-print justify-content-between align-items-center" style="margin-bottom:15px; position:relative;">

        <div class="col text-center">
            <div class="form-group compact-dropdown"
                style="margin-bottom:0; display:inline-block; width:auto; min-width:250px;">
                {!! Form::label('f10_location_id', __('purchase.business_location') . ':', [
                    'style' => 'display:inline-block; margin-right:5px;',
                ]) !!}
                {!! Form::select(
                    'f10_location_id',
                    $business_locations,
                    count($business_locations) == 1 ? $business_locations->keys()->first() : null,
                    [
                        'id' => 'f10_location_id',
                        'class' => 'form-control select2',
                        'style' => 'display:inline-block; width:auto; min-width:200px;',
                    ],
                ) !!}
            </div>
        </div>

        <div class="col-auto" style="position:absolute; right:0; top:50%; transform:translateY(-50%);">
            <button class="btn btn-info print_report" id="print_div"
                style="background-color:#00a2e8;border:none;margin-right:5px;" disabled>
                Save & Print
            </button>

            <button class="btn btn-danger" style="background-color:#ed1c24;border:none;">
                Close
            </button>
        </div>

    </div>

    <div id="form_f10_print_area" style="padding:0;margin:0 auto;">

        <div class="text-center" style="margin-bottom:25px; position:relative;">

            <h4 class="only-print" id="print_location_name" style="display:none;margin:0;font-weight:bold;">
            </h4>

            <h3 style="margin:5px 0;font-weight:bold;">
                Cash Receipt
            </h3>
            <div style="position:absolute; right:20px; top:-10px;
            font-size:24px; font-weight:bold;">
                F10
            </div>

        </div>

        <div class="f10-header-row">
            <div class="f10-header-left" style="display: flex; align-items: center; margin-bottom: 8px;">
                <label class="f10-header-label" style="margin-right: 8px; margin-bottom: 0;">Manager Name:</label>
                <select id="manager_dropdown" class="form-control select2 no-print"
                    style="width: 190px; max-width: 190px; display: inline-block;">
                    <option value="">Select Manager</option>
                    @php
                        $activeManagers = $managers->where('status', 'Active');
                        $singleManager = $activeManagers->count() === 1 ? $activeManagers->first() : null;
                    @endphp
                    @foreach ($activeManagers as $manager)
                        <option value="{{ $manager->id }}"
                            {{ $singleManager && $singleManager->id == $manager->id ? 'selected' : '' }}>
                            {{ $manager->manager_name }}
                        </option>
                    @endforeach
                </select>
                <span id="manager_display_text" class="only-print" style="margin-left: 5px; font-weight: normal;"></span>
            </div>
        </div>

        <div class="f10-header-row">
            <div class="f10-header-left">
                <label class="f10-header-label">F 10 Number:</label>
                {{-- IS2015: f10-header-centered centres the value in its field. --}}
                <input type="text" class="f10-header-input f10-header-centered" value="{{ $current_f10_number ?? ($opening_numbers ? $opening_numbers->f10_number : '') }}" readonly>
            </div>
        </div>

        <div class="f10-header-row" style="margin-bottom: 18px;">
            <div class="f10-header-left">
                <label class="f10-header-label">Document No:</label>
                {{-- IS2015: centred to match the F 10 Number field above. --}}
                <input type="text" class="f10-header-input f10-header-centered" value="{{ $opening_numbers ? $opening_numbers->document_no : '' }}" readonly>
            </div>
            <div class="f10-header-right">
                <label style="white-space:nowrap; margin-right:8px;">Date &amp; Time:</label>
                {{--
                    IS2015: this was a readonly text box fixed at now(), so the
                    receipt could only ever be dated the moment it was saved -
                    there was no way to enter one for an earlier day.

                    It is now a real picker. id/name added so the value is posted;
                    storeReceipt() reads it and falls back to now() when absent,
                    which keeps existing behaviour if the field is ever empty.
                --}}
                {{-- IS2026: f10-header-centered added. The IS2015 work centred the
                     F 10 Number and Document No fields but not this one, so the
                     date still sat hard against the left edge of its box. --}}
                {{--
                    IS2037 (third report): this is now a NATIVE datetime-local
                    input.

                    The two previous attempts attached a JavaScript picker -
                    first datetimepicker with a daterangepicker fallback, then
                    bootstrap-datepicker as well. Each time the issue came back,
                    which says the plugins are simply not available on this page,
                    so nothing ever attached and the field stayed a plain text
                    box.

                    A native datetime-local input needs no plugin: every current
                    browser renders its own calendar and clock. It cannot fail
                    for a missing dependency, which is the whole point after
                    three attempts.

                    The value is rendered in the format the control requires
                    (Y-m-d\TH:i). storeReceipt() already parses that via its
                    Carbon fallback, and the explicit format below makes it
                    exact rather than incidental.
                --}}
                <input type="datetime-local" id="f10_form_date" name="f10_form_date"
                       class="f10-header-input f10-header-centered"
                       value="{{ now()->format('Y-m-d\TH:i') }}"
                       autocomplete="off">
            </div>
        </div>

        <div
            style="font-size:16px; line-height:2.5; margin-bottom:30px; display:flex; flex-wrap:wrap; align-items:center;">

            <span style="margin-right:8px;"> <strong>Received</strong></span>

            <span id="received_amount_display" style="white-space:nowrap; margin-right:5px;">
                {{ $opening_numbers ? $opening_numbers->currency_prefix : '' }}
            </span>

            {{-- MA-002 (IS-1915): these three were readonly INPUTS, so the line read
                 as a row of boxes rather than a sentence. They are now plain
                 underlined spans. Nothing was ever typed into them - all three are
                 filled by script - so removing the input has no effect on entry. --}}
            <span id="amount_in_words" class="f10-fill f10-fill-wide"></span>

            <span style="margin-right:8px;"><strong>from</strong></span>

            <span style="margin-right:2px;"><strong>Mr./ Mrs. / Miss</strong></span>

            {{-- MA-002 (IS-1915): pre-filled with the signed-in user. Choosing a
                 manager from the dropdown still overwrites it. --}}
            <span id="display_manager_name" class="f10-fill f10-fill-name">{{ $loggedInUserName ?? '' }}</span>

            <span style="margin-right:8px;"><strong>for</strong></span>

            <span id="selected_location_display" class="f10-fill f10-fill-for"></span>
        </div>

        <div class="row">
            <div class="col-md-offset-3 col-md-6">
                <table class="table f10-receipt-table no-border">
                    <tr>
                        <td style="width: 50%; font-size: 16px;">1. Cash</td>
                        <td><input type="text" name="cash" class="payment" value="0.00" data-raw-value="0.00"
                                style="border: none; border-bottom: 1px dashed #000; width: 100%; text-align: right;">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 16px;">2. Bank / ATM Deposits</td>
                        <td><input type="text" name="bank_transfer" class="payment" value="0.00"
                                data-raw-value="0.00"
                                style="border: none; border-bottom: 1px dashed #000; width: 100%; text-align: right;">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 16px;">3. Cheques</td>
                        <td><input type="text" name="cheque" class="payment" value="0.00" data-raw-value="0.00"
                                style="border: none; border-bottom: 1px dashed #000; width: 100%; text-align: right;">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 16px;">4. Credit Voucher</td>
                        <td><input type="text" name="card" class="payment" value="0.00" data-raw-value="0.00"
                                style="border: none; border-bottom: 1px dashed #000; width: 100%; text-align: right;">
                        </td>
                    </tr>
                    <tr style="font-weight: bold;">
                        <td style="font-size: 18px; color: red;">Total</td>
                        <td style="position: relative;">
                            <input type="text" id="total_amount" readonly
                                style="border: none; border-bottom: 2px solid #000; width: 100%; font-weight: bold; background: transparent; text-align: right;">
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="row no-print" style="margin-top: 25px; margin-bottom: 10px;">
            <div class="col-md-6 text-center">
                <div id="cash_given_info"
                    style="display: none; min-height: 58px; padding: 8px; background-color: #f0f8ff; border: 1px solid #cce5ff; border-radius: 4px;">
                    <p style="margin: 0; font-size: 14px; color: #0066cc;">
                        <strong>Cash Given By:</strong><br>
                        <span id="cash_given_user"></span>
                    </p>
                </div>
            </div>
            <div class="col-md-6 text-center">
                <div id="received_amount_info"
                    style="display: none; min-height: 58px; padding: 8px; background-color: #f0fff0; border: 1px solid #c3e6cb; border-radius: 4px;">
                    <p style="margin: 0; font-size: 14px; color: #155724;">
                        <strong>Amount Received By:</strong><br>
                        <span id="received_amount_user"></span>
                    </p>
                </div>
            </div>
        </div>
        <div class="row print-signatures" style="margin-top: 60px;">
            <div class="col-md-6 text-center">
                <input type="text" id="manager_signature" 
                    style="border: none; border-bottom: 1px dashed #000; width: 80%; margin: 0 auto; display: block; text-align: center; background: transparent;">
                <p style="font-weight: bold;">Signature of the Manager</p>
                
                <!-- Cash Given Button Section -->
                <div class="cash-given-section no-print" style="margin-top: 15px;">
                    <button type="button" id="cash_given_btn" class="btn btn-warning btn-sm"
                            style="background-color: #ff9800; border-color: #ff9800;"
                            {{ $can_cash_given ? '' : 'disabled' }}
                            title="{{ $can_cash_given ? '' : 'You do not have permission to use this button.' }}">
                        <i class="fa fa-money"></i> Cash Given
                    </button>
                </div>
            </div>
            
            <div class="col-md-6 text-center">
                <input type="text" id="cashier_signature" 
                    style="border: none; border-bottom: 1px dashed #000; width: 80%; margin: 0 auto; display: block; text-align: center; background: transparent;">
                <p style="font-weight: bold;">Signature of the Cashier</p>
                
                <!-- Received the Amount Button Section -->
                <div class="received-amount-section no-print" style="margin-top: 15px;">
                    <button type="button" id="received_amount_btn" class="btn btn-success btn-sm"
                            style="background-color: #28a745; border-color: #28a745;"
                            {{ $can_received_amount ? '' : 'disabled' }}
                            title="{{ $can_received_amount ? '' : 'You do not have permission to use this button.' }}">
                        <i class="fa fa-check-circle"></i> Received the Amount
                    </button>
                </div>
                
                @if (auth()->check())
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-12 text-center">
                            <p style="font-size: 16px;">{{ auth()->user()->username }} -
                                {{ auth()->user()->employee?->designation?->name ?? (auth()->user()->designation ?? 'N/A') }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @php
        $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
    @endphp

    @if (!empty($reports_footer) && !empty($reports_footer->value))
        <style>
            #f10_page_footer {
                margin-top: 30px;
                width: 100%;
                text-align: left;
                font-size: 12px;
                color: #333;
                padding: 10px 0 0 10px;
                border-top: 1px solid #eee;
            }

            @media print {
                #f10_page_footer {
                    bottom: 0;
                    left: 0;
                    right: 0;
                    margin-top: 0;
                    page-break-inside: avoid;
                }
            }
        
    /* IS1627 F10 compact receipt */
    .f10-receipt-table th:first-child, .f10-receipt-table td:first-child { width: 55%; }
    .f10-receipt-table th:nth-child(2), .f10-receipt-table td:nth-child(2) { width: 20%; }
    .f10-receipt-table th:nth-child(3), .f10-receipt-table td:nth-child(3) { width: 25%; }
    @media print {
        .no-print, .main-header, .main-sidebar, .main-footer, .breadcrumb { display:none !important; }
        section.content, #form_f10_print_area { margin:0 !important; padding:0 !important; }
        #form_f10_print_area { position:absolute !important; top:0 !important; left:0 !important; width:100% !important; }
    }
</style>
        <div id="f10_page_footer">
            {!! $reports_footer->value !!}
        </div>
    @endif

</section>

<style>
    @media print {

        @page {
            size: A5 portrait;
            margin: 5px;
        }

        #form_f10_print_area {
            width: 100%;
            page-break-after: avoid;
        }

        .no-print {
            display: none !important;
        }

        .only-print {
            display: inline !important;
        }

        /* Hide dropdowns in print mode */
        #manager_dropdown,
        #f10_location_id {
            display: none !important;
        }

        /* Show display values in print mode */
        /* MA-002 (IS-1915): the three filled values read as part of the
           sentence - underlined like a written form, no box. */
        .f10-fill {
            display: inline-block;
            border-bottom: 1px solid #333;
            min-height: 22px;
            line-height: 22px;
            padding: 0 6px;
            margin-right: 10px;
            font-weight: 600;
        }
        .f10-fill-wide { flex: 1 1 300px; min-width: 250px; margin-right: 20px; }
        .f10-fill-name { min-width: 180px; }
        .f10-fill-for  { min-width: 200px; }

        #display_manager_name,
        #selected_location_display {
            display: inline !important;
        }

        /* Hide table backgrounds in print mode */
        .table,
        table {
            background-color: transparent !important;
            background: none !important;
        }

        .table td,
        table td {
            background-color: transparent !important;
            background: none !important;
        }

            .table td,
            table td {
                background-color: transparent !important;
                background: none !important;
            }

        }
    
    /* IS1627 F10 compact receipt */
    .f10-receipt-table th:first-child, .f10-receipt-table td:first-child { width: 55%; }
    .f10-receipt-table th:nth-child(2), .f10-receipt-table td:nth-child(2) { width: 20%; }
    .f10-receipt-table th:nth-child(3), .f10-receipt-table td:nth-child(3) { width: 25%; }
    @media print {
        .no-print, .main-header, .main-sidebar, .main-footer, .breadcrumb { display:none !important; }
        section.content, #form_f10_print_area { margin:0 !important; padding:0 !important; }
        #form_f10_print_area { position:absolute !important; top:0 !important; left:0 !important; width:100% !important; }
    }
</style>
     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    // Global state variables
    let cashGivenClicked = false;
    let receivedAmountClicked = false;
    let currentUserInfo = {
        name: '',
        designation: ''
    };

    function numberToWords(num) {
        if (num === 0 || isNaN(num)) return "Zero";

        var ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen'
        ];

        var tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty',
            'Seventy', 'Eighty', 'Ninety'
        ];

        function convertLessThanThousand(n) {
            if (n === 0) return '';
            if (n < 20) return ones[n];
            if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + ones[n % 10] : '');
            return ones[Math.floor(n / 100)] + ' Hundred' +
                (n % 100 ? ' ' + convertLessThanThousand(n % 100) : '');
        }

        function convertNumber(n) {
            var word = '';

            if (n >= 1000000000) {
                word += convertLessThanThousand(Math.floor(n / 1000000000)) + ' Billion ';
                n %= 1000000000;
            }

            if (n >= 1000000) {
                word += convertLessThanThousand(Math.floor(n / 1000000)) + ' Million ';
                n %= 1000000;
            }

            if (n >= 1000) {
                word += convertLessThanThousand(Math.floor(n / 1000)) + ' Thousand ';
                n %= 1000;
            }

            if (n > 0) {
                word += convertLessThanThousand(n);
            }

            return word.trim();
        }

        var parts = num.toString().split('.');
        var whole = parseInt(parts[0]);
        var cents = parts[1] ? parseInt(parts[1].substring(0, 2)) : 0;

        var words = convertNumber(whole);

        if (cents > 0) {
            words += ' and ' + convertNumber(cents) + ' Cents';
        }

        return words;
    }

    function initializeForm() {
        setupAmountFormatting();
        initializeUser();
        setupButtonHandlers();
        initializeFormState();


        if (typeof $.fn.select2 !== 'undefined') {
            $('#manager_dropdown, #f10_location_id').select2();

            var selectedValue = $('#f10_location_id').val();
            $('#f10_location_id option').each(function() {
                var text = $(this).text();
                var stripped = text.replace(/\s*\([^)]*\)$/, '');
                $(this).text(stripped);
            });
            $('#f10_location_id').select2('destroy').select2();
            if (selectedValue) {
                $('#f10_location_id').val(selectedValue).trigger('change');
            }
        }

        var managerSelect = $('#manager_dropdown');
        var managerOptions = managerSelect.find('option:not([value=""])');
        if (managerOptions.length === 1) {
            var singleManagerValue = managerOptions.first().val();
            managerSelect.val(singleManagerValue).trigger('change');
            var managerName = managerOptions.first().text();
            $('#display_manager_name').text(managerName);
        }

        function updateLocationDisplay() {
            var locationSelect = $('#f10_location_id');
            var selectedText = locationSelect.find('option:selected').text();

            if (!selectedText || selectedText.trim() === '') {
                selectedText = locationSelect.find('option').first().text();
            }

            // Clean location text by removing any content in parentheses
            selectedText = selectedText.replace(/\s*\([^)]*\)$/, '');
            
            $('#selected_location_display').text(selectedText);
            $('#print_location_name').text(selectedText);
            console.log('Location updated to:', selectedText);
        }

        var selectedManager = managerSelect.val();
        if (selectedManager) {
            var managerName = managerSelect.find('option:selected').text();
            $('#display_manager_name').text(managerName);
            $('#manager_display_text').text(managerName);
        }

        function calculateTotal() {
            var total = 0;
            $('.payment').each(function() {
                var val = parseFloat($(this).val());
                if (!isNaN(val)) {
                    total += val;
                }
            });

            $('#total_amount').val(total.toFixed(2));

            if (total > 0) {
                $('#amount_in_words').text(numberToWords(parseFloat(total)) + ' Only');
            } else {
                $('#amount_in_words').text('');
            }
            return total;
        }

        managerSelect.on('change', function() {
            var managerName = $(this).find('option:selected').text();
            // Clean manager name by removing any extra whitespace
            managerName = managerName.trim();
            $('#display_manager_name').text(managerName || '');
            $('#manager_display_text').text(managerName || '');
            console.log('Manager changed to:', managerName);
        });

        $('#f10_location_id').on('change', function() {
            updateLocationDisplay();
            console.log('Location changed');
        });

        $('.payment').on('keyup change', function() {
            calculateTotal();
        });

        updateLocationDisplay();
        calculateTotal();

        function adjustInputWidth(input) {
            if (input.value.length > 0) {
                input.style.width = ((input.value.length + 1) * 12) + 'px';
            } else {
                input.style.width = '150px';
            }
        }

        $('#amount_in_words, #display_manager_name, #selected_location_display').each(function() {
            adjustInputWidth(this);
        });
    }

    function initializeUser() {
        // Get current logged-in user information
        @if (auth()->check())
            currentUserInfo = {
                name: '{{ auth()->user()->username }}',
                designation: '{{ auth()->user()->employee?->designation?->name ?? (auth()->user()->designation ?? "N/A") }}'
            };
        @endif
    }

    function setupButtonHandlers() {
        // Cash Given button handler
        $('#cash_given_btn').on('click', function() {
            if (validateCashGivenPermission()) {
                handleCashGiven();
            }
        });

        // Received the Amount button handler
        $('#received_amount_btn').on('click', function() {
            if (validateReceivedAmountPermission()) {
                handleReceivedAmount();
            }
        });
    }

    function initializeFormState() {
        // Initially disable the Save & Print button
        $('#print_div').prop('disabled', true);
        
        // Initially enable amount fields
        $('.payment').prop('readonly', false);
    }

    function validateCashGivenPermission() {
        if (!@json($can_cash_given)) {
            showNotification('You do not have permission to mark cash as given.', 'warning');
            return false;
        }

        return true;
    }

    function validateReceivedAmountPermission() {
        if (!@json($can_received_amount)) {
            showNotification('You do not have permission to mark amount as received.', 'warning');
            return false;
        }

        return true;
    }

    function handleCashGiven() {
        cashGivenClicked = true;
        
        // Show user information
        $('#cash_given_user').text(currentUserInfo.name + ' - ' + currentUserInfo.designation);
        $('#cash_given_info').show();
        
        // Disable the Cash Given button
        $('#cash_given_btn').prop('disabled', true);
        
        // Lock all amount fields
        $('.payment').prop('readonly', true);
        
        // Show success message
        showNotification('Cash has been marked as given by ' + currentUserInfo.name, 'success');
    }

    function handleReceivedAmount() {
        if (!cashGivenClicked) {
            showNotification('Please click "Cash Given" first before marking amount as received', 'warning');
            return;
        }
        
        receivedAmountClicked = true;
        
        // Show user information
        $('#received_amount_user').text(currentUserInfo.name + ' - ' + currentUserInfo.designation);
        $('#received_amount_info').show();
        
        // Disable the Received the Amount button
        $('#received_amount_btn').prop('disabled', true);
        
        // Enable the Save & Print button
        $('#print_div').prop('disabled', false);
        
        // Show success message
        showNotification('Amount has been marked as received by ' + currentUserInfo.name, 'success');
    }

    function showNotification(message, type) {
        // Simple notification system - can be replaced with toastr or similar
        const notification = $('<div class="alert alert-' + type + ' alert-dismissible" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">' +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
            message +
            '</div>');
        
        $('body').append(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(function() {
            notification.fadeOut(function() {
                notification.remove();
            });
        }, 3000);
    }

    $(document).ready(function() {
        initializeForm();

        /*
         * IS2037: the JavaScript picker chain was REMOVED.
         *
         * It tried datetimepicker, then daterangepicker, then
         * bootstrap-datepicker, then a native fallback. None of the plugins is
         * available on this page, so the chain fell through every time and the
         * field stayed a plain text box - reported three times.
         *
         * The field is now type="datetime-local" in the markup, so the browser
         * supplies the picker itself. Attaching a plugin on top of a native
         * date-time control would fight it, so nothing is attached here.
         */
        
        // Fetch current F10 number on page load
        $.ajax({
            url: '/mpcs/F10/get-current-f10-number',
            method: 'GET',
            success: function(result) {
                if (result.success && result.current_f10_number) {
                    $('input[value="{{ $current_f10_number ?? ($opening_numbers ? $opening_numbers->f10_number : '') }}"]')
                        .val(result.current_f10_number);
                }
            }
        });
    });

    setTimeout(function() {
        initializeForm();
    }, 500);

    function formatAmount(value) {
        if (value === '' || value === null || isNaN(parseFloat(value))) {
            return '0.00';
        }
        var num = parseFloat(value);
        return num.toFixed(2);
    }

    function setupAmountFormatting() {
        $('.payment').each(function() {
            var $this = $(this);
            var initialVal = $this.val() || '0.00';
            $this.data('raw-value', parseFloat(initialVal) || 0);
            $this.val(formatNumberWithCommas(initialVal));
            $this.on('focus', function() {
                var currentVal = $(this).val();
                var rawVal = parseAmount(currentVal);
                if (rawVal.toString().endsWith('.00') || rawVal % 1 === 0) {
                    $(this).val(parseInt(rawVal));
                } else {
                    $(this).val(rawVal.toFixed(2));
                }
            });

            $this.on('blur', function() {
                var val = $(this).val();
                var formattedVal = formatNumberWithCommas(val);
                $(this).val(formattedVal);
                $(this).data('raw-value', parseAmount(val));

                calculateTotal();
            });

            $this.on('keypress', function(e) {
                var charCode = (e.which) ? e.which : e.keyCode;
                var val = $(this).val();
                if (charCode !== 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
                    e.preventDefault();
                }
                if (charCode === 46 && val.indexOf('.') !== -1) {
                    e.preventDefault();
                }
            });
        });
    }

    function formatNumberWithCommas(number) {
        if (!number && number !== 0) return '0.00';
        let num = parseFloat(number.toString().replace(/,/g, '')) || 0;
        return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function parseAmount(value) {
        if (!value) return 0;
        return parseFloat(value.toString().replace(/,/g, '')) || 0;
    }

    function calculateTotal() {
        var total = 0;
        $('.payment').each(function() {
            var val = $(this).val();
            total += parseFloat(val.toString().replace(/,/g, '')) || 0;
        });

        $('#total_amount').val(formatNumberWithCommas(total));

        if (total > 0) {
            $('#amount_in_words').text(numberToWords(total) + ' Only');
        } else {
            $('#amount_in_words').text('');
        }
    }

    $(document).ready(function() {
        setupAmountFormatting();
        calculateTotal();
        var receivedAmount = $('#received_amount_display').text().trim();
        if (receivedAmount && receivedAmount !==
            '{{ $opening_numbers ? $opening_numbers->currency_prefix : '' }}') {
            var numMatch = receivedAmount.match(/[\d,.]+/);
            if (numMatch) {
                var formattedReceived = formatNumberWithCommas(numMatch[0]);
                $('#received_amount_display').text(
                    '{{ $opening_numbers ? $opening_numbers->currency_prefix : '' }}' + formattedReceived);
            }
        }
    });

    $('#print_div').click(function(e) {
        e.preventDefault();
        
        // Validate that both buttons have been clicked
        if (!cashGivenClicked || !receivedAmountClicked) {
            showNotification('Please ensure both "Cash Given" and "Received the Amount" buttons have been clicked before saving and printing', 'warning');
            return;
        }

        let location_id = $('#f10_location_id').val();
        let manager_id = $('#manager_dropdown').val();

        $.ajax({
            url: '/mpcs/F10/save-receipt',
            method: 'POST',
            data: {
                location_id: location_id,
                manager_id: manager_id,
                total_amount: $('#total_amount').val(),
                cash_amount: $('input[name="cash"]').val(),
                bank_amount: $('input[name="bank_transfer"]').val(),
                cheque_amount: $('input[name="cheque"]').val(),
                card_amount: $('input[name="card"]').val(),
                cash_given_by: $('#cash_given_user').text(),
                received_by: $('#received_amount_user').text(),
                // IS2015: the date the user picked. storeReceipt() falls back to
                // now() when this is empty, so behaviour is unchanged if the
                // picker did not load.
                form_date: $('#f10_form_date').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(result) {
                if (result.success) {
                    // Get current form values before printing
                    var finalTotalNumeric = parseAmount($('#total_amount').val());
                    var cashAmount = $('input[name="cash"]').val();
                    var bankAmount = $('input[name="bank_transfer"]').val();
                    var chequeAmount = $('input[name="cheque"]').val();
                    var cardAmount = $('input[name="card"]').val();
                    var totalAmount = $('#total_amount').val();
                    var managerName = $('#display_manager_name').text();
                    var locationName = $('#selected_location_display').text();
                    var amountWords = finalTotalNumeric > 0 ? numberToWords(finalTotalNumeric) + ' Only' : '';
                    var managerSignature = $('#manager_signature').val();
                    var cashierSignature = $('#cashier_signature').val();
                    
                    var printContents = document.getElementById('form_f10_print_area').innerHTML;
                    var originalContents = document.body.innerHTML;

                    document.body.innerHTML = printContents;

                    // IS1634-015: remove the manager/cashier signature section from F10 Save & Print preview
                    $('.print-signatures').remove();
                    
                    // Remove all dashed borders in print view
                    $('input[style*="dashed"], div[style*="dashed"]').each(function() {
                        $(this).css('border', 'none');
                    });
                    
                    // Update form number in print
                    $('input[value="{{ $opening_numbers ? $opening_numbers->f10_number : '' }}"]')
                        .val(result.form_no);
                    
                    // Restore all form values in print view
                    $('input[name="cash"]').val(cashAmount);
                    $('input[name="bank_transfer"]').val(bankAmount);
                    $('input[name="cheque"]').val(chequeAmount);
                    $('input[name="card"]').val(cardAmount);
                    $('#total_amount').val(totalAmount);
                    $('#display_manager_name').text(managerName);
                    $('#selected_location_display').text(locationName);
                    $('#amount_in_words').text(amountWords);
                    $('#manager_signature').val(managerSignature);
                    $('#cashier_signature').val(cashierSignature);
                    
                    /*
                     * MA-002 (IS-1915 #2): open the printable receipt instead of
                     * printing this page.
                     *
                     * window.print() here printed the DATA ENTRY SCREEN - input
                     * boxes, buttons and all - which is why the output looked
                     * like separate fields rather than a document.
                     *
                     * The new route renders a standalone receipt with the four
                     * amount breakdowns and prints itself on load. If the id is
                     * missing for any reason we fall back to the old behaviour
                     * rather than leaving the user with no printout at all.
                     */
                    if (result.id) {
                        window.open('/mpcs/F10/receipt/' + result.id + '/print', '_blank');
                    } else {
                        window.print();
                    }
                    document.body.innerHTML = originalContents;
                    
                    // Update F10 number on the normal page
                    $('input[value="{{ $current_f10_number ?? ($opening_numbers ? $opening_numbers->f10_number : '') }}"]')
                        .val(result.form_no);
                    
                    location.reload();
                }
            }
        });
    });
</script>
