<div class="card">
  <div class="card-header"><strong>Ledger</strong></div>
  <div class="card-body" style="overflow:auto;">
    <table class="table"><thead><tr><th>Date</th><th>Type</th><th>Reference</th><th>Note</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Balance</th></tr></thead><tbody>
      @forelse($rows as $r)
      <tr><td>{{ $r->transaction_date }}</td><td>{{ ucwords(str_replace('_',' ', $r->entry_type)) }}</td><td>{{ $r->reference_no }}</td><td>{{ $r->note }}</td><td class="text-right">{{ number_format($r->debit,2) }}</td><td class="text-right">{{ number_format($r->credit,2) }}</td><td class="text-right">{{ number_format($r->balance,2) }}</td></tr>
      @empty <tr><td colspan="7" class="text-center">No ledger entries found.</td></tr>@endforelse
    </tbody></table>
    <div>{{ method_exists($rows,'links') ? $rows->links() : '' }}</div>
  </div>
</div>
