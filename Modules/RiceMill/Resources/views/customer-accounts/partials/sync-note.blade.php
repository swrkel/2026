@if(!$coreReady)
    <div class="rcm-alert rcm-alert-danger">
        <i class="fa fa-exclamation-triangle"></i>
        <span>The ERP customer receivable/payment tables are not available in this tenant. Rice Mill customer-account payment functions are therefore disabled.</span>
    </div>
@elseif(!empty($syncPendingCount))
    <div class="rcm-alert rcm-alert-info">
        <i class="fa fa-info-circle"></i>
        <span>{{ $syncPendingCount }} approved Rice Mill Sales Invoice{{ $syncPendingCount===1?' is':'s are' }} waiting for Finance synchronization. They remain visible here, but payments can only be allocated after the matching ERP receivable transaction exists.</span>
    </div>
@endif
