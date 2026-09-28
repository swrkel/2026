@extends('pumperdashboardnew::layouts.operator')
@section('title', 'Pump Operator Dashboard - New')
@section('body_attributes', 'class="pone-legacy-dashboard-body"')
@section('pone_content')
@php
    $hasShift = !empty($shift);
    $isShiftOpen = $hasShift && in_array($shift->status, ['open', 'closing'], true);
    $receiveEnabled = $isShiftOpen && ($assigned_pumps > 0 || $unconfirmed_pumps > 0);
    $paymentEnabled = $isShiftOpen;
    $otherSalesEnabled = $isShiftOpen && $unconfirmed_pumps === 0;
    $listOtherSalesEnabled = $isShiftOpen;
    $closePumpEnabled = $isShiftOpen && $open_confirmed_pumps > 0;
    $closeShiftEnabled = $isShiftOpen && $can_close_shift;
    $summaryEnabled = $hasShift;
    $dayEntriesEnabled = $isShiftOpen && $unconfirmed_pumps === 0;
    $unloadEnabled = $isShiftOpen;

    $tile = static function (bool $enabled, string $enabledUrl, string $message): array {
        return [
            'url' => $enabled ? $enabledUrl : '#',
            'class' => $enabled ? '' : ' is-disabled',
            'message' => $enabled ? '' : $message,
        ];
    };

    $receive = $tile($receiveEnabled, route('pumper-dashboard-new.operator.pumps.index', ['mode' => 'receive']), $hasShift ? 'No pump is waiting to be received.' : 'No active shift is available.');
    $payments = $tile($paymentEnabled, route('pumper-dashboard-new.operator.payments.index'), 'Payments are unavailable because the shift is closed or no active shift is available.');
    $otherSales = $tile($otherSalesEnabled, route('pumper-dashboard-new.operator.other-sales.create'), $unconfirmed_pumps > 0 ? 'Receive and confirm all assigned pumps before entering Other Sales.' : 'Other Sales are unavailable because the shift is closed.');
    $otherSalesList = $tile($listOtherSalesEnabled, route('pumper-dashboard-new.operator.other-sales.index'), 'The Other Sales list is unavailable because the shift is closed or no shift is active.');
    $closePump = $tile($closePumpEnabled, route('pumper-dashboard-new.operator.pumps.index', ['mode' => 'close']), $unconfirmed_pumps > 0 ? 'Receive and confirm the pump before closing it.' : 'There is no open pump available to close.');
    $closeShift = $tile($closeShiftEnabled, route('pumper-dashboard-new.operator.shift.close.form'), $open_pumps > 0 ? 'Close all assigned pumps before closing the shift.' : 'The shift cannot be closed at this time.');
    $paymentSummary = $tile($summaryEnabled, route('pumper-dashboard-new.operator.payments.summary'), 'No shift is available for the payment summary.');
    $unloadDetails = $tile($summaryEnabled, route('pumper-dashboard-new.operator.unload-stock.index'), 'No shift is available for unload details.');
    $metersWithPayments = $tile($summaryEnabled, route('pumper-dashboard-new.operator.reports.meters-with-payments'), 'No shift is available for this report.');
    $dayEntries = $tile($dayEntriesEnabled, route('pumper-dashboard-new.operator.day-entries.index'), $unconfirmed_pumps > 0 ? 'Receive and confirm all assigned pumps before opening Day Entries.' : 'Day Entries are unavailable because the shift is closed.');
    $currentMeter = $tile($closePumpEnabled, route('pumper-dashboard-new.operator.pumps.index', ['mode' => 'current']), $unconfirmed_pumps > 0 ? 'Receive and confirm the pump before entering a current meter.' : 'There is no open pump available for a current-meter entry.');
    $unloadStock = $tile($unloadEnabled, route('pumper-dashboard-new.operator.unload-stock.create'), 'Unload Stock is unavailable because the shift is closed or no shift is active.');
@endphp

