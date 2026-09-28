
<style>
    /*
    |--------------------------------------------------------------------------
    | PetroPD Assign Pumps Professional UI
    |--------------------------------------------------------------------------
    | Design-only update. Keeps the existing form fields, names, submit route,
    | validation and reconfirmation workflow unchanged.
    */
    .swal-overlay {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 16px !important;
    }

    .swal-modal {
        width: 520px;
        max-width: 92vw;
        margin: 0 !important;
        border-radius: 16px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .22);
    }

    .swal-title {
        color: #d9534f;
        font-weight: 800;
        margin-bottom: 10px;
    }

    .swal-text,
    .swal-content {
        width: 100%;
    }

    .swal-content {
        margin-top: 10px;
    }

    .swal-content .table th,
    .swal-content .table td {
        padding: 6px 8px !important;
        font-size: 14px;
        vertical-align: middle !important;
    }

    .swal-footer {
        text-align: center;
    }

    /* S372: Professional dark-blue reconfirmation popup for Assign Pumps */
    .swal-modal.petropd-reconfirm-swal {
        width: 640px !important;
        max-width: 94vw !important;
        border-radius: 16px !important;
        padding: 30px 42px 34px !important;
        border: 1px solid rgba(64, 132, 241, .75) !important;
        background:
            radial-gradient(circle at 18% 0%, rgba(58, 105, 184, .36) 0%, rgba(58, 105, 184, 0) 34%),
            radial-gradient(circle at 82% 100%, rgba(22, 52, 112, .52) 0%, rgba(22, 52, 112, 0) 38%),
            linear-gradient(135deg, #18335f 0%, #0f2a58 47%, #071d45 100%) !important;
        box-shadow: 0 26px 80px rgba(0, 0, 0, .42), inset 0 1px 0 rgba(255,255,255,.10) !important;
        color: #fff !important;
        overflow: hidden !important;
    }

    .swal-modal.petropd-reconfirm-swal .swal-icon--warning {
        width: 82px;
        height: 82px;
        border-color: #ffad2f !important;
        margin: 0 auto 18px !important;
        box-shadow: 0 8px 22px rgba(245, 158, 11, .25);
    }

    .swal-modal.petropd-reconfirm-swal .swal-icon--warning__body,
    .swal-modal.petropd-reconfirm-swal .swal-icon--warning__dot {
        background-color: #ffad2f !important;
    }

    .swal-modal.petropd-reconfirm-swal .swal-title {
        color: #ff4d3f !important;
        font-size: 31px !important;
        font-weight: 900 !important;
        margin: 0 0 12px !important;
        text-shadow: 0 2px 6px rgba(0,0,0,.28);
    }

    .swal-modal.petropd-reconfirm-swal .swal-content {
        margin: 0 !important;
        width: 100% !important;
        padding: 0 !important;
    }

    .petropd-reconfirm-content {
        color: #fff;
        text-align: center;
        font-size: 16px;
        line-height: 1.5;
    }

    .petropd-reconfirm-subtitle {
        margin: 0 0 16px;
        font-size: 16px;
        font-weight: 700;
        color: rgba(255,255,255,.92);
    }

    .petropd-reconfirm-table {
        width: 100%;
        max-width: 500px;
        margin: 0 auto 18px;
        border-collapse: separate;
        border-spacing: 0;
        overflow: hidden;
        border-radius: 11px;
        background: rgba(255,255,255,.94);
        box-shadow: 0 12px 30px rgba(0,0,0,.20);
    }

    .petropd-reconfirm-table th,
    .petropd-reconfirm-table td {
        padding: 12px 22px !important;
        border: 1px solid #d6dde8 !important;
        color: #33415c !important;
        font-size: 16px !important;
        text-align: left !important;
        vertical-align: middle !important;
        background: rgba(255,255,255,.93) !important;
    }

    .petropd-reconfirm-table th {
        width: 42%;
        font-weight: 900 !important;
        color: #253852 !important;
    }

    .petropd-reconfirm-question {
        margin: 0 0 4px;
        font-size: 17px;
        font-weight: 700;
        color: rgba(255,255,255,.94);
    }

    .swal-modal.petropd-reconfirm-swal .swal-footer {
        margin-top: 22px !important;
        padding: 0 !important;
        text-align: center !important;
    }

    .swal-modal.petropd-reconfirm-swal .swal-button {
        min-width: 140px !important;
        min-height: 52px !important;
        border-radius: 9px !important;
        border: 0 !important;
        color: #fff !important;
        font-size: 22px !important;
        font-weight: 900 !important;
        box-shadow: 0 10px 20px rgba(0,0,0,.26) !important;
    }

    .swal-modal.petropd-reconfirm-swal .swal-button--cancel {
        background: linear-gradient(135deg, #ff4646 0%, #d51f1f 100%) !important;
    }

    .swal-modal.petropd-reconfirm-swal .swal-button--confirm {
        background: linear-gradient(135deg, #38d94c 0%, #149a27 100%) !important;
    }

    .petropd-assign-pumps-modal .modal-dialog {
        width: 84%;
        max-width: 1260px;
    }

    .petropd-assign-pumps-modal .modal-content {
        border: 1px solid #d7e4f3;
        border-radius: 22px;
        overflow: hidden;
        background: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
        box-shadow: 0 26px 65px rgba(15, 23, 42, .20);
    }

    .petropd-assign-pumps-modal .modal-header {
        position: relative;
        min-height: 108px;
        padding: 28px 34px;
        border-bottom: 0;
        color: #fff;
        background: linear-gradient(135deg, #183c91 0%, #2f62c9 58%, #1b2b67 100%);
        overflow: hidden;
    }

    .petropd-assign-pumps-modal .modal-header:before,
    .petropd-assign-pumps-modal .modal-header:after {
        content: '';
        position: absolute;
        border-radius: 999px;
        background: rgba(255,255,255,.07);
        pointer-events: none;
    }

    .petropd-assign-pumps-modal .modal-header:before {
        width: 420px;
        height: 420px;
        left: 250px;
        top: -280px;
    }

    .petropd-assign-pumps-modal .modal-header:after {
        width: 340px;
        height: 340px;
        right: -90px;
        bottom: -260px;
    }

    .petropd-assign-pumps-modal .close {
        position: absolute;
        right: 16px;
        top: 14px;
        z-index: 3;
        color: #fff;
        opacity: .88;
        text-shadow: none;
        font-size: 24px;
    }

    .petropd-assign-header-row {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 22px;
        width: 100%;
        padding-right: 46px;
    }

    .petropd-assign-title-wrap {
        display: flex;
        align-items: center;
        gap: 18px;
        min-width: 0;
    }

    .petropd-assign-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 58px;
        height: 58px;
        border-radius: 18px;
        background: rgba(255,255,255,.12);
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.18);
        font-size: 38px;
        line-height: 1;
    }

    .petropd-assign-pumps-modal .modal-title {
        font-size: 30px !important;
        line-height: 1.1;
        font-weight: 900;
        color: #fff;
        margin: 0;
        letter-spacing: -.4px;
    }

    .petropd-shift-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        font-size: 31px;
        color: #ffffff;
        font-weight: 900;
        border: 1px solid rgba(255,255,255,.22);
        background: linear-gradient(135deg, #9f3150 0%, #f53535 70%, #dd1f1f 100%);
        border-radius: 16px;
        padding: 12px 26px;
        line-height: 1.15;
        box-shadow: 0 12px 25px rgba(220, 38, 38, .36), inset 0 1px 0 rgba(255,255,255,.25);
    }

    .petropd-assign-pumps-modal .modal-body {
        background: transparent;
        padding: 32px 34px 0;
    }

    .petropd-section-card {
        background: rgba(255,255,255,.88);
        border: 1px solid #dce7f5;
        border-radius: 16px;
        padding: 18px 20px;
        margin-bottom: 22px;
        box-shadow: 0 13px 32px rgba(28, 63, 115, .10);
    }

    .petropd-section-title {
        margin: 0 0 12px;
        color: #ef2323;
        font-size: 20px;
        font-weight: 900;
    }

    /*
     * Display each unclosed shift in one compact row. When there are multiple
     * shifts, each row is rendered directly below the previous one.
     */
    .petropd-open-shifts-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .petropd-open-shift-item {
        display: grid;
        grid-template-columns: minmax(170px, .8fr) minmax(240px, 1fr) minmax(280px, 1.35fr);
        align-items: center;
        column-gap: 24px;
        padding: 10px 14px;
        color: #194493;
        font-size: 15px;
        line-height: 1.4;
        min-height: 0;
        border: 1px solid #d8e4f4;
        border-radius: 10px;
        background: #fff;
    }

    .petropd-open-shift-detail {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .petropd-open-shift-item strong {
        color: #143e91;
        font-weight: 900;
    }

    .petropd-form-card {
        background: rgba(255,255,255,.94);
        border: 1px solid #dce7f5;
        border-radius: 16px;
        padding: 26px 28px 22px;
        box-shadow: 0 14px 34px rgba(28, 63, 115, .10);
    }

    .petropd-assign-pumps-modal .form-control {
        border-radius: 13px;
        border: 1px solid #d9e4f2;
        min-height: 52px;
        font-size: 16px;
        color: #1f2937;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, .03);
    }

    .petropd-assign-pumps-modal .select2-container .select2-selection--single {
        min-height: 52px;
        border-radius: 13px;
        border-color: #d9e4f2;
        display: flex;
        align-items: center;
    }

    .petropd-assign-pumps-modal .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 50px;
        padding-left: 14px;
        color: #1f2937;
        font-size: 16px;
    }

    .petropd-assign-pumps-modal .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 50px;
        right: 8px;
    }

    .petropd-assign-pumps-modal label {
        color: #1f2937;
        font-weight: 900;
        font-size: 16px;
        margin-bottom: 10px;
    }

    .petropd-pump-grid {
        border: 1px solid #dbe5f2;
        border-radius: 15px;
        padding: 18px 18px;
        min-height: 88px;
        max-height: 190px;
        overflow-y: auto;
        background: #fff;
        box-shadow: inset 0 1px 4px rgba(15, 23, 42, .04);
    }

    .petropd-pump-grid .pump-checkbox-item {
        display: inline-flex;
        align-items: center;
        width: 14.5%;
        min-width: 135px;
        margin: 6px 8px 6px 0;
        padding: 12px 20px;
        border: 1px solid #dce7f5;
        border-radius: 28px;
        background: linear-gradient(180deg, #f3f7ff 0%, #e9f0fb 100%);
        font-weight: 900;
        color: #173f92;
        box-shadow: 0 5px 13px rgba(28, 63, 115, .07);
        cursor: pointer;
    }

    .petropd-pump-grid input[type=checkbox] {
        margin-right: 12px;
        transform: scale(1.35);
        accent-color: #1f63d8;
        cursor: pointer;
    }

    .petropd-pump-grid .pump-checkbox-item span {
        font-size: 20px !important;
        line-height: 1;
    }

    .petropd-assign-help {
        display: block;
        margin: 14px 0 0;
        color: #174b9e !important;
        font-weight: 700;
        font-size: 17px !important;
    }

    .petropd-assign-pumps-modal .modal-footer {
        margin: 0 -34px;
        padding: 18px 34px 22px;
        background: rgba(232, 240, 252, .75);
        border-top: 1px solid #dbe7f5;
        display: flex;
        justify-content: flex-end;
        gap: 14px;
    }

    .petropd-assign-pumps-modal .btn {
        border-radius: 12px;
        min-width: 116px;
        min-height: 52px;
        font-size: 16px;
        font-weight: 900;
        box-shadow: 0 10px 20px rgba(15, 23, 42, .10);
    }

    .petropd-assign-pumps-modal .btn-primary {
        background: linear-gradient(135deg, #2f80ed 0%, #165bd8 100%);
        border-color: #165bd8;
    }

    .petropd-assign-pumps-modal .btn-default {
        color: #374151;
        background: #fff;
        border-color: #d7e2f0;
    }

    @media (max-width: 991px) {
        .petropd-assign-pumps-modal .modal-dialog {
            width: 95%;
        }
        .petropd-assign-header-row {
            align-items: flex-start;
            flex-direction: column;
        }
        .petropd-shift-badge {
            font-size: 24px;
        }
        .petropd-pump-grid .pump-checkbox-item {
            width: 30%;
        }
    }

    @media (max-width: 900px) {
        .petropd-open-shift-item {
            grid-template-columns: 1fr;
            row-gap: 4px;
        }
    }

    @media (max-width: 576px) {
        .petropd-assign-pumps-modal .modal-title {
            font-size: 24px !important;
        }
        .petropd-assign-icon {
            width: 48px;
            height: 48px;
            font-size: 30px;
        }
        .petropd-pump-grid .pump-checkbox-item {
            width: 100%;
        }
        .petropd-assign-pumps-modal .modal-footer {
            flex-direction: column;
        }
        .petropd-assign-pumps-modal .btn {
            width: 100%;
        }
        .swal-modal {
            width: 94vw;
        }
        .swal-content .table th,
        .swal-content .table td {
            display: block;
            width: 100% !important;
        }
    }
</style>

<div class="petropd-assign-pumps-modal"><div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' =>
        route('petropd.pump-assignments.store-bulk'), 'method' =>
        'post',
        'id' =>
        'receive_pump_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <div class="petropd-assign-header-row">
                <div class="petropd-assign-title-wrap">
                    <span class="petropd-assign-icon" aria-hidden="true">&#128167;</span>
                    <h4 class="modal-title">@lang( 'petropd::lang.assign_pumps' )</h4>
                </div>
                <span class="petropd-shift-badge">{{ __( 'petropd::lang.shift_number' ) }} : {{ sprintf("%04d", $shift_number + 1) }}</span>
            </div>
        </div>

        <div class="modal-body">
            @if($open_shift_assignments->count())
                <div class="petropd-section-card">
                    <h4 class="petropd-section-title"><strong>Shifts Not Closed</strong></h4>

                    <div class="petropd-open-shifts-list">
                        @foreach($open_shift_assignments->groupBy('shift_number') as $shift_no => $rows)
                            <div class="petropd-open-shift-item">
                                <div class="petropd-open-shift-detail">
                                    <strong>Shift No:</strong> {{ sprintf('%04d', $shift_no) }}
                                </div>
                                <div class="petropd-open-shift-detail">
                                    <strong>Pump Operator:</strong> {{ $rows->first()->pumper_name ?: '-' }}
                                </div>
                                <div class="petropd-open-shift-detail">
                                    <strong>Pumps Assigned:</strong>
                                    {{ $rows->pluck('pump_name')->filter()->implode(', ') ?: '-' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="petropd-form-card">
                <div class="row">
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('date', __( 'petropd::lang.date' ) . ':*') !!}
                                {!! Form::input('datetime-local', 'date', 
                                    \Carbon\Carbon::now()->format('Y-m-d\TH:i'), 
                                    ['class' => 'form-control', 'required', 'placeholder' => __('petropd::lang.please_select'), 'style' => 'width: 100%;']) !!}

                            </div>
                        </div>


                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('work_shift', __('petropd::lang.work_shift') . ':') !!}
                                {!! Form::select('work_shift', $work_shifts, null, [
                                    'class' => 'form-control select2',
                                    'placeholder' => __('petropd::lang.please_select'),
                                    'style' => 'width: 100%;',
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('pump_operator', __( 'petropd::lang.pump_operator' ) . ':*') !!}
                                @php
                                    /*
                                     * S282 final fix:
                                     * Use the operator list supplied by the PetroPD assignment controller.
                                     * Keep this field as a normal select so it always displays all rows from
                                     * pump_operators for the current business and is not blocked by Select2 AJAX.
                                     */
                                    $assign_pump_operators = collect($assign_pump_operators ?? []);

                                    if ($assign_pump_operators->isEmpty() && isset($available_pump_operators)) {
                                        $assign_pump_operators = $available_pump_operators instanceof \Illuminate\Support\Collection
                                            ? $available_pump_operators->pluck('name', 'id')
                                            : collect($available_pump_operators);
                                    }

                                    if ($assign_pump_operators->isEmpty() && isset($pump_operators)) {
                                        $assign_pump_operators = $pump_operators instanceof \Illuminate\Support\Collection
                                            ? $pump_operators->pluck('name', 'id')
                                            : collect($pump_operators);
                                    }
                                    // Tenant-safe final fallback: load directly from the current tenant database.
                                    if ($assign_pump_operators->isEmpty() && \Illuminate\Support\Facades\Schema::hasTable('pump_operators')) {
                                        $nameCol = \Illuminate\Support\Facades\Schema::hasColumn('pump_operators','name') ? 'name' : 'id';
                                        $assign_pump_operators = \Illuminate\Support\Facades\DB::table('pump_operators')
                                            ->when(\Illuminate\Support\Facades\Schema::hasColumn('pump_operators','deleted_at'), fn($q) => $q->whereNull('deleted_at'))
                                            ->orderBy($nameCol)
                                            ->get(['id', $nameCol . ' as name'])
                                            ->mapWithKeys(fn($r) => [$r->id => ($r->name ?: ('Operator '.$r->id))]);
                                    }
                                @endphp

                                @if($assign_pump_operators->isEmpty())
                                    <div class="alert alert-warning" style="margin-bottom:0;">All pump operators are already assigned to open shifts.</div>
                                @else
                                    {!! Form::select('pump_operator', $assign_pump_operators,
                                    null , ['class' => 'form-control', 'id' => 'pump_operator_selector','required',
                                    'placeholder' => __('petropd::lang.please_select'), 'style' => 'width: 100%;']); !!}
                                @endif
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('pump', __( 'petropd::lang.pump' ) . ':*') !!}
                                @php
                                    /*
                                     * S312 layout fix:
                                     * Pumps are loaded server-side and displayed as checkboxes.
                                     * This avoids the oversized browser multiselect dropdown and
                                     * keeps the modal aligned with the ERP form standards.
                                     */
                                    $pumps = collect($pumps ?? []);
                                    $selected_pump_ids = collect($selected_pump_ids ?? [])->map(fn($v) => (string) $v)->filter()->values()->all();
                                @endphp

                                <div id="pump-selector" class="petropd-pump-grid">
                                    @forelse($pumps as $pump_id => $pump_label)
                                        <label class="pump-checkbox-item">
                                            <input type="checkbox" name="pump[]" value="{{ $pump_id }}"
                                                {{ in_array((string) $pump_id, $selected_pump_ids, true) ? 'checked' : '' }}>
                                            <span style="font-size:120%;">{{ $pump_label }}</span>
                                        </label>
                                    @empty
                                        <div class="alert alert-warning" style="margin-bottom:0;">All pumps are already assigned to open shifts.</div>
                                    @endforelse
                                </div>
                                <small class="help-block petropd-assign-help">
                                    Select one or more pumps to assign.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary confirm_meter_reading_btn">@lang('messages.submit' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

   
    <script>
        $('.select2').not('#pump-selector').select2();
        
        var isUpdating = false; // Flag to prevent loop
        $(document).ready(function () {
            // Reset button state when modal is shown
            $('.modal').on('shown.bs.modal', function () {
                $('.confirm_meter_reading_btn').prop('disabled', false).text('@lang('messages.submit')');
            });

            // Intercept form submit for reconfirmation
            $('#receive_pump_form').on('submit', function(e) {
                if ($(this).data('confirmed') === true) {
                    $('.confirm_meter_reading_btn').prop('disabled', true).text('Processing...');
                    return true;
                }
                
                e.preventDefault();

                if ($('input[name="pump[]"]:checked').length === 0) {
                    toastr.error('Please select at least one pump.');
                    return false;
                }

                var dateVal = $('input[name="date"]').val();
                var operatorVal = $('#pump_operator_selector option:selected').text();
                var pumpsVal = [];
                $('input[name="pump[]"]:checked').each(function() {
                    pumpsVal.push($.trim($(this).closest('label').text()));
                });

                if (dateVal) {
                    var dt = new Date(dateVal);
                    if (!isNaN(dt.getTime())) {
                        var yyyy = dt.getFullYear();
                        var mm = String(dt.getMonth() + 1).padStart(2, '0');
                        var dd = String(dt.getDate()).padStart(2, '0');
                        var hh = String(dt.getHours()).padStart(2, '0');
                        var min = String(dt.getMinutes()).padStart(2, '0');
                        dateVal = yyyy + '-' + mm + '-' + dd + ' ' + hh + ':' + min;
                    }
                }

                var confirmationHtml =
                    '<div class="petropd-reconfirm-content">' +
                        '<p class="petropd-reconfirm-subtitle">Please confirm the pump assignment details:</p>' +
                        '<table class="petropd-reconfirm-table">' +
                            '<tr><th>Date &amp; Time:</th><td>' + (dateVal || 'N/A') + '</td></tr>' +
                            '<tr><th>Pump Operator:</th><td>' + (operatorVal || 'N/A') + '</td></tr>' +
                            '<tr><th>Assigned Pumps:</th><td>' + (pumpsVal.join(', ') || 'N/A') + '</td></tr>' +
                        '</table>' +
                        '<p class="petropd-reconfirm-question">Are you sure you want to assign these pumps?</p>' +
                    '</div>';

                swal({
                    title: 'Reconfirmation',
                    content: {
                        element: 'div',
                        attributes: {
                            innerHTML: confirmationHtml
                        }
                    },
                    icon: 'warning',
                    className: 'petropd-reconfirm-swal',
                    buttons: {
                        cancel: {
                            text: 'No',
                            visible: true,
                            value: false,
                            className: 'btn btn-danger'
                        },
                        confirm: {
                            text: 'Yes',
                            visible: true,
                            value: true,
                            className: 'btn btn-success'
                        }
                    },
                    dangerMode: false,
                }).then(function(confirmed) {
                    if (confirmed) {
                        var form = $('#receive_pump_form');
                        form.data('confirmed', true);
                        $('.confirm_meter_reading_btn').prop('disabled', true).text('Processing...');
                        form.submit();
                    }
                });

                return false;
            });

            $('#pump_operator_selector').change(function (e) {
                $('input[name="pump[]"]').prop('checked', false);
            });

            /*
             * IS1514 final fix:
             * Do not block the Pump dropdown in the modal. The previous client-side
             * assignment check could remove every selected pump and make the field
             * appear unusable. Server-side validation remains in storeBulk.
             */
        });
    </script>
