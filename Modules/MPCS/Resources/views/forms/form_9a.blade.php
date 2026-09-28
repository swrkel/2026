@extends('layouts.app')
@section('title', __('mpcs::lang.form_9_a'))

@section('content')
    @php
        $location = $location ?? null;
        $form_number = $form_number ?? '';
        $settings = $settings ?? null;

        // Use normal same-page links for these two tabs. This avoids the global
        // Manage Page/tab JavaScript treating the internal hash as a disabled page.
        $canViewF9aForm = auth()->user()->can('f9a_form');
        $canViewF9aSettings = auth()->user()->can('f9a_settings_form');
        $requestedF9aTab = request()->query('f9a_tab');
        $activeF9aTab = ($requestedF9aTab === 'settings' && $canViewF9aSettings)
            ? 'settings'
            : ($canViewF9aForm ? 'form' : 'settings');
    @endphp

    <style>
        /*
         * Screen styling for F9A.
         *
         * The page had NO stylesheet of its own, so it inherited whatever the
         * theme gave it and mixed 6.8pt, 9pt and 10px in the same view. That is
         * why it reads differently from the rest of the module.
         *
         * The rules below give it one type scale and the same card-and-grid
         * treatment the other forms use, without touching the markup - so the
         * form's structure, field names and behaviour are unchanged.
         */
        .f9a-sheet {
            background: #fff;
            border: 1px solid #d8dee9;
            border-radius: 4px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
            padding: 18px 20px 22px;
            margin-bottom: 16px;
        }

        .f9a-sheet .form-title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            text-align: center;
            margin: 0 0 4px;
        }

        /* One type scale for the whole form, replacing the mixed sizes. */
        .f9a-sheet table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            color: #111827;
        }

        .f9a-sheet table th,
        .f9a-sheet table td {
            border: 1px solid #dfe4ea;
            padding: 6px 8px;
            vertical-align: middle;
            line-height: 1.35;
        }

        .f9a-sheet table th {
            background: #f5f7fa;
            font-weight: 700;
            text-align: center;
            white-space: normal;
        }

        /* Equal-width digits so the columns of figures line up. */
        .f9a-sheet table td,
        .f9a-sheet table input {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum" 1;
        }

        /*
         * Inputs sit inside cells without the heavy bordered-box look, which is
         * what made the form read as a spreadsheet rather than a document.
         */
        .f9a-sheet table input.form-control,
        .f9a-sheet table input {
            border: 0;
            background: transparent;
            box-shadow: none;
            height: 26px;
            padding: 0 2px;
            font-size: 13px;
            text-align: right;
            width: 100%;
        }

        .f9a-sheet table input:focus {
            background: #eef4ff;
            outline: none;
        }

        .f9a-sheet .signature-area {
            margin-top: 22px;
            padding-top: 12px;
            border-top: 1px solid #dfe4ea;
        }

        /* Wide tables scroll rather than being squeezed or clipped. */
        .f9a-sheet .table-responsive {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        /* IS2337 #3: the Sales table has a variable number of product columns.
         * Keep every column at a readable width and provide an always-visible
         * bottom slider instead of squeezing/clipping the right-side columns. */
        .f9a-sheet .f9a-sales-table-scroll {
            overflow-x: auto !important;
            overflow-y: hidden !important;
            scrollbar-width: auto;
        }

        #form_9a_sales_table {
            width: max-content !important;
            min-width: 100% !important;
            table-layout: auto !important;
        }

        #form_9a_sales_table th,
        #form_9a_sales_table td {
            white-space: nowrap;
        }

        #f9a-sales-slider {
            display: none;
            margin: 7px 0 2px;
            padding: 5px 8px;
            background: #fff;
            border: 1px solid #d8dee9;
            border-radius: 4px;
        }

        #f9a-sales-slider input[type=range] {
            width: 100%;
            margin: 0;
            cursor: ew-resize;
        }

        @media print {
            #f9a-sales-slider { display: none !important; }
        }
    </style>

    <section class="content">

        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs" id="mpcs_f9a_tabs">
                    <ul class="nav nav-tabs">
                        @if ($canViewF9aForm)
                            <li class="{{ $activeF9aTab === 'form' ? 'active' : '' }}">
                                <a id="f9a_form_tab_link" href="{{ url('/mpcs/form-9a') }}?f9a_tab=form">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.form_9_a')</strong>
                                </a>
                            </li>
                        @endif
                        @if ($canViewF9aSettings)
                            <li class="{{ $activeF9aTab === 'settings' ? 'active' : '' }}">
                                <a id="f9a_settings_tab_link" href="{{ url('/mpcs/form-9a') }}?f9a_tab=settings">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.form_9_a_settings')</strong>
                                </a>
                            </li>
                        @endif
                    </ul>
                    <div class="tab-content">
                        @if ($canViewF9aForm)
                            <div class="tab-pane {{ $activeF9aTab === 'form' ? 'active' : '' }}" id="f9a_form_tab">
                                @include('mpcs::forms.partials.9a_form', [
                                    'location' => $location,
                                    'form_number' => $form_number,
                                    'settings' => $settings,
                                ])
                            </div>
                        @endif
                        @if ($canViewF9aSettings)
                            <div class="tab-pane {{ $activeF9aTab === 'settings' ? 'active' : '' }}" id="f9a_form_settings_tab">
                                @include('mpcs::forms.partials.9a_settings_form')
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade form_9_a_settings_modal" id="form_9_a_settings_modal" tabindex="-1" role="dialog"
            aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade update_form_9_a_settings_modal" id="update_form_9_a_settings_modal" tabindex="-1"
            role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    </section>

    <!-- /.content -->

