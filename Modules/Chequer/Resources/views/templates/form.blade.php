@extends('chequer::layouts.app')
@section('title', $row ? 'Edit Template' : 'Create Template')
@section('chequer_content')
@php
    $map = $fieldMap ?? [];
    $fields = $map['fields'] ?? [];
    $opts = $map['options'] ?? [];
    $image = $map['template_image'] ?? null;
    $signatureImage = $map['signature_image'] ?? null;
@endphp
<div class="cheq-top">
    <div>
        <div class="cheq-title">{{ $row ? 'Edit' : 'Create' }} Template</div>
        <div class="cheq-sub">Position every printable field visually and save the layout inside the standalone Chequer module.</div>
    </div>
    <div class="cheq-page-actions">
        @if($row)
            <a class="cheq-btn blue" target="_blank" href="{{ url('/chequer-module/templates/'.$row->id.'/test-print') }}">Test Print</a>
        @endif
        <a class="cheq-btn gray" href="{{ url('/chequer-module/templates') }}">Back</a>
    </div>
</div>

@if($errors->any())
    <div class="cheq-alert danger">
        <b>Please correct the following:</b>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form class="cheq-card" id="cheq-template-form" method="post" enctype="multipart/form-data" action="{{ $row ? url('/chequer-module/templates/'.$row->id) : url('/chequer-module/templates') }}">
    @csrf
    @if($row) @method('PUT') @endif
    <input type="hidden" name="field_map" id="field_map" value='@json($map)'>
    <input type="hidden" name="existing_template_image" value="{{ $image }}">
    <input type="hidden" name="existing_signature_image" value="{{ $signatureImage }}">

    <div class="cheq-section-title">Template setup</div>
    <div class="cheq-form-grid cheq-form-grid-tight">
        <div class="cheq-field">
            <label>Template Name <span class="req">*</span></label>
            <input class="cheq-input" name="template_name" id="template_name" maxlength="191" value="{{ old('template_name',$row->template_name ?? '') }}" required autocomplete="off">
        </div>
        <div class="cheq-field">
            <label>Copy Template</label>
            <select class="cheq-select" id="copy_template">
                <option value="">Select Template</option>
                @foreach($templates as $template)
                    <option value="{{ $template->id }}"
                        data-map='@json(json_decode($template->field_map ?? "{}", true))'
                        data-width="{{ $template->paper_width }}"
                        data-height="{{ $template->paper_height }}"
                        data-bank="{{ $template->bank_name }}">
                        {{ $template->template_name }} @if($template->bank_name) - {{ $template->bank_name }} @endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="cheq-field">
            <label>Cheque Background Image</label>
            <input class="cheq-input" type="file" name="template_image" id="template_image" accept="image/png,image/jpeg,image/webp">
            @if($image)<label class="minor-check"><input type="checkbox" name="remove_template_image" value="1"> Remove current image</label>@endif
        </div>
        <div class="cheq-field">
            <label>Signature Image</label>
            <input class="cheq-input" type="file" name="signature_image" id="signature_image" accept="image/png,image/jpeg,image/webp">
            @if($signatureImage)<label class="minor-check"><input type="checkbox" name="remove_signature_image" value="1"> Remove current signature</label>@endif
        </div>
        <div class="cheq-field">
            <label>Bank Name</label>
            <input class="cheq-input" id="bank_name" name="bank_name" value="{{ old('bank_name',$row->bank_name ?? '') }}">
        </div>
        <div class="cheq-field">
            <label>Date Format</label>
            <select class="cheq-select" name="date_format" id="date_format">
                @foreach(['dd-mm-yyyy'=>'dd-mm-yyyy','mm-dd-yyyy'=>'mm-dd-yyyy','yyyy-mm-dd'=>'yyyy-mm-dd'] as $val=>$label)
                    <option value="{{ $val }}" {{ ($map['date_format'] ?? 'dd-mm-yyyy') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="cheq-field">
            <label>Date Separator</label>
            <select class="cheq-select" name="separator" id="separator">
                @foreach(['-','/','.',' '] as $separator)
                    <option value="{{ $separator }}" {{ ($map['separator'] ?? '-') === $separator ? 'selected' : '' }}>{{ $separator === ' ' ? 'Space' : $separator }}</option>
                @endforeach
            </select>
        </div>
        <div class="cheq-field">
            <label>Template Width</label>
            <div class="cheq-inline"><input class="cheq-input" type="number" step="0.01" min="1" max="30" name="paper_width" id="paper_width" value="{{ old('paper_width',$row->paper_width ?? 6.77) }}" required><span>in</span></div>
        </div>
        <div class="cheq-field">
            <label>Template Height</label>
            <div class="cheq-inline"><input class="cheq-input" type="number" step="0.01" min="1" max="20" name="paper_height" id="paper_height" value="{{ old('paper_height',$row->paper_height ?? 3.33) }}" required><span>in</span></div>
        </div>
        <div class="cheq-field">
            <label>Status</label>
            <select class="cheq-select" name="status">
                <option value="active" {{ old('status',$row->status ?? 'active')==='active'?'selected':'' }}>Active</option>
                <option value="inactive" {{ old('status',$row->status ?? '')==='inactive'?'selected':'' }}>Inactive</option>
            </select>
        </div>
        <div class="cheq-field check-field">
            <label><input type="checkbox" name="is_default" value="1" {{ !empty($map['is_default']) ? 'checked' : '' }}> Default Template</label>
            <label><input type="checkbox" name="snap_to_grid" id="snap_to_grid" value="1" {{ array_key_exists('snap_to_grid',$map) ? (!empty($map['snap_to_grid'])?'checked':'') : 'checked' }}> Snap to Grid</label>
        </div>
        <div class="cheq-field">
            <label>Grid Size</label>
            <div class="cheq-inline"><input class="cheq-input" type="number" name="grid_size" id="grid_size" min="1" max="50" value="{{ (int)($map['grid_size'] ?? 5) }}"><span>px</span></div>
        </div>
    </div>

    <div class="cheq-template-options">
        @foreach([
            'amount_words_2'=>'Amount in words 2',
            'amount_words_3'=>'Amount in words 3',
            'strike_bearer'=>'Strike Bearer',
            'stamp'=>'Stamp',
            'signature'=>'Signature',
            'double_cross'=>'Double Cross',
            'account_payee_only'=>'A/C Payee Only',
            'not_negotiable'=>'Not Negotiable'
        ] as $key=>$label)
            <label><input type="checkbox" name="{{ $key }}" id="{{ $key }}" {{ !empty($opts[$key]) ? 'checked' : '' }}> {{ $label }}</label>
        @endforeach
    </div>

    <div class="cheq-designer-toolbar">
        <button type="button" class="cheq-btn gray" id="zoom_out">− Zoom</button>
        <span id="zoom_label">100%</span>
        <button type="button" class="cheq-btn gray" id="zoom_in">+ Zoom</button>
        <button type="button" class="cheq-btn gray" id="toggle_grid">Grid</button>
        <button type="button" class="cheq-btn gray" id="reset_preview">Reset Layout</button>
        <button type="button" class="cheq-btn blue" id="browser_test_print">Preview / Test Print</button>
        <span class="designer-tip">Drag fields or use arrow keys. Shift + arrow moves 10px.</span>
    </div>

    <div class="cheq-designer-wrap">
        <div class="cheq-designer-tools">
            <div class="cheq-section-title">Selected Field</div>
            <div class="cheq-field"><label>Field</label><input class="cheq-input" id="selected_field" readonly></div>
            <div class="cheq-field"><label>Preview Text</label><input class="cheq-input" id="field_sample"></div>
            <div class="two-col">
                <div class="cheq-field"><label>Top</label><input class="cheq-input" type="number" id="move_top" step="1"></div>
                <div class="cheq-field"><label>Left</label><input class="cheq-input" type="number" id="move_left" step="1"></div>
                <div class="cheq-field"><label>Width</label><input class="cheq-input" type="number" id="field_width" step="1" min="5"></div>
                <div class="cheq-field"><label>Height</label><input class="cheq-input" type="number" id="field_height" step="1" min="5"></div>
                <div class="cheq-field"><label>Font Size</label><input class="cheq-input" type="number" id="field_font_size" step="1" min="6" max="72"></div>
                <div class="cheq-field"><label>Alignment</label><select class="cheq-select" id="field_align"><option value="left">Left</option><option value="center">Center</option><option value="right">Right</option></select></div>
            </div>
            <label class="minor-check"><input type="checkbox" id="field_bold"> Bold</label>
            <label class="minor-check"><input type="checkbox" id="field_enabled"> Show Field</label>
            <button type="button" class="cheq-btn blue full" id="apply_position">Apply Field Settings</button>
        </div>

        <div class="cheq-designer-area" id="designer_area">
            <div class="ruler ruler-x" id="ruler_x"></div>
            <div class="canvas-stage" id="canvas_stage">
                <div id="cheque_canvas" class="cheq-cheque-canvas {{ !empty($map['snap_to_grid']) ? 'show-grid' : '' }}"
                     style="width: {{ (float)($row->paper_width ?? 6.77) * 96 }}px; height: {{ (float)($row->paper_height ?? 3.33) * 96 }}px; @if($image) background-image:url('{{ asset('storage/'.$image) }}'); @endif">
                    @foreach($fields as $key => $f)
                        <div class="cheq-draggable"
                             tabindex="0"
                             data-key="{{ $key }}"
                             style="top:{{ $f['top'] ?? 10 }}px;left:{{ $f['left'] ?? 10 }}px;width:{{ $f['width'] ?? 120 }}px;height:{{ $f['height'] ?? 28 }}px;font-size:{{ $f['font_size'] ?? 14 }}px;text-align:{{ $f['align'] ?? 'left' }};font-weight:{{ !empty($f['bold']) ? '700' : '400' }};display:{{ !empty($f['enabled']) ? 'flex' : 'none' }}">
                            @if($key === 'signature' && $signatureImage)
                                <img src="{{ asset('storage/'.$signatureImage) }}" alt="Signature">
                            @else
                                <span>{{ $f['sample'] ?? $f['label'] ?? $key }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="date-preview"><b>Date preview:</b> <span id="date_preview_text"></span></div>
        </div>
    </div>

    <div class="cheq-actions-row">
        <button class="cheq-btn green" type="submit">Save Template</button>
        <a class="cheq-btn gray" href="{{ url('/chequer-module/templates') }}">Cancel</a>
    </div>
</form>

<style>
.cheq-page-actions,.cheq-actions-row,.cheq-designer-toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.cheq-alert{border-radius:12px;padding:12px 16px;margin-bottom:14px}.cheq-alert.danger{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239}.cheq-section-title{font-weight:900;font-size:17px;color:#0f172a;margin:0 0 14px}.req{color:#ef4444}.cheq-form-grid-tight{grid-template-columns:repeat(4,minmax(180px,1fr));gap:14px}.cheq-inline{display:flex;gap:8px;align-items:center}.cheq-inline span{font-weight:800;color:#64748b}.check-field{display:flex;flex-direction:column;justify-content:center;gap:10px}.minor-check{font-weight:700;color:#475569;font-size:12px;margin-top:7px;display:block}.cheq-template-options{display:flex;flex-wrap:wrap;gap:10px;margin:18px 0}.cheq-template-options label{background:#f8fafc;border:1px solid #dbe7f5;border-radius:12px;padding:9px 12px;font-weight:800}.cheq-designer-toolbar{background:#eff6ff;border:1px solid #bfdbfe;border-radius:14px;padding:10px 12px;margin-top:18px}.cheq-designer-toolbar #zoom_label{font-weight:900;min-width:48px;text-align:center}.designer-tip{font-weight:700;color:#475569;margin-left:auto}.cheq-designer-wrap{display:grid;grid-template-columns:300px 1fr;gap:18px;margin-top:14px}.cheq-designer-tools{background:#f8fbff;border:1px solid #dbe7f5;border-radius:18px;padding:16px}.two-col{display:grid;grid-template-columns:1fr 1fr;gap:8px}.cheq-btn.full{width:100%;justify-content:center;margin-top:10px}.cheq-designer-area{overflow:auto;border:1px dashed #bcd0e8;border-radius:18px;background:#f8fafc;padding:18px;min-height:450px}.ruler-x{height:24px;margin-left:0;background:repeating-linear-gradient(to right,#94a3b8 0,#94a3b8 1px,transparent 1px,transparent 10px);opacity:.55}.canvas-stage{transform-origin:top left;display:inline-block}.cheq-cheque-canvas{position:relative;background-color:#fff;background-size:100% 100%;background-repeat:no-repeat;border:1px solid #334155;box-shadow:0 14px 30px rgba(15,23,42,.12);overflow:hidden}.cheq-cheque-canvas.show-grid{background-image:linear-gradient(rgba(59,130,246,.12) 1px,transparent 1px),linear-gradient(90deg,rgba(59,130,246,.12) 1px,transparent 1px);background-size:10px 10px}.cheq-cheque-canvas.show-grid[style*="background-image:url"]{background-blend-mode:multiply}.cheq-draggable{position:absolute;border:1px dashed #334155;background:rgba(255,255,255,.68);padding:3px 5px;cursor:move;overflow:hidden;color:#000;align-items:center;box-sizing:border-box;user-select:none}.cheq-draggable span{width:100%;white-space:normal}.cheq-draggable img{width:100%;height:100%;object-fit:contain}.cheq-draggable.active{outline:3px solid #2563eb;background:rgba(219,234,254,.84);z-index:50}.date-preview{margin-top:12px;color:#334155}.cheq-actions-row{margin-top:18px}@media(max-width:1100px){.cheq-designer-wrap{grid-template-columns:1fr}.cheq-form-grid-tight{grid-template-columns:repeat(2,minmax(180px,1fr))}}@media(max-width:650px){.cheq-form-grid-tight{grid-template-columns:1fr}.two-col{grid-template-columns:1fr}}
@media print{body *{visibility:hidden!important}#cheque_canvas,#cheque_canvas *{visibility:visible!important}#cheque_canvas{position:absolute!important;left:0;top:0;border:0!important;box-shadow:none!important}.cheq-draggable{border:0!important;background:transparent!important;outline:0!important}}
</style>
<script>
(function(){
    const initialMap = @json($map);
    let map = JSON.parse(JSON.stringify(initialMap));
    let selectedKey = null;
    let zoom = 1;
    const pxPerInch = 96;
    const canvas = document.getElementById('cheque_canvas');
    const stage = document.getElementById('canvas_stage');
    const fieldMap = document.getElementById('field_map');
    const existingBackground = @json($image ? asset('storage/'.$image) : null);
    const existingSignature = @json($signatureImage ? asset('storage/'.$signatureImage) : null);

    function deepMerge(target, source){
        if(!source || typeof source !== 'object') return target;
        Object.keys(source).forEach(function(key){
            if(source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])){
                target[key] = deepMerge(target[key] || {}, source[key]);
            } else target[key] = source[key];
        });
        return target;
    }
    function syncHidden(){ fieldMap.value = JSON.stringify(map); }
    function snap(value){
        if(!document.getElementById('snap_to_grid').checked) return Math.round(value);
        const size = Math.max(1, parseInt(document.getElementById('grid_size').value || 5));
        return Math.round(value / size) * size;
    }
    function clampField(el, left, top){
        const maxLeft = Math.max(0, canvas.clientWidth - el.offsetWidth);
        const maxTop = Math.max(0, canvas.clientHeight - el.offsetHeight);
        return {left:Math.max(0,Math.min(maxLeft,left)),top:Math.max(0,Math.min(maxTop,top))};
    }
    function fieldEl(key){ return canvas.querySelector('[data-key="'+key+'"]'); }
    function syncElementFromMap(key){
        const f = map.fields[key], el = fieldEl(key); if(!f || !el) return;
        el.style.top = (parseInt(f.top || 0))+'px'; el.style.left = (parseInt(f.left || 0))+'px';
        el.style.width = (parseInt(f.width || 120))+'px'; el.style.height = (parseInt(f.height || 28))+'px';
        el.style.fontSize = (parseInt(f.font_size || 14))+'px'; el.style.textAlign = f.align || 'left';
        el.style.fontWeight = f.bold ? '700':'400'; el.style.display = f.enabled ? 'flex':'none';
        const span = el.querySelector('span'); if(span) span.textContent = f.sample || f.label || key;
    }
    function renderAll(){
        Object.keys(map.fields || {}).forEach(syncElementFromMap);
        resizeCanvas(); updateDatePreview(); syncHidden();
    }
    function resizeCanvas(){
        canvas.style.width = (parseFloat(document.getElementById('paper_width').value || 6.77) * pxPerInch)+'px';
        canvas.style.height = (parseFloat(document.getElementById('paper_height').value || 3.33) * pxPerInch)+'px';
        Object.keys(map.fields || {}).forEach(function(key){
            const el=fieldEl(key); if(!el) return; const p=clampField(el,parseInt(el.style.left||0),parseInt(el.style.top||0));
            el.style.left=p.left+'px';el.style.top=p.top+'px';map.fields[key].left=p.left;map.fields[key].top=p.top;
        });
        syncHidden();
    }
    function select(el){
        canvas.querySelectorAll('.cheq-draggable').forEach(x=>x.classList.remove('active'));
        el.classList.add('active'); selectedKey=el.dataset.key;
        const f=map.fields[selectedKey] || {};
        document.getElementById('selected_field').value=f.label || selectedKey;
        document.getElementById('field_sample').value=f.sample || f.label || selectedKey;
        document.getElementById('move_top').value=parseInt(el.style.top||0);
        document.getElementById('move_left').value=parseInt(el.style.left||0);
        document.getElementById('field_width').value=parseInt(el.style.width||0);
        document.getElementById('field_height').value=parseInt(el.style.height||0);
        document.getElementById('field_font_size').value=parseInt(el.style.fontSize||14);
        document.getElementById('field_align').value=f.align || 'left';
        document.getElementById('field_bold').checked=!!f.bold;
        document.getElementById('field_enabled').checked=f.enabled !== false;
        el.focus();
    }
    function saveElementPosition(el){
        const key=el.dataset.key; if(!map.fields[key]) return;
        map.fields[key].top=parseInt(el.style.top||0); map.fields[key].left=parseInt(el.style.left||0);
        syncHidden(); if(selectedKey===key) select(el);
    }
    function bindDraggable(el){
        el.addEventListener('click',()=>select(el));
        el.addEventListener('mousedown',function(e){
            if(e.button!==0) return; select(el);
            const rect=canvas.getBoundingClientRect(), startX=e.clientX, startY=e.clientY;
            const startTop=parseInt(el.style.top||0), startLeft=parseInt(el.style.left||0);
            function move(ev){
                const deltaX=(ev.clientX-startX)/zoom, deltaY=(ev.clientY-startY)/zoom;
                const p=clampField(el,snap(startLeft+deltaX),snap(startTop+deltaY));
                el.style.left=p.left+'px';el.style.top=p.top+'px';
                document.getElementById('move_left').value=p.left;document.getElementById('move_top').value=p.top;
            }
            function up(){document.removeEventListener('mousemove',move);document.removeEventListener('mouseup',up);saveElementPosition(el);}
            document.addEventListener('mousemove',move);document.addEventListener('mouseup',up);e.preventDefault();
        });
        el.addEventListener('keydown',function(e){
            if(!['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(e.key)) return;
            const step=e.shiftKey?10:1; let top=parseInt(el.style.top||0),left=parseInt(el.style.left||0);
            if(e.key==='ArrowUp') top-=step;if(e.key==='ArrowDown') top+=step;if(e.key==='ArrowLeft') left-=step;if(e.key==='ArrowRight') left+=step;
            const p=clampField(el,left,top);el.style.top=p.top+'px';el.style.left=p.left+'px';saveElementPosition(el);e.preventDefault();
        });
    }
    canvas.querySelectorAll('.cheq-draggable').forEach(bindDraggable);

    document.getElementById('apply_position').addEventListener('click',function(){
        if(!selectedKey) return; const el=fieldEl(selectedKey); if(!el) return;
        const f=map.fields[selectedKey] || {};
        f.sample=document.getElementById('field_sample').value || f.label || selectedKey;
        f.width=Math.max(5,parseInt(document.getElementById('field_width').value||120));
        f.height=Math.max(5,parseInt(document.getElementById('field_height').value||28));
        f.font_size=Math.max(6,parseInt(document.getElementById('field_font_size').value||14));
        f.align=document.getElementById('field_align').value;f.bold=document.getElementById('field_bold').checked;f.enabled=document.getElementById('field_enabled').checked;
        el.style.width=f.width+'px';el.style.height=f.height+'px';
        const p=clampField(el,snap(parseInt(document.getElementById('move_left').value||0)),snap(parseInt(document.getElementById('move_top').value||0)));
        f.left=p.left;f.top=p.top;map.fields[selectedKey]=f;syncElementFromMap(selectedKey);syncHidden();select(el);
    });

    const optionToField={amount_words_2:'amount_words_2',amount_words_3:'amount_words_3',stamp:'stamp',signature:'signature',double_cross:'double_cross',account_payee_only:'account_payee',not_negotiable:'not_negotiable',strike_bearer:'strike_bearer'};
    document.querySelectorAll('.cheq-template-options input[type="checkbox"]').forEach(function(chk){
        chk.addEventListener('change',function(){
            map.options=map.options||{};map.options[chk.name]=chk.checked;
            const key=optionToField[chk.name];if(key&&map.fields[key]){map.fields[key].enabled=chk.checked;syncElementFromMap(key);}syncHidden();
        });
    });

    function formatDate(){
        const d=new Date(),pad=n=>String(n).padStart(2,'0');
        const values={dd:pad(d.getDate()),mm:pad(d.getMonth()+1),yyyy:String(d.getFullYear())};
        return document.getElementById('date_format').value.split('-').map(x=>values[x]).join(document.getElementById('separator').value);
    }
    function updateDatePreview(){
        const text=formatDate();document.getElementById('date_preview_text').textContent=text;
        if(map.fields.date){map.fields.date.sample=text;syncElementFromMap('date');}
        map.date_format=document.getElementById('date_format').value;map.separator=document.getElementById('separator').value;syncHidden();
    }
    ['date_format','separator'].forEach(id=>document.getElementById(id).addEventListener('change',updateDatePreview));
    ['paper_width','paper_height'].forEach(id=>document.getElementById(id).addEventListener('input',resizeCanvas));
    document.getElementById('snap_to_grid').addEventListener('change',()=>canvas.classList.toggle('show-grid',document.getElementById('snap_to_grid').checked));
    document.getElementById('toggle_grid').addEventListener('click',()=>canvas.classList.toggle('show-grid'));

    function filePreview(input,callback){const file=input.files[0];if(!file)return;const reader=new FileReader();reader.onload=e=>callback(e.target.result);reader.readAsDataURL(file);}
    document.getElementById('template_image').addEventListener('change',function(){filePreview(this,url=>{canvas.style.backgroundImage='url("'+url+'")';});});
    document.getElementById('signature_image').addEventListener('change',function(){filePreview(this,url=>{const el=fieldEl('signature');if(el){el.innerHTML='<img src="'+url+'" alt="Signature">';}});});

    document.getElementById('copy_template').addEventListener('change',function(){
        const opt=this.options[this.selectedIndex];if(!opt||!opt.dataset.map)return;
        try{
            const copied=JSON.parse(opt.dataset.map||'{}');map=deepMerge(JSON.parse(JSON.stringify(initialMap)),copied);
            document.getElementById('paper_width').value=opt.dataset.width||6.77;document.getElementById('paper_height').value=opt.dataset.height||3.33;
            if(!document.getElementById('bank_name').value)document.getElementById('bank_name').value=opt.dataset.bank||'';
            document.getElementById('date_format').value=map.date_format||'dd-mm-yyyy';document.getElementById('separator').value=map.separator||'-';
            Object.keys(optionToField).forEach(function(name){const c=document.getElementById(name);if(c)c.checked=!!(map.options&&map.options[name]);});
            if(map.template_image)canvas.style.backgroundImage='url("{{ asset('storage') }}/'+map.template_image+'")';
            if(map.signature_image){const sig=fieldEl('signature');if(sig)sig.innerHTML='<img src="{{ asset('storage') }}/'+map.signature_image+'" alt="Signature">';}
            renderAll();
        }catch(error){alert('Unable to copy the selected template.');}
    });

    document.getElementById('reset_preview').addEventListener('click',function(){
        if(!confirm('Reset all field positions to the values loaded when this page opened?'))return;
        map=JSON.parse(JSON.stringify(initialMap));
        document.getElementById('paper_width').value={{ (float)($row->paper_width ?? 6.77) }};document.getElementById('paper_height').value={{ (float)($row->paper_height ?? 3.33) }};
        canvas.style.backgroundImage=existingBackground?'url("'+existingBackground+'")':'';
        Object.keys(optionToField).forEach(function(name){const c=document.getElementById(name);if(c)c.checked=!!(map.options&&map.options[name]);});
        if(existingSignature){const el=fieldEl('signature');if(el)el.innerHTML='<img src="'+existingSignature+'" alt="Signature">';}
        renderAll();
    });

    function setZoom(value){zoom=Math.max(.5,Math.min(2,value));stage.style.transform='scale('+zoom+')';stage.style.marginBottom=((zoom-1)*canvas.clientHeight)+'px';document.getElementById('zoom_label').textContent=Math.round(zoom*100)+'%';}
    document.getElementById('zoom_in').addEventListener('click',()=>setZoom(zoom+.1));document.getElementById('zoom_out').addEventListener('click',()=>setZoom(zoom-.1));
    document.getElementById('browser_test_print').addEventListener('click',function(){
        canvas.querySelectorAll('.cheq-draggable').forEach(x=>x.classList.remove('active'));window.print();
    });
    document.getElementById('cheq-template-form').addEventListener('submit',function(){updateDatePreview();syncHidden();});
    renderAll();
})();
</script>
@endsection
