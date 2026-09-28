<div class="purchase-table-wrap">
    <table class="table table-bordered purchase-data-table" id="purchase_entries_table">
        <thead>
            <tr>
                <th class="purchase-col-action">Action</th>
                <th>Date</th>
                <th>Purchase No.</th>
                <th class="purchase-col-supplier-ref">Supplier Ref.</th>
                <th>Supplier</th>
                <th>Location / Store</th>
                <th>Status</th>
                <th class="purchase-col-payment-status">Payment<br>Status</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                @php
                    $canEdit = app(\Modules\Purchase\Utils\PurchaseAccessUtil::class)->canEdit();
                    $canDelete = app(\Modules\Purchase\Utils\PurchaseAccessUtil::class)->canDelete();
                    $canPrint = app(\Modules\Purchase\Utils\PurchaseAccessUtil::class)->canPrint();
                    $canAddPayment = app(\Modules\Purchase\Utils\PurchaseAccessUtil::class)->canAddPayments();
                    // Use the calculated balance, not only payment_status: legacy rows
                    // can have a stale status while their actual paid total is correct.
                    $hasPurchaseDue = (float) ($row->due_amount ?? 0) > 0.000001;
                @endphp
                <tr data-entry-id="{{ $row->id }}">
                    <td class="action-cell purchase-col-action">
                        {{-- IS2145: the standalone View button removed. View is already
                             the first item inside the Action menu below, so this was a
                             duplicate that widened the column and put one action outside
                             the dropdown while every other action sat inside it. --}}
                        <div class="btn-group purchase-action-group">
                            <button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Action <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="{{ route('purchase.entries.show', $row->id) }}"><i class="fa fa-eye"></i> View</a></li>
                                @if($canPrint)
                                    <li><a href="{{ route('purchase.entries.print', $row->id) }}" target="_blank" rel="noopener"><i class="fa fa-print"></i> Print</a></li>
                                @endif
                                @if($canAddPayment && $hasPurchaseDue)
                                    <li>
                                        <a href="{{ route('purchase.entries.payments.create', $row->id) }}">
                                            <i class="fa fa-money text-green"></i> Add Payment
                                        </a>
                                    </li>
                                @endif
                                @if($canEdit)
                                    <li><a href="{{ route('purchase.entries.edit', $row->id) }}"><i class="fa fa-edit"></i> Edit</a></li>
                                @endif
                                @if($canDelete)
                                    <li role="separator" class="divider"></li>
                                    <li><a href="#" class="purchase-entry-delete" data-entry-id="{{ $row->id }}" data-url="{{ route('purchase.entries.destroy', $row->id) }}" data-token="{{ csrf_token() }}"><i class="fa fa-trash text-red"></i> Delete</a></li>
                                @endif
                            </ul>
                        </div>
                    </td>
                    <td>{{ !empty($row->transaction_date) ? \Carbon\Carbon::parse($row->transaction_date)->format('d/m/Y H:i') : '' }}</td>
                    <td>{{ $row->invoice_no ?: ('PUR-'.$row->id) }}</td>
                    <td class="purchase-col-supplier-ref">{{ $row->ref_no ?: '-' }}</td>
                    <td>{{ $row->supplier_name ?: '-' }}</td>
                    <td>{{ $row->location_name ?: '-' }}{{ $row->store_name ? ' / '.$row->store_name : '' }}</td>
                    <td class="text-center"><span class="status-label status-{{ strtolower((string)$row->status) }}">{{ $row->status ?: '-' }}</span></td>
                    <td class="text-center purchase-col-payment-status"><span class="status-label status-{{ strtolower((string)$row->payment_status) }}">{{ $row->payment_status ?: 'due' }}</span></td>
                    <td class="amount">{{ number_format((float)$row->final_total, 2) }}</td>
                    <td class="amount">{{ number_format((float)$row->paid_amount, 2) }}</td>
                    <td class="amount">{{ number_format((float)$row->due_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="purchase-empty-state">No purchase entries were found for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
