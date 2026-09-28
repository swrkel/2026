@extends('RiceMill::layout')
@section('rcm-title','New Packing Batch')
@section('rcm-actions')
<a class="rcm-btn secondary" href="{{ route('rice-mill.packaging-material-mappings.index') }}"><i class="fa fa-random"></i> Material Usage Mapping</a>
<a class="rcm-btn secondary" href="{{ route('rice-mill.packaging-materials.index') }}"><i class="fa fa-cubes"></i> Packaging Materials</a>
@endsection
@section('rcm-content')
<form method="post" action="{{ route('rice-mill.packing.store') }}" id="rcm-packing-form">@csrf
    <div class="rcm-card">
        <div class="rcm-form-grid rcm-location-store-group">
            <div class="rcm-field"><label>Packed At</label><input type="datetime-local" name="packed_at" value="{{ old('packed_at') }}"></div>
            <div class="rcm-field"><label>Location</label><select class="rcm-searchable rcm-location-select" name="location_id"><option value="">Select location</option>@foreach($locations as $x)<option value="{{ $x['id'] }}" {{ (string) old('location_id') === (string) $x['id'] ? 'selected' : '' }}>{{ $x['name'] }}</option>@endforeach</select></div>
            <div class="rcm-field"><label>Store</label><select class="rcm-searchable rcm-store-select" name="store_id"><option value="">Select store</option>@foreach($stores as $x)<option value="{{ $x['id'] }}" data-location-id="{{ $x['location_id'] ?? '' }}" {{ (string) old('store_id') === (string) $x['id'] ? 'selected' : '' }}>{{ $x['name'] }}</option>@endforeach</select></div>
        </div>
    </div>

    <div class="rcm-card">
        @if($errors->has('lines'))
            <div class="rcm-alert rcm-alert-danger" style="margin-bottom:12px">{{ $errors->first('lines') }}</div>
        @endif
        <div class="rcm-form-grid">
            <div class="rcm-field">
                <label>Rice Product</label>
                <select name="lines[0][product_id]" id="rcm-packing-product" class="rcm-searchable" data-placeholder="Type to search Rice Product" required>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}"
                                data-available="{{ (float)$p->available_to_pack }}"
                                {{ (string)old('lines.0.product_id')===(string)$p->id?'selected':'' }}>
                            {{ $p->name }} — Available to pack {{ number_format($p->available_to_pack,$rcmQuantityPrecision) }} kg
                        </option>
                    @endforeach
                </select>
                <small>Available to pack: <strong id="rcm-packing-available">0</strong> kg</small>
            </div>
            <div class="rcm-field"><label>Bag Size (kg)</label><input id="rcm-packing-bag-size" type="number" step="{{ $rcmQuantityStep }}" min="{{ $rcmQuantityStep }}" name="lines[0][bag_size_kg]" required value="{{ old('lines.0.bag_size_kg') }}"></div>
            <div class="rcm-field"><label>Bag Count</label><input id="rcm-packing-bag-count" type="number" min="1" step="1" name="lines[0][bag_count]" required value="{{ old('lines.0.bag_count') }}"></div>
            <div class="rcm-field"><label>Packing Quantity (kg)</label><input id="rcm-packing-total" type="text" readonly value="0"><small id="rcm-packing-warning" style="display:none;color:#b91c1c;font-weight:700">Packing quantity exceeds the available quantity.</small></div>
        </div>

        <div style="margin-top:18px">
            <div class="rcm-panel-head"><h3>Auto Material Usage</h3><span class="rcm-panel-hint">Mapped bags, thread and other materials are reduced automatically when this packing operation is saved.</span></div>
            <div id="rcm-packing-material-preview" class="rcm-muted">Select the Rice Product, Bag Size and Bag Count to preview mapped material usage.</div>
        </div>

        <br>
        <div id="rcm-packing-block-message" class="rcm-alert rcm-alert-danger" style="display:none;margin-bottom:10px"></div>
        <button type="submit" class="rcm-btn" id="rcm-packing-save">Save Packing Batch</button>
    </div>
