@php
    $master = $overview['master'] ?? [];
    $stock = $overview['stock'] ?? [];
    $quality = $overview['quality'] ?? [];
    $expiry = $overview['expiry'] ?? [];
    $serial = $overview['serial'] ?? [];
    $price = $overview['price'] ?? [];
    $cards = [
        ['label'=>'Total Products','value'=>$master['total']??0,'hint'=>'Complete product master','icon'=>'fa-cubes','tone'=>'blue'],
        ['label'=>'Active Products','value'=>$master['active']??0,'hint'=>'Available for normal operation','icon'=>'fa-check-circle','tone'=>'green'],
        ['label'=>'Low Stock Lines','value'=>$stock['low_stock_lines']??0,'hint'=>'Needs purchase or reorder review','icon'=>'fa-level-down','tone'=>'amber'],
        ['label'=>'Negative Stock','value'=>$stock['negative_stock_lines']??0,'hint'=>'Requires immediate correction','icon'=>'fa-exclamation-triangle','tone'=>'red'],
        ['label'=>'No Image','value'=>$quality['without_image']??0,'hint'=>'Product image is missing','icon'=>'fa-picture-o','tone'=>'amber'],
        ['label'=>'Low Health','value'=>$quality['low_health']??0,'hint'=>'Health score below target','icon'=>'fa-heartbeat','tone'=>'red'],
        ['label'=>'Expired Batches','value'=>$expiry['expired']??0,'hint'=>'Expired batch or lot records','icon'=>'fa-calendar-times-o','tone'=>'red'],
        ['label'=>'Expiring 30 Days','value'=>$expiry['expiring_30_days']??0,'hint'=>'FEFO attention required','icon'=>'fa-clock-o','tone'=>'amber'],
        ['label'=>'Serial Numbers','value'=>$serial['total_serials']??0,'hint'=>'Tracked serialized items','icon'=>'fa-barcode','tone'=>'cyan'],
        ['label'=>'Warranty Active','value'=>$serial['warranty_active']??0,'hint'=>'Active warranty records','icon'=>'fa-shield','tone'=>'green'],
        ['label'=>'Price Changes Today','value'=>$price['changes_today']??0,'hint'=>'Updated pricing records','icon'=>'fa-tags','tone'=>'cyan'],
        ['label'=>'Future Prices','value'=>$price['future_prices']??0,'hint'=>'Scheduled effective prices','icon'=>'fa-calendar-plus-o','tone'=>'blue'],
    ];
@endphp
<div class="productsnew-kpi-grid-advanced">
    @foreach($cards as $card)
        <div class="pn-stat-card pn-tone-{{ $card['tone'] }}">
            <span class="pn-stat-icon"><i class="fa {{ $card['icon'] }}" aria-hidden="true"></i></span>
            <span class="pn-stat-copy">
                <small>{{ $card['label'] }}</small>
                <strong>{{ is_numeric($card['value']) ? number_format((float)$card['value'], floor((float)$card['value']) != (float)$card['value'] ? 3 : 0) : $card['value'] }}</strong>
                <em>{{ $card['hint'] }}</em>
            </span>
        </div>
    @endforeach
</div>
