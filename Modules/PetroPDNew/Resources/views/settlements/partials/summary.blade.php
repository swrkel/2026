@php
    $classifiedShortage = (float) $settlement->source_shortage_total + (float) $settlement->manual_shortage_total;
    $classifiedExcess = (float) $settlement->source_excess_total + (float) $settlement->manual_excess_total;
    $accountedTotal = (float) $settlement->received_total + $classifiedShortage - $classifiedExcess;
@endphp
<div class="pdn-card {{ $sourceMatches ? 'pdn-source-ok' : 'pdn-source-bad' }}">
    <div class="pdn-summary">
        <div class="pdn-summary-item"><span>Status</span><strong><span class="pdn-badge {{ $settlement->status }}">{{ $settlement->status }}</span></strong></div>
        <div class="pdn-summary-item"><span>Reconciliation</span><strong><span class="pdn-badge {{ $settlement->reconciliation_status }}">{{ $settlement->reconciliation_status }}</span></strong></div>
        <div class="pdn-summary-item"><span>Meter Sales</span><strong>{{ number_format((float)$settlement->meter_sales_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Other Sales</span><strong>{{ number_format((float)$settlement->other_sales_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Expected Adjustments</span><strong>{{ number_format((float)$settlement->expected_adjustments_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Expected Total</span><strong>{{ number_format((float)$settlement->expected_total,4) }}</strong></div>

        <div class="pdn-summary-item"><span>PONE Declared Total</span><strong>{{ number_format((float)$settlement->source_declared_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Source Collections</span><strong>{{ number_format((float)$settlement->source_payments_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Manual Collections</span><strong>{{ number_format((float)$settlement->manual_payments_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Received Adjustments</span><strong>{{ number_format((float)$settlement->received_adjustments_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Normal Received Total</span><strong>{{ number_format((float)$settlement->received_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Operational Difference</span><strong class="{{ abs((float)$settlement->operational_variance_amount) > 0.00005 ? 'pdn-text-danger' : 'pdn-text-success' }}">{{ number_format((float)$settlement->operational_variance_amount,4) }}</strong></div>

        <div class="pdn-summary-item"><span>PONE Shortage</span><strong class="{{ (float)$settlement->source_shortage_total > 0.00005 ? 'pdn-text-danger' : '' }}">{{ number_format((float)$settlement->source_shortage_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Manual Shortage</span><strong class="{{ (float)$settlement->manual_shortage_total > 0.00005 ? 'pdn-text-danger' : '' }}">{{ number_format((float)$settlement->manual_shortage_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>PONE Excess</span><strong class="{{ (float)$settlement->source_excess_total > 0.00005 ? 'pdn-text-success' : '' }}">{{ number_format((float)$settlement->source_excess_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Manual Excess</span><strong class="{{ (float)$settlement->manual_excess_total > 0.00005 ? 'pdn-text-success' : '' }}">{{ number_format((float)$settlement->manual_excess_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Accounted Total</span><strong>{{ number_format($accountedTotal,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Unresolved Variance</span><strong class="{{ abs((float)$settlement->variance_amount) > 0.00005 ? 'pdn-text-danger' : 'pdn-text-success' }}">{{ number_format((float)$settlement->variance_amount,4) }}</strong></div>

        <div class="pdn-summary-item"><span>Shortage Recovered</span><strong>{{ number_format((float)$settlement->shortage_recovery_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>Excess Commission</span><strong>{{ number_format((float)$settlement->excess_commission_total,4) }}</strong></div>
        <div class="pdn-summary-item"><span>PONE Closed At</span><strong>{{ optional($settlement->source_closed_at)->format('d M Y H:i') }}</strong></div>
        <div class="pdn-summary-item"><span>Source Hash</span><strong class="pdn-mono">{{ substr((string)$settlement->source_hash,0,12) }}…</strong></div>
    </div>
    <div class="pdn-alert info" style="margin-top:12px">
        Shortage and excess remain separate operational classifications and are never counted as normal collections.
    </div>
    @if($settlement->notes)<div class="pdn-note"><strong>Notes:</strong> {!! nl2br(e($settlement->notes)) !!}</div>@endif
</div>
