{{--
    8050: this page now uses the ADMIN layout, so it has the sidebar.

    It extended the standalone layout - a complete HTML document with no
    sidebar, header or navigation. That is why Help Guide Settings opened as a
    bare page with no way back except the browser button.

    The admin layout extends the application layout, which carries the sidebar
    and shared styling. Both article pages already use it.

    Switching layouts meant renaming the section and the two stacks to the names
    this layout yields. The page's own CSS and script are unchanged - only where
    they are injected.
--}}
@extends('helpguide::layouts.admin', [
    'title' => 'Help Guide Settings',
    'heading' => 'Help Guide Settings',
    'subheading' => 'Choose a tenant and business, then set which modules appear in its Help Guide.',
])
{{--
    8050: titled "Help Guide Settings", matching the sidebar entry and the name
    the requirement uses. It was "Help Guide Modules", which read as a list of
    modules rather than the screen where they are configured per business.
--}}
@section('title','Help Guide Settings')
@push('css')
<style>
.hg-form-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}.hg-field label{display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:#4b5870}.hg-search-select{position:relative}.hg-search-select input{width:100%;height:42px;border:1px solid #d9e0ea;border-radius:8px;padding:0 12px;background:#fff}.hg-options{display:none;position:absolute;z-index:50;left:0;right:0;top:45px;max-height:260px;overflow:auto;background:white;border:1px solid #d9e0ea;border-radius:8px;box-shadow:0 8px 22px rgba(0,0,0,.12)}.hg-options.open{display:block}.hg-option{padding:9px 12px;cursor:pointer}.hg-option:hover{background:#f1f5fb}.hg-uid{height:42px;display:flex;align-items:center;padding:0 12px;background:#f6f8fb;border:1px solid #e0e5ed;border-radius:8px;overflow:hidden}.hg-toolbar{display:flex;justify-content:space-between;align-items:center;gap:10px;margin:18px 0 10px}.hg-section-title{font-size:16px;font-weight:700;margin:0}.hg-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px}.hg-check{display:flex;align-items:flex-start;gap:9px;padding:11px;border:1px solid #e4e9f1;border-radius:8px;background:#fff}.hg-check input{margin-top:3px}.hg-check small{display:block;color:#77839a;margin-top:3px}.hg-section{margin-top:18px}.hg-section.disabled .hg-check{background:#fafbfc}.hg-section.override .hg-check{border-color:#c9d9ff;background:#f6f9ff}.hg-status{font-size:13px;color:#657188}.hg-savebar{position:sticky;bottom:0;background:rgba(245,247,251,.96);border-top:1px solid #e0e5ed;margin:22px -22px -22px;padding:12px 22px;display:flex;justify-content:flex-end}.hg-loading{display:none}.hg-loading.show{display:inline-block}.hg-toast{display:none;margin-top:12px;padding:10px 12px;border-radius:8px}.hg-toast.ok{display:block;background:#eaf7ee;color:#216c36}.hg-toast.err{display:block;background:#fdeeee;color:#9a2c2c}@media(max-width:850px){.hg-form-grid{grid-template-columns:1fr}.hg-savebar{margin-left:-12px;margin-right:-12px}}
</style>
@endpush
@section('hg_content')
{{--
    8050: the page's own <h1> is removed.

    The admin layout renders the heading and subheading passed to @extends, so
    keeping this one showed "Help Guide Settings" twice. The action button is
    kept and moved to its own row.
--}}
<div style="display:flex;justify-content:flex-end;margin-bottom:14px">
    <a class="hg-btn" href="{{ route('helpguide.superadmin.articles.list') }}">Manage Help Articles</a>
</div>
<p class="hg-sub">Super Admin control of Help Guide visibility by Tenant Database and Business.</p>
@if(!$ready)
<div class="hg-alert">Help Guide tables are not installed yet. Run the module migration or <strong>Modules/HelpGuide/Database/SQL/HELPGUIDE_MASTER_INSTALL.sql</strong> in the central database.</div>
@endif
<div class="hg-card">
    <div class="hg-form-grid">
        <div class="hg-field"><label>Tenant Database</label>
            <div class="hg-search-select" id="tenantSelect"><input type="text" autocomplete="off" placeholder="Type to search Tenant Database"><div class="hg-options">
                <div class="hg-option" data-value="all">All</div>
                @foreach($tenants as $tenant)<div class="hg-option" data-value="{{ $tenant['id'] }}" data-db="{{ $tenant['database'] }}">{{ $tenant['label'] }}</div>@endforeach
            </div></div>
        </div>
        <div class="hg-field"><label>Business Name</label>
            <div class="hg-search-select" id="businessSelect"><input type="text" autocomplete="off" placeholder="Select Tenant first"><div class="hg-options"><div class="hg-option" data-value="all">All</div></div></div>
        </div>
        <div class="hg-field"><label>Business UID</label><div class="hg-uid" id="businessUid">—</div></div>
    </div>
    <div id="selectionNotice" class="hg-status" style="margin-top:12px">Select one Tenant Database and one Business to manage module visibility. “All” remains available for filtering, but module settings are edited per business to prevent accidental bulk changes.</div>
    <div id="matrix" style="display:none">
        <div class="hg-section" id="assignedSection"><div class="hg-toolbar"><h2 class="hg-section-title">Modules assigned for the Business</h2><span class="hg-status">Enabled in Help Guide by default</span></div><div class="hg-list" id="assignedList"></div></div>
        <div class="hg-section disabled" id="disabledSection"><div class="hg-toolbar"><h2 class="hg-section-title">Modules not enabled for the Business</h2><span class="hg-status">Disabled in Help Guide by default</span></div><div class="hg-list" id="disabledList"></div></div>
        <div class="hg-section override" id="overrideSection"><div class="hg-toolbar"><h2 class="hg-section-title">Modules disabled for the Business, but still enabled in the Help Guide</h2><span class="hg-status">Help-only exceptions</span></div><div class="hg-list" id="overrideList"></div></div>
        <div id="toast" class="hg-toast"></div>
        <div class="hg-savebar"><button class="hg-btn" id="saveBtn" type="button">Save Help Guide Modules</button></div>
    </div>
</div>
@endsection
@push('hg_scripts')
<script>
(function(){
const state={tenant:null,business:null,modules:[]};
const csrf=document.querySelector('meta[name="csrf-token"]').content;
function setupSearchSelect(id,onChange){const root=document.getElementById(id),input=root.querySelector('input'),box=root.querySelector('.hg-options');input.addEventListener('focus',()=>box.classList.add('open'));input.addEventListener('input',()=>{box.classList.add('open');const q=input.value.toLowerCase();box.querySelectorAll('.hg-option').forEach(o=>o.style.display=o.textContent.toLowerCase().includes(q)?'block':'none')});root.addEventListener('click',e=>{const o=e.target.closest('.hg-option');if(!o)return;input.value=o.textContent.trim();root.dataset.value=o.dataset.value||'';root.dataset.db=o.dataset.db||'';box.classList.remove('open');onChange(o)});document.addEventListener('click',e=>{if(!root.contains(e.target))box.classList.remove('open')});}
setupSearchSelect('tenantSelect', async o=>{state.tenant=o.dataset.value;state.business=null;document.getElementById('businessUid').textContent='—';document.getElementById('matrix').style.display='none';const bRoot=document.getElementById('businessSelect'),bInput=bRoot.querySelector('input'),bBox=bRoot.querySelector('.hg-options');bInput.value='Loading...';bBox.innerHTML='<div class="hg-option" data-value="all">All</div>';try{const r=await fetch('{{ route('helpguide.superadmin.businesses') }}?tenant_id='+encodeURIComponent(state.tenant));const j=await r.json();(j.businesses||[]).forEach(b=>{const d=document.createElement('div');d.className='hg-option';d.dataset.value=b.id;d.dataset.uid=b.uid||'';d.dataset.tenantId=b.tenant_id||state.tenant;d.dataset.tenantDatabase=b.tenant_database||'';d.textContent=b.name;bBox.appendChild(d)});bInput.value='';bInput.placeholder='Type to search Business Name'}catch(e){bInput.value='';bInput.placeholder='Unable to load businesses'}});
setupSearchSelect('businessSelect', async o=>{state.business=o.dataset.value;document.getElementById('businessUid').textContent=o.dataset.uid||'—';document.getElementById('matrix').style.display='none';if(!state.tenant||state.tenant==='all'||!state.business||state.business==='all'){document.getElementById('selectionNotice').textContent='Select one Tenant Database and one Business to edit. “All” is available for searching/filtering only.';return;}document.getElementById('selectionNotice').textContent='Loading module assignments...';try{const r=await fetch('{{ route('helpguide.superadmin.matrix') }}?tenant_id='+encodeURIComponent(state.tenant)+'&business_id='+encodeURIComponent(state.business));const j=await r.json();if(!r.ok)throw new Error(j.error||'Unable to load modules');state.modules=j.modules||[];document.getElementById('businessUid').textContent=(j.business&&j.business.uid)||'—';render();document.getElementById('matrix').style.display='block';document.getElementById('selectionNotice').textContent='Only modules enabled below are shown in this business Help Guide.';}catch(e){document.getElementById('selectionNotice').textContent=e.message}});
function card(m){const wrap=document.createElement('label');wrap.className='hg-check';wrap.dataset.key=m.key;const cb=document.createElement('input');cb.type='checkbox';cb.checked=!!m.help_enabled;cb.dataset.key=m.key;const text=document.createElement('span');text.innerHTML='<strong>'+escapeHtml(m.name)+'</strong><small>'+(m.assigned?'Assigned to Business':'Not assigned to Business')+'</small>';wrap.append(cb,text);cb.addEventListener('change',()=>{m.help_enabled=cb.checked;render()});return wrap;}
function render(){const a=document.getElementById('assignedList'),d=document.getElementById('disabledList'),o=document.getElementById('overrideList');a.innerHTML=d.innerHTML=o.innerHTML='';state.modules.forEach(m=>{if(m.assigned)a.appendChild(card(m));else if(m.help_enabled)o.appendChild(card(m));else d.appendChild(card(m));});document.getElementById('assignedSection').style.display=a.children.length?'block':'none';document.getElementById('disabledSection').style.display=d.children.length?'block':'none';document.getElementById('overrideSection').style.display=o.children.length?'block':'none';}
function escapeHtml(s){return String(s).replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]))}
document.getElementById('saveBtn').addEventListener('click',async()=>{const btn=document.getElementById('saveBtn'),toast=document.getElementById('toast');btn.disabled=true;toast.className='hg-toast';const enabled={};state.modules.forEach(m=>{if(m.help_enabled)enabled[m.key]=1});try{const r=await fetch('{{ route('helpguide.superadmin.save') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify({tenant_id:state.tenant,business_id:Number(state.business),enabled_modules:enabled})});const j=await r.json();if(!r.ok||!j.success)throw new Error(j.message||'Save failed');toast.textContent=j.message;toast.className='hg-toast ok'}catch(e){toast.textContent=e.message;toast.className='hg-toast err'}finally{btn.disabled=false}});
})();
</script>
@endpush
