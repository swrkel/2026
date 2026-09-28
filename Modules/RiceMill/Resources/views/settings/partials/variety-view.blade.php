<dl class="rcm-detail-list rcm-detail-list-wide">
    <dt>Code</dt><dd>{{ $variety->code }}</dd><dt>Paddy Variety</dt><dd>{{ $variety->name }}</dd>
    <dt>Moisture %</dt><dd>{{ $variety->default_moisture_percent !== null ? number_format($variety->default_moisture_percent,3) : '-' }}</dd><dt>Foreign Matter %</dt><dd>{{ $variety->foreign_matter_limit_percent !== null ? number_format($variety->foreign_matter_limit_percent,3) : '-' }}</dd>
    <dt>Expected Rice Yield %</dt><dd>{{ $variety->expected_rice_yield_percent !== null ? number_format($variety->expected_rice_yield_percent,3) : '-' }}</dd><dt>Expected Broken Rice %</dt><dd>{{ $variety->expected_broken_rice_percent !== null ? number_format($variety->expected_broken_rice_percent,3) : '-' }}</dd>
    <dt>Expected Bran %</dt><dd>{{ $variety->expected_bran_percent !== null ? number_format($variety->expected_bran_percent,3) : '-' }}</dd><dt>Expected Husk %</dt><dd>{{ $variety->expected_husk_percent !== null ? number_format($variety->expected_husk_percent,3) : '-' }}</dd>
    <dt>Expected Process Loss %</dt><dd>{{ $variety->expected_process_loss_percent !== null ? number_format($variety->expected_process_loss_percent,3) : '-' }}</dd><dt>Quality Grade</dt><dd>{{ $variety->quality_grade ?: '-' }}</dd>
    <dt>Stock Lot Prefix</dt><dd>{{ $variety->lot_sequence_prefix }}</dd><dt>Opening Number</dt><dd>{{ number_format($variety->lot_opening_number ?: 1,0) }}</dd>
    <dt>Next Number</dt><dd>{{ number_format($variety->lot_next_number,0) }}</dd><dt>Next Stock Lot</dt><dd>{{ $variety->lot_next_preview }}</dd>
    <dt>Status</dt><dd>{{ $variety->active ? 'Enabled' : 'Disabled' }}</dd>
</dl>
