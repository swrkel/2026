<div class="purchase-table-wrap">
    <table class="table table-bordered purchase-data-table" id="purchase_returns_table">
        <thead>
            <tr>
                <th>Action</th>
                <th>Return Date</th>
                <th>Return No.</th>
                <th>Original Purchase</th>
                <th>Supplier</th>
                <th>Location / Store</th>
                <th>Status</th>
                <th>Return Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr data-return-id="{{ $row->id }}">
                    <td class="action-cell">
                        <div class="btn-group">
                            <button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown">Action <span class="caret"></span></button>
                            <ul class="dropdown-menu">
                                <li><a href="{{ route('purchase.returns.show', $row->id) }}"><i class="fa fa-eye"></i> View</a></li>
                                @if(app(\Modules\Purchase\Utils\PurchaseAccessUtil::class)->canDeleteReturns())
                                    <li role="separator" class="divider"></li>
                                    <li><a href="#" class="purchase-return-delete" data-url="{{ route('purchase.returns.destroy', $row->id) }}"><i class="fa fa-trash text-red"></i> Delete</a></li>
                                @endif
                            </ul>
                        </div>
                    </td>
                    <td>{{ !empty($row->transaction_date) ? \Carbon\Carbon::parse($row->transaction_date)->format('d/m/Y H:i') : '' }}</td>
                    <td>{{ $row->ref_no ?: $row->invoice_no ?: ('PR-'.$row->id) }}</td>
                    <td>{{ $row->parent_purchase_no ?: '-' }}</td>
                    <td>{{ $row->supplier_name ?: '-' }}</td>
                    <td>{{ $row->location_name ?: '-' }}{{ $row->store_name ? ' / '.$row->store_name : '' }}</td>
                    <td class="text-center"><span class="status-label status-{{ strtolower((string)$row->status) }}">{{ $row->status ?: 'final' }}</span></td>
                    <td class="amount">{{ number_format((float)$row->final_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="purchase-empty-state">No purchase return entries were found for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
