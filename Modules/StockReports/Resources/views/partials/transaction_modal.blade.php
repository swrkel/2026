@php
    $type_labels = [
        'sell' => __('sale.sale'),
        'purchase' => __('purchase.purchase'),
        'Stock_transfer' => __('lang_v1.stock_transfer'),
        'sell_return' => __('lang_v1.sell_return'),
        'purchase_return' => __('lang_v1.purchase_return'),
        'stock_adjustment' => __('stock_adjustment.stock_adjustment'),
        'purchase_transfer' => __('lang_v1.purchase_transfer'),
        'sell_transfer' => __('lang_v1.sell_transfer'),
        'production_purchase' => 'Production Purchase',
        'production_sell' => 'Production Sell',
        'opening_stock' => __('lang_v1.opening_stock'),
    ];
    $type_label = $type_labels[$transaction->type] ?? ucfirst(str_replace('_', ' ', $transaction->type));
    $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
@endphp
<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                {{ $type_label }}
                @if($transaction->ref_no)
                    <small>({{ $transaction->ref_no }})</small>
                @endif
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-striped">
                        <tr>
                            <th>@lang('messages.date'):</th>
                            <td>{{ @format_datetime($transaction->transaction_date) }}</td>
                        </tr>
                        <tr>
                            <th>@lang('sale.type'):</th>
                            <td>{{ $type_label }}</td>
                        </tr>
                        <tr>
                            <th>@lang('messages.status'):</th>
                            <td><span class="label label-{{ $transaction->status == 'final' ? 'success' : ($transaction->status == 'draft' ? 'warning' : 'info') }}">{{ ucfirst($transaction->status) }}</span></td>
                        </tr>
                        <tr>
                            <th>@lang('purchase.ref_no'):</th>
                            <td>{{ $transaction->ref_no }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-striped">
                        <tr>
                            <th>Location:</th>
                            <td>{{ $transaction->location->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Store:</th>
                            <td>{{ $transaction->store->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Contact:</th>
                            <td>{{ $transaction->contact->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Final Total:</th>
                            <td>{{ number_format($transaction->final_total, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <h4>Transaction Lines</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @if(in_array($transaction->type, ['purchase', 'purchase_transfer', 'opening_stock', 'production_purchase']))
                        @forelse($transaction->purchase_lines as $line)
                        <tr>
                            <td>{{ $line->product->name ?? 'N/A' }}</td>
                            <td>{{ $line->quantity }}</td>
                            <td>{{ number_format($line->unit_cost, 2) }}</td>
                            <td>{{ number_format($line->quantity * $line->unit_cost, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No lines found</td>
                        </tr>
                        @endforelse
                    @elseif(in_array($transaction->type, ['sell', 'sell_transfer', 'sell_return', 'production_sell']))
                        @forelse($transaction->sell_lines as $line)
                        <tr>
                            <td>{{ $line->product->name ?? 'N/A' }}</td>
                            <td>{{ $line->quantity }}</td>
                            <td>{{ number_format($line->unit_price, 2) }}</td>
                            <td>{{ number_format($line->quantity * $line->unit_price, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No lines found</td>
                        </tr>
                        @endforelse
                    @elseif($transaction->type == 'stock_adjustment')
                        @forelse($transaction->stock_adjustment_lines as $line)
                        <tr>
                            <td>{{ $line->product->name ?? 'N/A' }}</td>
                            <td>
                                @if($line->stock_adjustment_type == 'decrease')
                                    <span class="text-danger">-{{ $line->quantity }}</span>
                                @else
                                    <span class="text-success">+{{ $line->quantity }}</span>
                                @endif
                                ({{ ucfirst($line->stock_adjustment_type) }})
                            </td>
                            <td>{{ number_format($line->unit_price, 2) }}</td>
                            <td>{{ number_format($line->quantity * $line->unit_price, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No lines found</td>
                        </tr>
                        @endforelse
                    @else
                        <tr>
                            <td colspan="4" class="text-center">No line items available</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            
            @if($transaction->additional_notes)
            <div class="well well-sm">
                <strong>Notes:</strong> {{ $transaction->additional_notes }}
            </div>
            @endif

            @if(!empty($reports_footer) && !empty($reports_footer->value))
                <div class="stock-report-admin-footer stock-report-modal-footer">
                    {!! $reports_footer->value !!}
                </div>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>
