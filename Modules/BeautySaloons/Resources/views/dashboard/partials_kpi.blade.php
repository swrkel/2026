<div class="row beauty-dashboard-kpis">
    @foreach($cards as $card)
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="bs-kpi-card">
                <div class="bs-kpi-title">{{ $card['title'] }}</div>
                <div class="bs-kpi-value">{{ $card['value'] }}</div>
                @if(!empty($card['sub']))<div class="bs-kpi-sub">{{ $card['sub'] }}</div>@endif
            </div>
        </div>
    @endforeach
</div>
