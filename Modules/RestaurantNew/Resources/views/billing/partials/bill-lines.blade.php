<div class="card pos-standard-card">
    <div class="card-header"><strong>@lang('restaurantnew::lang.bill_items')</strong></div>
    <div class="card-body table-responsive">
        <table class="table table-sm restaurantnew-table">
            <thead>
                <tr>
                    <th>@lang('restaurantnew::lang.item')</th>
                    <th class="text-right">@lang('restaurantnew::lang.qty')</th>
                    <th class="text-right">@lang('restaurantnew::lang.unit_price')</th>
                    <th class="text-right">@lang('restaurantnew::lang.total')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bill->lines as $line)
                    <tr>
                        <td>{{ $line->item_name }} @if($line->variant_name)<small>({{ $line->variant_name }})</small>@endif</td>
                        <td class="text-right">{{ number_format($line->quantity, 4) }}</td>
                        <td class="text-right">{{ number_format($line->unit_price, 4) }}</td>
                        <td class="text-right">{{ number_format($line->line_total, 4) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
