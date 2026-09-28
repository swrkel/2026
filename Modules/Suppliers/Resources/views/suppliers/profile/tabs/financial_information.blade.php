@if(!empty($standaloneTab))
    <div class="supplier-profile-panel supplier-standalone-tab">
@endif
<h4>@lang('suppliers::lang.financial_information')</h4>
<div class="row">
    <div class="col-md-3"><strong>@lang('suppliers::lang.pay_term_number')</strong><br>{{ $financial['pay_term_number'] ?? '-' }}</div>
    <div class="col-md-3"><strong>@lang('suppliers::lang.pay_term_type')</strong><br>{{ $financial['pay_term_type'] ?? '-' }}</div>
    <div class="col-md-3 text-right"><strong>@lang('suppliers::lang.credit_limit')</strong><br>{{ number_format((float)($financial['credit_limit'] ?? 0), 2) }}</div>
    <div class="col-md-3 text-right"><strong>@lang('suppliers::lang.opening_balance')</strong><br>{{ number_format((float)($financial['opening_balance'] ?? 0), 2) }}</div>
</div>
@if(!empty($standaloneTab))
    </div>
@endif
