@php
    $product = $workspace['product'];
@endphp
<div class="row">
    <div class="col-md-9">
        <div class="box productsnew-card productsnew-command-summary">
            <div class="box-body">
                <div class="media">
                    <div class="media-left">
                        <div class="productsnew-product-avatar">
                            @if($workspace['summary']['image_url'])
                                <img src="{{ $workspace['summary']['image_url'] }}" alt="{{ $product->name }}">
                            @else
                                <i class="fa fa-cube"></i>
                            @endif
                        </div>
                    </div>
                    <div class="media-body">
                        <h2>{{ $product->name }}</h2>
                        <div class="productsnew-meta-row">
                            <span>SKU: <strong>{{ $workspace['summary']['sku'] ?: '-' }}</strong></span>
                            <span>Barcode: <strong>{{ $workspace['summary']['barcode'] ?: '-' }}</strong></span>
                            <span>Type: <strong>{{ $workspace['summary']['type'] }}</strong></span>
                            <span>Status: <strong>{{ ucfirst($workspace['summary']['status']) }}</strong></span>
                        </div>
                        <div class="progress productsnew-health-progress"><div class="progress-bar" role="progressbar" style="width: {{ $workspace['summary']['health_score'] }}%">{{ $workspace['summary']['health_score'] }}%</div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row productsnew-kpi-grid">
            @include('productsnew::command_center.partials.widget', ['title'=>'Available Stock','value'=>$workspace['inventory']['available'],'icon'=>'fa-cubes'])
            @include('productsnew::command_center.partials.widget', ['title'=>'Selling Price','value'=>number_format($workspace['finance']['selling_price'],4),'icon'=>'fa-tags'])
            @include('productsnew::command_center.partials.widget', ['title'=>'Margin %','value'=>$workspace['finance']['margin_percentage'].'%','icon'=>'fa-line-chart'])
            @include('productsnew::command_center.partials.widget', ['title'=>'Alerts','value'=>count($workspace['alerts']),'icon'=>'fa-bell'])
        </div>

        <div class="nav-tabs-custom productsnew-tabs">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#pcc-overview" data-toggle="tab">Overview</a></li>
                <li><a href="#pcc-inventory" data-toggle="tab">Inventory</a></li>
                <li><a href="#pcc-finance" data-toggle="tab">Pricing & Cost</a></li>
                <li><a href="#pcc-timeline" data-toggle="tab">Timeline</a></li>
                <li><a href="#pcc-media" data-toggle="tab">Media</a></li>
                <li><a href="#pcc-relations" data-toggle="tab">Relationships</a></li>
                <li><a href="#pcc-integrations" data-toggle="tab">ERP Usage</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="pcc-overview">@include('productsnew::command_center.partials.overview')</div>
                <div class="tab-pane" id="pcc-inventory">@include('productsnew::command_center.partials.inventory')</div>
                <div class="tab-pane" id="pcc-finance">@include('productsnew::command_center.partials.finance')</div>
                <div class="tab-pane" id="pcc-timeline">@include('productsnew::command_center.partials.timeline')</div>
                <div class="tab-pane" id="pcc-media">@include('productsnew::command_center.partials.media')</div>
                <div class="tab-pane" id="pcc-relations">@include('productsnew::command_center.partials.relationships')</div>
                <div class="tab-pane" id="pcc-integrations">@include('productsnew::command_center.partials.integrations')</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        @include('productsnew::command_center.partials.actions')
        @include('productsnew::command_center.partials.alerts')
    </div>
</div>