@endsection
@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            // Loading state for Add F 9A settings button
            $(document).on('click', '#add_form_9_a_settings_button', function() {
                var $btn = $(this);
                $btn.data('original-html', $btn.html());
                $btn.html('<i class="fa fa-refresh fa-spin"></i> ' + $btn.text());
                // Don't disable here; Bootstrap modal needs to fire
            });

            $(document).on('shown.bs.modal', '#form_9_a_settings_modal', function() {
                var $btn = $('#add_form_9_a_settings_button');
                if ($btn.data('original-html')) {
                    $btn.html($btn.data('original-html'));
                }
            });

            $(document).on('hidden.bs.modal', '#form_9_a_settings_modal', function() {
                var $btn = $('#add_form_9_a_settings_button');
                if ($btn.data('original-html')) {
                    $btn.html($btn.data('original-html'));
                }
            });

            // Open modal when button is clicked
            $('#text_details_button').click(function() {
                $('.text_details_modal').modal('show');
            });

            // Save text details
            $('#save_text_details').click(function() {
                var textContent = $('#text_content').val();
                var formNumber = $('.9c_from_date').text(); // Get the form number
                var id = $('#id').val();
                if (textContent.trim() === '') {
                    alert('Please enter some text');
                    return;
                }

                $.ajax({
                    method: 'POST',
                    url: '/mpcs/get-text-store',
                    data: {
                        _token: '{{ csrf_token() }}',
                        text_content: textContent,
                        form: formNumber, // Add form number to the data
                        id: id || undefined // Send id only when editing
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.text_details_modal').modal('hide');
                            $('#text_content').val('');
                            $('#id').val(''); // Clear the ID after save
                            loadTextDetails(); // Refresh the table
                        }
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                });
            });

            // Function to load text details
            function loadTextDetails() {
                $.ajax({
                    url: '/mpcs/get-text-get',
                    method: 'GET',
                    success: function(response) {
                        var tableBody = $('#text_details_table tbody');
                        tableBody.empty();

                        response.data.forEach(function(item) {
                            var row = '<tr>' +
                                '<td>' +
                                '<button class="btn btn-xs btn-primary edit-text-detail" data-id="' +
                                item.id + '"><i class="fa fa-edit"></i> Edit</button> ' +
                                '</td>' +
                                '<td>' + item.text_content + '</td>' +
                                '</tr>';

                            tableBody.append(row);
                        });
                    }
                });
            }

            // Initial load
            loadTextDetails();

            // Edit functionality
            $(document).on('click', '.edit-text-detail', function() {
                var id = $(this).data('id');

                $.ajax({
                    url: '/mpcs/get-text-edit',
                    method: 'GET',
                    data: {
                        id: id
                    },
                    success: function(response) {
                        $('#text_content').val(response.text_content);
                        $('#text_details_form').append(
                            '<input type="hidden" id="id" name="id" value="' + id + '">');
                        $('.text_details_modal').modal('show');
                    }
                });
            });

            // Delete functionality
            $(document).on('click', '.delete-text-detail', function() {
                if (confirm('Are you sure you want to delete this text detail?')) {
                    var id = $(this).data('id');

                    $.ajax({
                        url: '/mpcs/delete-text-detail',
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}',
                            id: id
                        },
                        success: function(response) {
                            if (response.success) {
                                loadTextDetails(); // Refresh the table
                            }
                        }
                    });
                }
            });
        });
        $(document).ready(function() {
            // Function to enable or disable the button based on ref_pre_form_number
            function toggleButtonState() {
                const refPreFormNumber = $('#ref_pre_form_number').val()
                    .trim(); // Get the value of ref_pre_form_number
                const addButton = $('#add_form_9_a_settings_button'); // Select the button

                if (refPreFormNumber !== '') {
                    // If ref_pre_form_number is not empty, disable the button
                    // addButton.prop('disabled', true);
                } else {
                    // If ref_pre_form_number is empty, enable the button
                    // addButton.prop('disabled', false);
                }
            }

            // Call the function on page load
            toggleButtonState();
            // Optionally, recheck the state if ref_pre_form_number changes dynamically
            $(document).on('change', '#ref_pre_form_number', function() {
                toggleButtonState();
            });
        });
        $(document).ready(function() {
            // Fetch and display Form 9A data
            $('#9a_date_ranges').daterangepicker({
                singleDatePicker: true, // For selecting a single date
                showDropdowns: true, // To show the dropdown for predefined date ranges
                locale: {
                    format: 'YYYY-MM-DD', // Adjust the date format according to your needs
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Custom Date Range': [moment().startOf('month'), moment().endOf(
                        'month')], // Default custom date range (this can be modified)
                }
            }, function(start, end, label) {
                if (label === 'Custom Date Range') {
                    // Show the modal for manual input
                    $('.custom_date_typing_modal').modal('show');
                    // $('.custom_date_typing_modal').modal('show'); // Uncomment if needed
                } else {
                    // Set the selected date in the input
                    $('#9a_date_ranges').val(start.format('YYYY-MM-DD'));
                    get9AForm();

                }
                // Refresh DataTable with new date
                //form_9a_tables.ajax.reload();
            });

            $('#custom_date_apply_button').on('click', function() {
                let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $(
                    '#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $(
                    '#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $(
                    '#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $(
                    '#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $(
                    '#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $(
                    '#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (startDate.length === 10 && endDate.length === 10) {
                    let formattedStartDate = moment(startDate).format(moment_date_format);
                    let formattedEndDate = moment(endDate).format(moment_date_format);
                    let fullRange = formattedStartDate + ' ~ ' + formattedEndDate;

                    // === Update #9c_date_range if it exists ===
                    if ($('#9a_date_ranges').length) {
                        $('#9a_date_ranges').val(fullRange);
                        $('#9a_date_ranges').data('daterangepicker').setStartDate(moment(startDate));
                        $('#9a_date_ranges').data('daterangepicker').setEndDate(moment(endDate));
                        $("#report_date_range").text("Date Range: " + fullRange);
                        if (typeof get9AForm === 'function') get9AForm();
                    }

                    // Hide the modal
                    $('.custom_date_typing_modal').modal('hide');
                } else {
                    alert("Please select both start and end dates.");
                }
            });

            // Reset the field when the cancel button is clicked
            $('#9a_date_ranges').on('cancel.daterangepicker', function(ev, picker) {
                $('#9a_date_ranges').val('');
            });

            // Set the default selected date range when initializing the date picker
            $('#9a_date_ranges').data('daterangepicker').setStartDate(moment().startOf('day'));
            $(
                '#9a_date_ranges').data('daterangepicker').setEndDate(moment().endOf('day'));

            // Display the selected date range on the page
            let date = $('#9a_date_ranges').val().split(' - ');

            $('.to_date').text(date[1]);

            // $('#9a_date_ranges').change(function() {
            //     console.log("eccce");
            //     // form_9a_tables.ajax.reload();
            //     get9AForm();
            // });
            get9AForm();

            $('#9a_date_ranges').on('apply.daterangepicker', function(ev, picker) {
                get9AForm();
            });

            $('#form_9a_location_id').on('change', function() {
                get9AForm();
            });



            function syncF9ASalesSlider() {
                var scroll = document.querySelector('.f9a-sales-table-scroll');
                var sliderWrap = document.getElementById('f9a-sales-slider');
                var slider = document.getElementById('f9a-sales-slider-input');
                if (!scroll || !sliderWrap || !slider) return;

                var maxScroll = Math.max(0, scroll.scrollWidth - scroll.clientWidth);
                sliderWrap.style.display = maxScroll > 2 ? 'block' : 'none';
                if (maxScroll <= 2) {
                    slider.value = 0;
                    return;
                }

                var ratio = scroll.scrollLeft / maxScroll;
                slider.value = Math.round(Math.max(0, Math.min(1, ratio)) * 1000);
            }

            $(document)
                .off('input.f9aSalesSlider change.f9aSalesSlider', '#f9a-sales-slider-input')
                .on('input.f9aSalesSlider change.f9aSalesSlider', '#f9a-sales-slider-input', function() {
                    var scroll = document.querySelector('.f9a-sales-table-scroll');
                    if (!scroll) return;
                    var maxScroll = Math.max(0, scroll.scrollWidth - scroll.clientWidth);
                    scroll.scrollLeft = maxScroll * (Number(this.value || 0) / 1000);
                });

            $(document)
                .off('scroll.f9aSalesSlider', '.f9a-sales-table-scroll')
                .on('scroll.f9aSalesSlider', '.f9a-sales-table-scroll', syncF9ASalesSlider);

            $(window).off('resize.f9aSalesSlider').on('resize.f9aSalesSlider', function() {
                window.setTimeout(syncF9ASalesSlider, 0);
            });

            function get9AForm() {
                const start_date = $('input#9a_date_ranges')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');

                const end_date = $('input#9a_date_ranges')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');

                // Add Loading Spinner to arrays before AJAX
                $('#sales_table_body').html(`<tr><td colspan="15" class="text-center" style="padding: 30px;"><i class="fa fa-spinner fa-spin fa-2x fa-fw"></i><br/>Loading Sales Data...</td></tr>`);
                $('#receipts_subcat_body').html(`<tr><td colspan="4" class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-fw"></i> Loading Receipts...</td></tr>`);
                $('#payments_table_body').html(`<tr><td colspan="4" class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-fw"></i> Loading Payments...</td></tr>`);

                $.ajax({
                    method: 'get',
                    url: '/mpcs/get-9a-form_value',
                    data: {
                        start_date,
                        end_date,
                        form_9a_location_id: $('#form_9a_location_id').val()
                    },
                    success: function(data) {
                        try {
                            console.log("Form 9A Data Received:", data);
                            if (!data || !data.payments) {
                                console.error("Malformed data received:", data);
                                $('#sales_table_body, #receipts_subcat_body, #payments_table_body').html('<tr><td colspan="10" class="text-center text-danger">Error: Malformed data.</td></tr>');
                                return;
                            }

                            // Meta details
                            $('#date_range_from').text(start_date);
                            $('#date_range_to').text(end_date);
                            $('#form_number').text(data.form_number ?? '');
                            if (data.location) {
                                $('#business_location_name_header').text(data.location.name);
                            }

                            const fmt = (v) => formatCurrency(v);
                            const safeNum = (v) => parseFloat(v || 0);

                            // --- 1. Populate Sales Section (Top Table) ---
                            let salesHead = `
                                <tr>
                                    <th style="width: 30px;"></th>
                                    <th style="width: 170px;">Description</th>
                                    ${data.sub_categories_data.map(item => `<th style="width: 75px;">${item.name}</th>`).join('')}
                                    <th style="background-color: #e8e8e8; font-weight: bold; width: 85px;">Total</th>
                                    <th style="width: 200px;">Office Use</th>
                                </tr>
                            `;
                            $('#sales_table_head').html(salesHead);

                            let salesBody = `
                                <tr>
                                    <td class="text-center font-weight-bold">1</td>
                                    <th class="text-left">Cash Today</th>
                                    ${data.sub_categories_data.map(item => `<td class="text-right">${fmt(item.row1)}</td>`).join('')}
                                    <td class="text-right font-weight-bold" style="background-color: #f0f0f0;">${fmt(data.total_row1)}</td>
                                    <td rowspan="7" class="text-left" style="vertical-align: top; font-size: 10px; padding: 8px 12px;">
                                        <div style="margin-bottom: 8px;"><strong>Received On</strong></div>
                                        <div style="margin-bottom: 8px;"><strong>Checked</strong></div>
                                        <div style="margin-bottom: 20px;"><strong>Approved</strong></div>
                                        <div style="display: flex; justify-content: flex-end; margin-bottom: 8px; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 2px;">Short <span style="margin: 0 4px; border-left: 1px solid #000;"></span> Excess</div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                            <div><strong>Today</strong></div>
                                            <div style="flex: 1; border-right: 1px solid #000; margin-right: 20px;"></div>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                            <div><strong>Previous Day</strong></div>
                                            <div style="flex: 1; border-right: 1px solid #000; margin-right: 20px;"></div>
                                        </div>
                                        <div style="display: flex; justify-content: space-between;">
                                            <div><strong>Total</strong></div>
                                            <div style="flex: 1; border-right: 1px solid #000; margin-right: 20px;"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center font-weight-bold">2</td>
                                    <th class="text-left">Credit Today</th>
                                    ${data.sub_categories_data.map(item => `<td class="text-right">${fmt(item.row2)}</td>`).join('')}
                                    <td class="text-right font-weight-bold" style="background-color: #f0f0f0;">${fmt(data.total_row2)}</td>
                                </tr>
                                <tr>
                                    <td class="text-center font-weight-bold">3</td>
                                    <th class="text-left">Cash Previous day</th>
                                    ${data.sub_categories_data.map(item => `<td class="text-right">${fmt(item.row3)}</td>`).join('')}
                                    <td class="text-right font-weight-bold" style="background-color: #f0f0f0;">${fmt(data.total_row3)}</td>
                                </tr>
                                <tr>
                                    <td class="text-center font-weight-bold">4</td>
                                    <th class="text-left">Credit Previous Day</th>
                                    ${data.sub_categories_data.map(item => `<td class="text-right">${fmt(item.row4)}</td>`).join('')}
                                    <td class="text-right font-weight-bold" style="background-color: #f0f0f0;">${fmt(data.total_row4)}</td>
                                </tr>
                                <tr>
                                    <td class="text-center font-weight-bold">5</td>
                                    <th class="text-left">Total Cash - As of Today (1 + 3)</th>
                                    ${data.sub_categories_data.map(item => `<td class="text-right">${fmt(item.row5)}</td>`).join('')}
                                    <td class="text-right font-weight-bold" style="background-color: #f0f0f0;">${fmt(data.total_row5)}</td>
                                </tr>
                                <tr>
                                    <td class="text-center font-weight-bold">6</td>
                                    <th class="text-left">Total Credit - As of Today (2 + 4)</th>
                                    ${data.sub_categories_data.map(item => `<td class="text-right">${fmt(item.row6)}</td>`).join('')}
                                    <td class="text-right font-weight-bold" style="background-color: #f0f0f0;">${fmt(data.total_row6)}</td>
                                </tr>
                                <tr>
                                    <td class="text-center font-weight-bold">7</td>
                                    <th class="text-left">Total Sale as of Today (5 + 6)</th>
                                    ${data.sub_categories_data.map(item => `<td class="text-right">${fmt(item.row7)}</td>`).join('')}
                                    <td class="text-right font-weight-bold" style="background-color: #f0f0f0;">${fmt(data.total_row7)}</td>
                                </tr>
                            `;
                            $('#sales_table_body').html(salesBody);
                            // Let the browser calculate the dynamic column widths,
                            // then refresh the bottom horizontal slider.
                            window.setTimeout(syncF9ASalesSlider, 0);

                            // --- 2. Populate Receipts Section (Bottom-Left) ---
                            let receiptsHtml = '';
                            let totalPrev = 0, totalToday = 0, totalTotal = 0;
                            if (data.receipts_data) {
                                data.receipts_data.forEach(item => {
                                    totalPrev += item.prev;
                                    totalToday += item.today;
                                    totalTotal += item.total;
                                    receiptsHtml += `
                                        <tr>
                                            <td>${fmt(item.prev)}</td>
                                            <th class="text-left">${item.description}</th>
                                            <td>${fmt(item.today)}</td>
                                            <td>${fmt(item.total)}</td>
                                        </tr>
                                    `;
                                });
                            }
                            $('#receipts_subcat_body').html(receiptsHtml);
                            $('#receipts_total_prev').text(fmt(totalPrev));
                            $('#receipts_total_today').text(fmt(totalToday));
                            $('#receipts_total_total').text(fmt(totalTotal));

                            // --- 3. Populate Payments Section (Bottom-Right) ---
                            let paymentsHtml = `
                                <tr>
                                    <td>${fmt(data.payments.cash.prev)}</td>
                                    <th class="text-right" style="padding-right: 15px; vertical-align: middle;">Cash / F 10 No:</th>
                                    <td>
                                        <div class="text-center font-weight-bold" style="padding: 6px 12px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 4px;">
                                            ${data.payments.cash.form_no ? data.payments.cash.form_no + ' | ' : ''}${fmt(data.payments.cash.today)}
                                        </div>
                                        <input type="hidden" class="payment-input" id="cash_payment" value="${safeNum(data.payments.cash.today).toFixed(2)}">
                                    </td>
                                    <td id="cash_payment_total">${fmt(data.payments.cash.total)}</td>
                                </tr>
                                <tr>
                                    <td>${fmt(data.payments.cheques.prev)}</td>
                                    <th class="text-right" style="padding-right: 15px;">Cheques</th>
                                    <td>
                                        <div class="text-center" style="padding: 6px 12px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 4px;">
                                            ${fmt(data.payments.cheques.today)}
                                        </div>
                                        <input type="hidden" class="payment-input" id="cheque_payment" value="${safeNum(data.payments.cheques.today).toFixed(2)}">
                                    </td>
                                    <td id="cheque_payment_total">${fmt(data.payments.cheques.total)}</td>
                                </tr>
                            `;

                            // Card Rows
                            if (data.payments.cards && data.payments.cards.length > 0) {
                                data.payments.cards.forEach((card, idx) => {
                                    paymentsHtml += `
                                        <tr>
                                            <td>${fmt(card.prev)}</td>
                                            <th class="text-right" style="padding-right: 15px;">${card.name}</th>
                                            <td>
                                                <div class="text-center" style="padding: 6px 12px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 4px;">
                                                    ${fmt(card.today)}
                                                </div>
                                                <input type="hidden" class="payment-input card-input" value="${safeNum(card.today).toFixed(2)}">
                                            </td>
                                            <td class="card-total-cell">${fmt(card.total)}</td>
                                        </tr>
                                    `;
                                });
                            }

                            // Bank Rows
                            if (data.payments.banks && data.payments.banks.length > 0) {
                                data.payments.banks.forEach((bank, idx) => {
                                    paymentsHtml += `
                                        <tr>
                                            <td>${fmt(bank.prev)}</td>
                                            <th class="text-right" style="padding-right: 15px;">${bank.name}</th>
                                            <td>
                                                <div class="text-center" style="padding: 6px 12px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 4px;">
                                                    ${fmt(bank.today)}
                                                </div>
                                                <input type="hidden" class="payment-input bank-input" value="${safeNum(bank.today).toFixed(2)}">
                                            </td>
                                            <td class="bank-total-cell">${fmt(bank.total)}</td>
                                        </tr>
                                    `;
                                });
                            }

                            // Other Row
                            paymentsHtml += `
                                <tr>
                                    <td>${fmt(data.payments.other.prev)}</td>
                                    <th class="text-right" style="padding-right: 15px;">Other</th>
                                    <td><input type="number" class="form-control text-center payment-input" id="other_payment" value="${safeNum(data.payments.other.today).toFixed(2)}" step="0.01"></td>
                                    <td id="other_payment_total">${fmt(data.payments.other.total)}</td>
                                </tr>
                            `;

                            // Total Row
                            paymentsHtml += `
                                <tr class="font-weight-bold">
                                    <td id="payments_total_prev">${fmt(data.payments.pre_day_total)}</td>
                                    <th class="text-right" style="padding-right: 15px;">Total</th>
                                    <td id="payments_total_today">0.00</td>
                                    <td id="payments_total_total">0.00</td>
                                </tr>
                                <tr class="font-weight-bold">
                                    <td id="pre_day_balance">${fmt(data.payments.pre_day_balance)}</td>
                                    <th class="text-right" style="padding-right: 15px;">Balance in Hand</th>
                                    <td id="balance_in_hand_today">0.00</td>
                                    <td id="balance_total_total">${fmt(data.payments.pre_day_balance)}</td>
                                </tr>
                                <tr class="font-weight-bold bg-light">
                                    <td id="pre_day_grand_total">${fmt(data.payments.pre_day_grand_total)}</td>
                                    <th class="text-right" style="padding-right: 15px;">Grand Total</th>
                                    <td id="grand_total_today">0.00</td>
                                    <td id="grand_total_total">0.00</td>
                                </tr>
                            `;

                            $('#payments_table_body').html(paymentsHtml);

                            if (data.text_details) {
                                $('#textdetail').html(data.text_details.text_content);
                            }
                            
                            // Set up event listeners for the new inputs
                            $('.payment-input').off('input').on('input', updateTotals);
                            
                            
                            updateTotals();
                        } catch (err) {
                            console.error("Critical JS Error in AJAX Success:", err);
                            $('#sales_table_body, #receipts_subcat_body, #payments_table_body').html(`<tr><td colspan="10" class="text-center text-danger">JS Error: ${err.message}</td></tr>`);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        $('#sales_table_body, #receipts_subcat_body, #payments_table_body').html('<tr><td colspan="10" class="text-center text-danger">AJAX Request Failed: ' + status + '</td></tr>');
                    }
                });
            }

            function clearFormValues() {
                $('[id$="_sales"], [id^="total_"]').text('');
            }

            // Reload Form 9A data when the date changes
            // $('#9a_date_ranges').change(function() {
            //     get9AForm();
            // });

            function clearFormValues() {
                $('[id$="_rup"], [id$="_cent"]').text('');
            }



            // Reload Form 9A data when the date changes
            // $('#9a_date_ranges').change(function() {
            //     get9AForm();
            // });

            // Initialize DataTable for Form 9A settings (Sales / Receipts)
            var form_9a_settings_table = $('#form_9a_settings_table').DataTable({
                processing: true,
                serverSide: true,
                paging: false,
                autoWidth: false,
                responsive: false,
                // IS2286: use the full container and wrap headings on screen.
                scrollX: false,
                order: [],
                ajax: {
                    type: "get",
                    url: "/mpcs/get-form-9a-settings",
                    dataSrc: "data", // Ensure this matches the key in your JSON response
                    error: function(xhr, error, thrown) {
                        console.error("DataTables error:", xhr.responseText);
                    }
                },
                columns: [{
                        data: 'action',
                        name: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'starting_number',
                        name: 'starting_number'
                    },
                    {
                        data: 'total_sale_to_pre',
                        name: 'total_sale_to_pre'
                    },
                    {
                        data: 'pre_day_cash_sale',
                        name: 'pre_day_cash_sale'
                    },
                    {
                        data: 'pre_day_card_sale',
                        name: 'pre_day_card_sale'
                    },
                    {
                        data: 'pre_day_credit_sale',
                        name: 'pre_day_credit_sale'
                    },
                    {
                        data: 'pre_day_cash',
                        name: 'pre_day_cash'
                    },
                    {
                        data: 'pre_day_cheques',
                        name: 'pre_day_cheques'
                    },
                    {
                        data: 'pre_day_total',
                        name: 'pre_day_total'
                    },
                    {
                        data: 'pre_day_balance',
                        name: 'pre_day_balance'
                    },
                    {
                        data: 'pre_day_grand_total',
                        name: 'pre_day_grand_total'
                    }
                ]
            });

            // Separate Payments-section DataTable (same source, payment-focused columns)
            var form_9a_payments_table = $('#form_9a_payments_table').DataTable({
                processing: true,
                serverSide: true,
                paging: false,
                autoWidth: false,
                responsive: false,
                // IS2286: all six payment columns remain visible without a slider.
                scrollX: false,
                order: [],
                drawCallback: function() {
                    $('#f9a_payments_table_section').scrollLeft(0);
                },
                ajax: {
                    type: "get",
                    url: "/mpcs/get-form-9a-settings",
                    dataSrc: "data",
                    error: function(xhr, error, thrown) {
                        console.error("DataTables (payments) error:", xhr.responseText);
                    }
                },
                columns: [
                    { data: 'starting_number', name: 'starting_number' },
                    { data: 'pre_day_cash', name: 'pre_day_cash' },
                    { data: 'pre_day_cheques', name: 'pre_day_cheques' },
                    { data: 'pre_day_total', name: 'pre_day_total' },
                    { data: 'pre_day_balance', name: 'pre_day_balance' },
                    { data: 'pre_day_grand_total', name: 'pre_day_grand_total' }
                ]
            });

            function adjustF9ASettingsTables() {
                window.setTimeout(function() {
                    if ($.fn.dataTable.isDataTable('#form_9a_settings_table')) {
                        form_9a_settings_table.columns.adjust().draw(false);
                    }
                    if ($.fn.dataTable.isDataTable('#form_9a_payments_table')) {
                        form_9a_payments_table.columns.adjust().draw(false);
                    }
                }, 80);
            }

            adjustF9ASettingsTables();
            $(document).on('click', 'a[href="#9a_form_settings_tab"], a[href*="f9a_tab=settings"]', adjustF9ASettingsTables);
            $(window).on('resize.f9aSettings', adjustF9ASettingsTables);

            // Handle Form 9A settings submission
            $(document).on('submit', 'form#add_9a_form_settings', function(e) {
                e.preventDefault();
                var $form = $(this);
                $form.find('button[type="submit"]').attr('disabled', true);
                var data = $form.serialize();
                const dateValue = $form.find('[name="datepicker"]').val();
                const refPrevious = $form.find('[name="ref_previous_form_number"]').val();

                $.ajax({
                    method: $form.attr('method'),
                    url: $form.attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            form_9a_settings_table.ajax.reload();
                            if (typeof form_9a_payments_table !== 'undefined') {
                                form_9a_payments_table.ajax.reload();
                            }
                            get9AForm();
                            $('div#form_9_a_settings_modal').modal('hide');
                            $('#add_form_9_a_settings_button').prop('disabled', true);
                            $('.9a_from_date').html(dateValue);
                            $('.9a_from_no').html(refPrevious);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error submitting Form 9A settings:", error);
                        toastr.error("Failed to save Form 9A settings. Please try again.");
                    },
                    complete: function() {
                        $form.find('button[type="submit"]').attr('disabled', false);
                    }
                });
            });

            // Handle Form 9A settings update
            $(document).on('submit', 'form#update_9a_form_settings', function(e) {
                e.preventDefault();
                var $form = $(this);
                $form.find('button[type="submit"]').attr('disabled', true);
                var data = $form.serialize();
                const dateValue = $form.find('[name="datepicker"]').val();
                const refPrevious = $form.find('[name="ref_previous_form_number"]').val();

                $.ajax({
                    method: $form.attr('method'),
                    url: $form.attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            form_9a_settings_table.ajax.reload();
                            if (typeof form_9a_payments_table !== 'undefined') {
                                form_9a_payments_table.ajax.reload();
                            }
                            get9AForm();
                            $('div#update_form_9_a_settings_modal').modal('hide');
                            $('#add_form_9_a_settings_button').prop('disabled', true);
                            $('.9a_from_date').html(dateValue);
                            $('.9a_from_no').html(refPrevious);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error updating Form 9A settings:", error);
                        toastr.error("Failed to update Form 9A settings. Please try again.");
                    },
                    complete: function() {
                        $form.find('button[type="submit"]').attr('disabled', false);
                    }
                });
            });

            // Print Form 9A
            $("#print_div").click(function() {
                printDiv();
            });

            function printDiv() {
                var source = document.getElementById('form_9a_print_area');
                if (!source) {
                    return;
                }

                var clone = source.cloneNode(true);
                $(source).find('input, textarea, select').each(function(index) {
                    var target = $(clone).find('input, textarea, select').get(index);
                    if (!target) {
                        return;
                    }

                    if (target.tagName === 'SELECT') {
                        target.value = this.value;
                    } else {
                        target.value = this.value;
                        target.setAttribute('value', this.value);
                    }
                });

                var printWindow = window.open('', '_blank', 'width=1200,height=850');
                if (!printWindow) {
                    window.print();
                    return;
                }

                printWindow.document.open();
                printWindow.document.write(`<!doctype html>
<html>
<head>
    <title>F9A</title>
    <style>
        /*
         * Print styling brought to the same standard as the F15 daily report.
         *
         * It was Times New Roman at 6.8pt with 1px cell padding - dense enough
         * to be hard to read and quite unlike every other printed form in the
         * system. The changes below are the ones that decide whether a printed
         * sheet looks considered or merely produced:
         *
         *   - a sans-serif face at a legible size, matching the other forms
         *   - tabular figures, so decimal points line up down each column
         *   - emphasis carried by RULES and WEIGHT rather than shading, which
         *     prints identically on every printer and does not depend on
         *     "background graphics" being enabled in the print dialog
         *   - real cell padding, so the grid breathes
         *   - a proper masthead rather than a heading floating above the table
         */
        @page { size: A4 landscape; margin: 8mm; }

        html, body {
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.28;
            -webkit-font-smoothing: antialiased;
        }

        * { box-sizing: border-box; }

        #form_9a_print_area {
            width: 100%;
            height: auto;
            page-break-inside: auto;
        }

        .row { display: flex; flex-wrap: wrap; width: 100%; margin: 0 !important; }
        .col-md-12 { width: 100%; padding: 0 2px !important; }
        .col-md-6 { width: 50%; padding: 0 3px !important; }
        .table-responsive { overflow: visible !important; }

        /*
         * table-layout: auto, not fixed. With fixed layout a column never grows,
         * so a long figure simply spills across the cell border - the fault that
         * had to be corrected on the F15 print.
         */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            margin: 0 0 4px !important;
        }

        thead { display: table-header-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }

        th, td {
            border: .75px solid #444 !important;
            padding: 3px 5px !important;
            line-height: 1.25;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            border-top: 1.25px solid #000 !important;
            border-bottom: 1.25px solid #000 !important;
            font-size: 9.5pt;
            letter-spacing: .02em;
        }

        td {
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum" 1;
            white-space: nowrap;
        }

        .text-left { text-align: left !important; white-space: normal; }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }

        /* Figures typed into the form print as figures, not as form controls. */
        input {
            border: 0 !important;
            background: transparent !important;
            height: auto !important;
            padding: 0 !important;
            font-size: 10pt !important;
            font-family: inherit !important;
            font-variant-numeric: tabular-nums;
            width: 100%;
            text-align: right;
            color: #000 !important;
        }

        h4, h5 { margin: 0 !important; line-height: 1.2 !important; }

        /* Masthead: the title reads as a heading, closed off by a rule. */
        .form-title {
            font-size: 15pt !important;
            font-weight: 700 !important;
            text-align: center;
            letter-spacing: .01em;
            margin: 0 0 2px !important;
        }

        #form_9a_print_area > .row:first-child {
            border-bottom: 1.25px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px !important;
        }

        #receipts_payments_row { margin: 6px 0 0 !important; }
        .form-group, .signature-area { margin: 6px 0 !important; }

        .signature-area {
            page-break-inside: avoid;
            padding-top: 10px;
        }

        .no-print, button, .select2-container, .dropdown, .box-header { display: none !important; }
    </style>
</head>
<body>${clone.outerHTML}</body>
</html>`);
                printWindow.document.close();
                printWindow.focus();
                printWindow.onload = function() {
                    printWindow.print();
                };
                printWindow.onafterprint = function() {
                    printWindow.close();
                };
            }
        });

        // Helper function to format currency
        function formatCurrency(amount) {
            return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }




        function updateTextDetails(data) {
            // Create a more detailed display of the text details
            console.log("updateTextDetails", data.text_content);


            $('#textdetail').html(data.text_content);
        }

        function formatCurrency(value) {
            return parseFloat(value || 0).toFixed(2);
        }

        /**
         * Calculate and update Payments Section totals using business rules:
         * - Total payments (today) = cash today + cheques/cards today
         * - Cash as of today = previous-day cash total (stored) + cash today
         * - Cheques/cards as of today = previous-day cheques/cards total (stored) + cheques/cards today
         * - Totals and balances roll forward from stored previous-day figures; no previous-day values are recomputed here.
         */
        function updateTotals() {
            const fmt = (v) => parseFloat(v || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            const parse = (v) => parseFloat(String(v || 0).replace(/,/g, '')) || 0;

            let totalPrev = 0;
            let totalToday = 0;
            let totalTotal = 0;

            // 1. Cash Row
            const cashPrev = parse($('#payments_table_body tr:eq(0) td:eq(0)').text());
            const cashToday = parse($('#cash_payment').val());
            const cashTotal = cashPrev + cashToday;
            $('#cash_payment_total').text(fmt(cashTotal));
            totalPrev += cashPrev; totalToday += cashToday; totalTotal += cashTotal;

            // 2. Cheque Row
            const chequePrev = parse($('#payments_table_body tr:eq(1) td:eq(0)').text());
            const chequeToday = parse($('#cheque_payment').val());
            const chequeTotal = chequePrev + chequeToday;
            $('#cheque_payment_total').text(fmt(chequeTotal));
            totalPrev += chequePrev; totalToday += chequeToday; totalTotal += chequeTotal;

            // 3. Card Rows
            $('.card-input').each(function() {
                const prev = parse($(this).closest('tr').find('td:eq(0)').text());
                const today = parse($(this).val());
                const total = prev + today;
                $(this).closest('tr').find('.card-total-cell').text(fmt(total));
                totalPrev += prev; totalToday += today; totalTotal += total;
            });

            // 4. Bank Rows
            $('.bank-input').each(function() {
                const prev = parse($(this).closest('tr').find('td:eq(0)').text());
                const today = parse($(this).val());
                const total = prev + today;
                const totalCell = $(this).closest('tr').find('.bank-total-cell');
                if (totalCell.length) {
                    totalCell.text(fmt(total));
                }
                totalPrev += prev; totalToday += today; totalTotal += total;
            });

            // 5. Other Row
            if ($('#other_payment').length) {
                const otherPrev = parse($('#other_payment').closest('tr').find('td:eq(0)').text());
                const otherToday = parse($('#other_payment').val());
                const otherTotal = otherPrev + otherToday;
                $('#other_payment_total').text(fmt(otherTotal));
                totalPrev += otherPrev; totalToday += otherToday; totalTotal += otherTotal;
            }

            // --- Footer Rows ---

            // Total Row
            $('#payments_total_prev').text(fmt(totalPrev));
            $('#payments_total_today').text(fmt(totalToday));
            $('#payments_total_total').text(fmt(totalTotal));

            // Grand Total Today must equal the Receipts Section total for Today.
            const grandTotalPrev = parse($('#pre_day_grand_total').text());
            const receiptsToday = parse($('#receipts_total_today').text());
            const grandTotalToday = receiptsToday;

            // Balance in Hand = Grand Total Today - Total Payments Today.
            const balanceInHandPrev = parse($('#pre_day_balance').text());
            const balanceInHandToday = grandTotalToday - totalToday;
            const balanceInHandTotal = balanceInHandPrev + balanceInHandToday;
            const grandTotalTotal = grandTotalPrev + grandTotalToday;

            $('#balance_in_hand_today').text(fmt(balanceInHandToday));
            $('#balance_total_total').text(fmt(balanceInHandTotal));
            $('#grand_total_today').text(fmt(grandTotalToday));
            $('#grand_total_total').text(fmt(grandTotalTotal));
            
            console.log("Calculated Totals:", {
                totalPrev, totalToday, totalTotal,
                balanceInHandPrev, balanceInHandTotal,
                grandTotalPrev, grandTotalToday, grandTotalTotal
            });
        }

        function populatePaymentsTable(data) {
            // Re-setup event listeners for the new inputs
            $(document).on('input', '.payment-input', function() {
                updateTotals();
            });

            updateTotals();
        }

        // Helper function to format currency
        function formatCurrency(amount) {
            return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
        // Event listeners for input changes (cash / cheques-cards / other)
        $(document).ready(function() {
            $('#cash, #card, #other').on('input', updateTotals);
        });
    </script>
@endsection