<section class="pone-legacy-dashboard" aria-label="Pump Operator Dashboard-New">
    <header class="pone-legacy-dashboard-header">
        <div class="pone-legacy-dashboard-heading">
            <h1>Pump Operator Dashboard - New</h1>
            <h2>Shift NO: {{ $shift_number ?: '-' }}</h2>
            <p><span aria-hidden="true">&#9786;</span> {{ $time_greeting }}, {{ $operator_first_name }}!</p>
        </div>

        <div class="pone-legacy-dashboard-header-right">
            <div class="pone-legacy-dashboard-clock" data-pone-clock data-timezone="Asia/Colombo">
                <span><strong>Today:</strong> <span data-pone-clock-date>{{ $dashboard_now->format('m-d-Y') }}</span></span>
                <span><strong>Time:</strong> <span data-pone-clock-time>{{ $dashboard_now->format('H:i:s') }}</span></span>
            </div>

            <nav class="pone-legacy-dashboard-actions" aria-label="Dashboard actions">
                <button type="button" class="pone-legacy-top-button pone-legacy-top-fullscreen" data-pone-fullscreen title="Fullscreen" aria-label="Fullscreen">&#9974;</button>

                <form method="post" action="{{ route('pumper-dashboard-new.operator.logout') }}">
                    @csrf
                    <input type="hidden" name="main_system" value="1">
                    <button type="submit" class="pone-legacy-top-button pone-legacy-top-main"><span aria-hidden="true">&#128187;</span> Main System</button>
                </form>

                <a class="pone-legacy-top-button pone-legacy-top-passcode" href="{{ route('pumper-dashboard-new.operator.settings.passcode.edit') }}"><span aria-hidden="true">&#128274;</span> Update Passcode</a>

                <form method="post" action="{{ route('pumper-dashboard-new.operator.logout') }}">
                    @csrf
                    <button type="submit" class="pone-legacy-top-button pone-legacy-top-logout"><span aria-hidden="true">&#10162;</span> Logout</button>
                </form>
            </nav>
        </div>
    </header>

    @if (!$hasShift)
        <div class="pone-legacy-dashboard-notice pone-legacy-dashboard-notice-warning">
            No active Pumper Dashboard-New shift is assigned to this operator. Ask the administrator to create or assign a shift.
        </div>
    @elseif ($unconfirmed_pumps > 0)
        <div class="pone-legacy-dashboard-notice">
            {{ $unconfirmed_pumps }} assigned pump(s) still require receiving or confirmation.
        </div>
    @endif

    <div class="pone-legacy-dashboard-grid">
        <a id="pone-receive-pump" class="pone-legacy-dashboard-card pone-card-orange{{ $receive['class'] }}" href="{{ $receive['url'] }}" data-disabled-message="{{ $receive['message'] }}">
            <span class="pone-legacy-card-icon" aria-hidden="true">&#9981;</span><span class="pone-legacy-card-label">Receive Pump</span>
        </a>

        <a id="pone-payments" class="pone-legacy-dashboard-card pone-card-gray{{ $payments['class'] }}" href="{{ $payments['url'] }}" data-disabled-message="{{ $payments['message'] }}">
            <span class="pone-legacy-card-icon" aria-hidden="true">&#36;</span><span class="pone-legacy-card-label">Payments</span>
        </a>

        <a id="pone-other-sales" class="pone-legacy-dashboard-card pone-card-light-gray{{ $otherSales['class'] }}" href="{{ $otherSales['url'] }}" data-disabled-message="{{ $otherSales['message'] }}">
            <span class="pone-legacy-card-icon" aria-hidden="true">&#128722;</span><span class="pone-legacy-card-label">Other Sales</span>
        </a>

        <a id="pone-list-other-sales" class="pone-legacy-dashboard-card pone-card-purple{{ $otherSalesList['class'] }}" href="{{ $otherSalesList['url'] }}" data-disabled-message="{{ $otherSalesList['message'] }}">
            <span class="pone-legacy-card-icon" aria-hidden="true">&#9776;</span><span class="pone-legacy-card-label">List Other Sales</span>
        </a>

        <a id="pone-close-pump" class="pone-legacy-dashboard-card pone-card-green{{ $closePump['class'] }}" href="{{ $closePump['url'] }}" data-disabled-message="{{ $closePump['message'] }}">
            <span class="pone-legacy-card-icon" aria-hidden="true">&#128274;</span><span class="pone-legacy-card-label">Close Pump</span><small>Tap here to select pump</small>
        </a>

        <a id="pone-close-shift" class="pone-legacy-dashboard-card pone-card-yellow{{ $closeShift['class'] }}" href="{{ $closeShift['url'] }}" data-disabled-message="{{ $closeShift['message'] }}">
            <span class="pone-legacy-card-icon" aria-hidden="true">&#9211;</span><span class="pone-legacy-card-label">Close Shift</span>
        </a>

        <a id="pone-payment-summary" class="pone-legacy-dashboard-card pone-card-dark-gray{{ $paymentSummary['class'] }}" href="{{ $paymentSummary['url'] }}" data-disabled-message="{{ $paymentSummary['message'] }}">
            <span class="pone-legacy-card-icon" aria-hidden="true">&#128196;</span><span class="pone-legacy-card-label">Payment Summary</span>
        </a>

        <a id="pone-unload-details" class="pone-legacy-dashboard-card pone-card-deep-orange{{ $unloadDetails['class'] }}" href="{{ $unloadDetails['url'] }}" data-disabled-message="{{ $unloadDetails['message'] }}">
            <span class="pone-legacy-card-icon" aria-hidden="true">&#8801;</span><span class="pone-legacy-card-label">Unload Stock Details</span>
        </a>

        <a id="pone-meters-payments" class="pone-legacy-dashboard-card pone-card-purple{{ $metersWithPayments['class'] }}" href="{{ $metersWithPayments['url'] }}" data-disabled-message="{{ $metersWithPayments['message'] }}">
            <span class="pone-legacy-card-icon" aria-hidden="true">&#9638;</span><span class="pone-legacy-card-label">Meters With Payments</span>
        </a>

        <a id="pone-day-entries" class="pone-legacy-dashboard-card pone-card-blue{{ $dayEntries['class'] }}" href="{{ $dayEntries['url'] }}" data-disabled-message="{{ $dayEntries['message'] }}">
            <span class="pone-legacy-card-icon" aria-hidden="true">&#128197;</span><span class="pone-legacy-card-label">Day Entries</span>
        </a>

        <a id="pone-current-meter" class="pone-legacy-dashboard-card pone-card-red{{ $currentMeter['class'] }}" href="{{ $currentMeter['url'] }}" data-disabled-message="{{ $currentMeter['message'] }}">
            <span class="pone-legacy-card-icon" aria-hidden="true">&#9719;</span><span class="pone-legacy-card-label">Enter Current Meter</span>
        </a>

        <a id="pone-unload-stock" class="pone-legacy-dashboard-card pone-card-deep-blue{{ $unloadStock['class'] }}" href="{{ $unloadStock['url'] }}" data-disabled-message="{{ $unloadStock['message'] }}">
            <span class="pone-legacy-card-icon" aria-hidden="true">&#128666;</span><span class="pone-legacy-card-label">Unload Stock</span>
        </a>
    </div>

    <footer class="pone-legacy-dashboard-footer">
        {{ $business->name ?? 'Business' }} · {{ $location->name ?? 'Location' }} · Pump Operator Display 2
    </footer>
</section>
@endsection