</form>
<script>
(function(){
    var materialMappings=@json($materialMappings);
    function fmt(v,p){ return Number(v||0).toLocaleString(undefined,{minimumFractionDigits:p,maximumFractionDigits:p}); }
    function packingState(){
        var product=document.getElementById('rcm-packing-product');
        var size=document.getElementById('rcm-packing-bag-size');
        var count=document.getElementById('rcm-packing-bag-count');
        if(!product||!size||!count){ return; }
        var option=product.options[product.selectedIndex];
        var available=parseFloat(option ? option.getAttribute('data-available') : '0') || 0;
        var bagSize=parseFloat(size.value)||0;
        var bagCount=parseInt(count.value||'0',10)||0;
        var qty=bagSize*bagCount;
        var precision={{ (int)$rcmQuantityPrecision }};
        document.getElementById('rcm-packing-available').textContent=fmt(available,precision);
        document.getElementById('rcm-packing-total').value=fmt(qty,precision);
        var tooMuch=qty>available+0.0000001;
        document.getElementById('rcm-packing-warning').style.display=tooMuch?'block':'none';

        var key=(product.value||'')+'|'+bagSize.toFixed(3);
        var mapped=materialMappings[key]||[];
        var materialShortage=false;
        var preview=document.getElementById('rcm-packing-material-preview');
        if(!bagSize||!bagCount){
            preview.innerHTML='<span class="rcm-muted">Select the Rice Product, Bag Size and Bag Count to preview mapped material usage.</span>';
        }else if(!mapped.length){
            preview.innerHTML='<span class="rcm-muted">No material usage mapping is configured for this Rice Product and Bag Size. Packaging can still be saved, but no other materials will be auto-consumed.</span>';
        }else{
            var html='<div class="rcm-table-wrap"><table class="rcm-table"><thead><tr><th>Material</th><th>Usage / Bag</th><th>Required</th><th>Available</th><th>Status</th></tr></thead><tbody>';
            mapped.forEach(function(m){
                var required=(parseFloat(m.usage_per_bag)||0)*bagCount;
                var av=parseFloat(m.available)||0;
                var short=required>av+0.0000001;
                if(short){ materialShortage=true; }
                html+='<tr><td>'+String(m.name)+'</td><td>'+fmt(m.usage_per_bag,precision)+' '+String(m.unit)+'</td><td>'+fmt(required,precision)+' '+String(m.unit)+'</td><td>'+fmt(av,precision)+' '+String(m.unit)+'</td><td>'+(short?'<strong style="color:#b91c1c">Insufficient</strong>':'<strong style="color:#166534">Available</strong>')+'</td></tr>';
            });
            html+='</tbody></table></div>';
            preview.innerHTML=html;
        }
        var blockMessage=document.getElementById('rcm-packing-block-message');
        var message='';
        if(tooMuch){
            message='Cannot save this Packing Batch because the packing quantity exceeds the available Rice quantity.';
        }else if(materialShortage){
            message='Cannot save this Packing Batch because one or more mapped packaging materials have insufficient stock. Add/adjust the material stock, then save again.';
        }
        if(blockMessage){
            blockMessage.textContent=message;
            blockMessage.style.display=message?'block':'none';
        }
        var save=document.getElementById('rcm-packing-save');
        if(save){
            save.dataset.blocked=message?'1':'0';
            save.dataset.blockMessage=message;
            save.setAttribute('aria-disabled',message?'true':'false');
        }
    }
    ['change','input'].forEach(function(evt){
        document.addEventListener(evt,function(e){
            if(e.target && ['rcm-packing-product','rcm-packing-bag-size','rcm-packing-bag-count'].indexOf(e.target.id)!==-1){ packingState(); }
        });
    });
    var form=document.getElementById('rcm-packing-form');
    if(form){
        form.addEventListener('submit',function(e){
            packingState();
            var save=document.getElementById('rcm-packing-save');
            if(save&&save.dataset.blocked==='1'){
                e.preventDefault();
                e.stopPropagation();
                alert(save.dataset.blockMessage||'This Packing Batch cannot be saved until the highlighted stock issue is corrected.');
                return false;
            }
        });
    }
    document.addEventListener('DOMContentLoaded',packingState);
})();
</script>
@endsection
