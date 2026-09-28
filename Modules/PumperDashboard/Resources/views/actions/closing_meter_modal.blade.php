@include('pumperdashboard::partials.pumper_dashboard_ui_standard')
@php
    $print_ready = !empty($all_pumps_closed) && !empty($shift_id);
@endphp
<style>
    .pumper-close-pump-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        padding-right: 32px;
    }
    .pumper-close-pump-modal-title {
        margin: 0 0 6px;
        font-weight: 700;
    }
    .pumper-close-pump-shift {
        color: #d32f2f;
        font-size: 23px;
        font-weight: 700;
        white-space: nowrap;
    }
    .pumper-close-pump-print-panel {
        min-width: 255px;
        padding: 10px 12px;
        border: 1px solid #d7e2ef;
        border-radius: 9px;
        background: #f8fbff;
        text-align: center;
        box-shadow: 0 2px 8px rgba(13, 71, 161, 0.08);
    }
    .pumper-close-pump-print-status {
        display: block;
        margin-bottom: 8px;
        font-size: 16px;
        font-weight: 700;
    }
    .pumper-close-pump-print-status.is-ready { color: #1b5e20; }
    .pumper-close-pump-print-status.is-pending { color: #b26a00; }
    .pumper-close-pump-print-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 42px;
        width: 100%;
        padding: 9px 13px;
        border: 0;
        border-radius: 7px;
        background: #1976d2;
        color: #fff !important;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none !important;
        box-shadow: 0 2px 6px rgba(25, 118, 210, 0.25);
    }
    .pumper-close-pump-print-button:hover,
    .pumper-close-pump-print-button:focus {
        background: #0d5eaf;
        color: #fff !important;
    }
    .pumper-close-pump-print-button[disabled] {
        background: #c7ced6;
        color: #6c757d !important;
        cursor: not-allowed;
        box-shadow: none;
    }
    .pumper-close-pump-touch-card {
        min-height: 190px !important;
        padding: 14px 10px !important;
        border-radius: 10px !important;
        white-space: normal !important;
        display: block !important;
        touch-action: manipulation;
    }
    .pumper-close-pump-touch-card h2 {
        margin-top: 12px !important;
        margin-bottom: 8px !important;
        font-size: 34px !important;
        font-weight: 700 !important;
    }
    .pumper-close-pump-touch-card h4 {
        min-height: 38px;
        margin-bottom: 12px !important;
        font-size: 17px !important;
        line-height: 20px !important;
        white-space: normal !important;
    }
    .pumper-close-pump-touch-action {
        display: inline-block;
        margin-top: 10px;
        padding: 12px 18px;
        min-width: 150px;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.95);
        color: #1B5E20;
        font-size: 17px;
        font-weight: 700;
        line-height: 1.2;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.20);
    }
    .pumper-close-pump-touch-action i {
        margin-right: 6px;
        font-size: 20px;
    }
    .pumper-close-pump-touch-card:hover .pumper-close-pump-touch-action,
    .pumper-close-pump-touch-card:focus .pumper-close-pump-touch-action {
        background: #ffffff;
        color: #0B3D12;
    }
    @media (max-width: 991px) {
        .pumper-close-pump-card-wrap { width: 46% !important; }
        .pumper-close-pump-modal-header { flex-direction: column; }
        .pumper-close-pump-print-panel { width: 100%; min-width: 0; }
    }
    @media (max-width: 767px) {
        .pumper-close-pump-card-wrap { width: 95% !important; }
    }
</style>
<div class="modal-dialog pumper-ui-standard" role="document" style="width: 72%; max-width: 1050px;">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>

            <div class="pumper-close-pump-modal-header">
                <div>
                    <h4 class="modal-title pumper-close-pump-modal-title">
                        @lang('pumperdashboard::lang.closing_meter')
                    </h4>
                    <div class="pumper-close-pump-shift">
                        @lang('pumperdashboard::lang.shift_number') :
                        {{ !empty($shift_number) ? sprintf('%04d', $shift_number) : '-' }}
                    </div>
                </div>

                <div class="pumper-close-pump-print-panel">
                    @if($print_ready)
                        <span class="pumper-close-pump-print-status is-ready">
                            <i class="fa fa-check-circle"></i> Total Pumps Are Closed
                        </span>
                        <a class="pumper-close-pump-print-button"
                           href="{{ route('pumperdashboard.closed-pumps-statement', ['shift_id' => $shift_id]) }}"
                           target="_blank"
                           rel="noopener">
                            <i class="fa fa-print"></i> Click here to Get the Print
                        </a>
                    @else
                        <span class="pumper-close-pump-print-status is-pending">
                            <i class="fa fa-clock-o"></i> Close all pumps to enable printing
                        </span>
                        <button type="button" class="pumper-close-pump-print-button" disabled>
                            <i class="fa fa-print"></i> Click here to Get the Print
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    @forelse ($pumps as $pump)
                        @if(in_array(strtolower((string) $pump->status), ['close', 'closed'], true))
                            <div class="col-md-3 text-center pumper-close-pump-card-wrap" style="padding: 0; margin: 10px;">
                                <a class="btn btn-primary btn-flat closed-pump-card pumper-close-pump-touch-card"
                                   href="{{ action('\\Modules\\PumperDashboard\\Http\\Controllers\\ClosingShiftController@show', $pump->assignment_id) }}"
                                   style="height: 190px; width:100%; background: #800080; border: 0; text-align: center !important; margin: 0 10px;">
                                    <span class="label label-danger" style="font-size: 17px;">
                                        @lang('pumperdashboard::lang.closed')
                                    </span>
                                    <h2>{{ $pump->pump_no }}</h2>
                                    <h4>{{ $pump->pumper_name }}</h4>
                                </a>
                            </div>
                        @elseif((int) $pump->is_confirmed === 0)
                            <div class="col-md-3 text-center pumper-close-pump-card-wrap" style="padding: 0; margin: 10px;">
                                <a class="btn btn-primary btn-flat closed-pump-card pumper-close-pump-touch-card"
                                   href="#"
                                   style="height: 190px; width:100%; background: #F9A825; border: 0; text-align: center !important; margin: 0 10px;">
                                    <span class="label label-danger" style="font-size: 17px;">
                                        Pending @lang('pumperdashboard::lang.receive_pump')
                                    </span>
                                    <h2>{{ $pump->pump_no }}</h2>
                                    <h4>{{ $pump->pumper_name }}</h4>
                                </a>
                            </div>
                        @else
                            <div class="col-md-3 text-center pumper-close-pump-card-wrap" style="padding: 0; margin: 10px;">
                                <a class="btn btn-primary btn-flat closed-pump-card pumper-close-pump-touch-card"
                                   style="height: 190px; width:100%; background: #2E7D32; border: 0; margin: 0 10px;"
                                   href="{{ route('pumperdashboard.close-pump.show', ['pump_id' => (int) $pump->pump_id, 'route_assignment_id' => (int) $pump->assignment_id]) }}">
                                    <h2>{{ $pump->pump_no }}</h2>
                                    <h4>{{ $pump->pumper_name }}</h4>
                                    <span class="pumper-close-pump-touch-action">
                                        <i class="fa fa-hand-pointer-o"></i> @lang('pumperdashboard::lang.close_pump')
                                    </span>
                                </a>
                            </div>
                        @endif
                    @empty
                        <div class="col-md-12">
                            <div class="alert alert-info text-center" style="font-size: 16px; margin-bottom: 0;">
                                No pending pump assignments were found.
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="clearfix"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
