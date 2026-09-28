<div class="supplier-profile-panel">
    <h4>@lang('suppliers::lang.purchase_history')</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>@lang('suppliers::lang.date')</th><th>@lang('suppliers::lang.reference_no')</th><th>@lang('suppliers::lang.status')</th><th>@lang('suppliers::lang.payment_status')</th><th class="text-right">@lang('suppliers::lang.amount')</th></tr></thead>
            <tbody>
                @forelse($recentPurchases ?? [] as $purchase)
                    <tr><td>{{ $purchase['transaction_date'] ?? '-' }}</td><td>{{ $purchase['ref_no'] ?? '-' }}</td><td>{{ $purchase['status'] ?? '-' }}</td><td>{{ $purchase['payment_status'] ?? '-' }}</td><td class="text-right">{{ number_format((float)($purchase['final_total'] ?? 0), 2) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">@lang('suppliers::lang.no_records_found')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
