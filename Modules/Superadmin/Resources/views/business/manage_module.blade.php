@extends('layouts.app')
@section('title', ($section['title'] ?? $moduleKey) . ' — ' . $business->name)

@section('content')
{{--
 | Per-module permissions page - MA 007 split, step 1 (read only).
 |
 | Everything is scoped under .pmp- so it cannot disturb the rest of the
 | application's styles.
 |
 | THE STICKY HEADER is the point of the design. With 62 to 111 items in a
 | module, you lose track of which module you are in as soon as you scroll -
 | so module name, live count and search stay with you the whole way down.
--}}

<style>
.pmp-wrap {
    --pmp-ink: #1b2430;
    --pmp-muted: #6b7785;
    --pmp-line: #e3e8ee;
    --pmp-bg: #f7f9fb;
    --pmp-on: #0f766e;
    --pmp-on-soft: #ecfdf5;
    --pmp-off: #b42318;
    --pmp-off-soft: #fef3f2;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--pmp-ink);
    padding-bottom: 40px;
}

/* ---- sticky identity bar ---- */
.pmp-bar {
    position: sticky;
    top: 0;
    z-index: 900;
    background: #fff;
    border-bottom: 1px solid var(--pmp-line);
    box-shadow: 0 1px 3px rgba(27, 36, 48, .06);
    padding: 14px 20px;
    margin: -15px -15px 20px;
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}
.pmp-id { min-width: 220px; }
.pmp-id h2 {
    margin: 0;
    font-size: 19px;
    font-weight: 650;
    letter-spacing: -.01em;
    line-height: 1.2;
}
.pmp-id p {
    margin: 2px 0 0;
    font-size: 12.5px;
    color: var(--pmp-muted);
}
.pmp-count {
    font-variant-numeric: tabular-nums;
    font-size: 13px;
    color: var(--pmp-muted);
    white-space: nowrap;
}
.pmp-count b { color: var(--pmp-on); font-weight: 650; }
.pmp-search {
    flex: 1 1 220px;
    min-width: 180px;
}
.pmp-search input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--pmp-line);
    border-radius: 7px;
    font-size: 14px;
    background: var(--pmp-bg);
    transition: border-color .15s, background .15s;
}
.pmp-search input:focus {
    outline: none;
    border-color: var(--pmp-on);
    background: #fff;
}
.pmp-actions { display: flex; gap: 8px; }
.pmp-btn {
    border: 1px solid var(--pmp-line);
    background: #fff;
    color: var(--pmp-ink);
    padding: 7px 13px;
    border-radius: 7px;
    font-size: 13px;
    cursor: pointer;
    transition: border-color .15s, color .15s;
}
.pmp-btn:hover { border-color: var(--pmp-on); color: var(--pmp-on); }

/* ---- module toggle ---- */
.pmp-module {
    background: #fff;
    border: 1px solid var(--pmp-line);
    border-radius: 10px;
    padding: 16px 18px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.pmp-module-text { flex: 1; }
.pmp-module-text strong { font-size: 15px; font-weight: 620; }
.pmp-module-text span { display: block; font-size: 12.5px; color: var(--pmp-muted); margin-top: 2px; }

/* ---- item grid ---- */
.pmp-section-head {
    display: flex;
    align-items: baseline;
    gap: 10px;
    margin: 0 0 12px;
}
.pmp-section-head h3 {
    margin: 0;
    font-size: 13px;
    font-weight: 650;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--pmp-muted);
}
.pmp-group-heading {
    grid-column: 1 / -1;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .045em;
    color: var(--pmp-muted);
    border-bottom: 1px solid var(--pmp-line);
    padding: 10px 2px 6px;
    margin-top: 2px;
}
.pmp-group-heading:first-child { margin-top: 0; }

.pmp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 2px;
    background: var(--pmp-line);
    border: 1px solid var(--pmp-line);
    border-radius: 10px;
    overflow: hidden;
}
.pmp-item {
    background: #fff;
    /* 21px. Raised in stages: 12 -> 7 (too tight to scan) -> 14 -> 21.
       At 52 to 111 items a row is clicked often, so comfort beats density. */
    padding: 14px 12px;
    display: flex;
    align-items: center;
    gap: 13px;
    transition: background .12s;
    position: relative;
    min-height: 0;
}
.pmp-item:hover { background: var(--pmp-bg); z-index: 10; }
.pmp-item.is-off { background: var(--pmp-off-soft); }
.pmp-item input[type="checkbox"] {
    margin: 0;
    /* 22px, up from 15px. A permission checkbox is a deliberate click and
       a small target is easy to miss. */
    width: 22px;
    height: 22px;
    accent-color: var(--pmp-on);
    flex: none;
    cursor: pointer;
}
.pmp-item-label { flex: 1; min-width: 0; }
.pmp-item-label .pmp-name {
    display: block;
    font-size: 13.5px;
    line-height: 1.25;
    word-break: break-word;
}
.pmp-item.is-off .pmp-name { color: var(--pmp-off); }
.pmp-key {
    /* Absolutely positioned so revealing it cannot change the row height -
       otherwise every hover nudges the grid below it. */
    position: absolute;
    left: 47px;
    right: 12px;
    bottom: -12px;
    z-index: 5;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 10.5px;
    color: var(--pmp-muted);
    background: var(--pmp-bg);
    padding: 1px 5px;
    border-radius: 4px;
    opacity: 0;
    pointer-events: none;
    transition: opacity .15s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pmp-item:hover .pmp-key { opacity: 1; }
.pmp-tag {
    font-size: 10.5px;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--pmp-on);
    background: var(--pmp-on-soft);
    border-radius: 4px;
    padding: 1px 6px;
    margin-left: 6px;
    vertical-align: 1px;
}

.pmp-empty {
    background: #fff;
    border: 1px dashed var(--pmp-line);
    border-radius: 10px;
    padding: 32px;
    text-align: center;
    color: var(--pmp-muted);
    font-size: 14px;
}
.pmp-empty code {
    display: inline-block;
    margin-top: 8px;
    font-size: 12px;
    background: var(--pmp-bg);
    padding: 4px 8px;
    border-radius: 5px;
}
.pmp-note {
    font-size: 13px;
    color: var(--pmp-muted);
    margin: 0 0 14px;
}
.pmp-preview {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 11px 15px;
    font-size: 13.5px;
    margin-bottom: 18px;
}
.pmp-nomatch { padding: 24px; text-align: center; color: var(--pmp-muted); font-size: 14px; }

@media (max-width: 640px) {
    .pmp-bar { gap: 12px; padding: 12px 15px; }
    .pmp-id { min-width: 100%; }
}


.pmp-state-tabs { display: flex; gap: 4px; }
.pmp-state-tab {
    border: 1px solid var(--pmp-line); background: #fff; color: var(--pmp-muted);
    padding: 7px 12px; border-radius: 7px; font-size: 13px; cursor: pointer;
}
.pmp-state-tab:hover { border-color: var(--pmp-on); color: var(--pmp-on); }
.pmp-state-tab.is-on { background: var(--pmp-on-soft); border-color: var(--pmp-on); color: var(--pmp-on); font-weight: 620; }

/* Module switcher. Type to find; filter by enabled state. */
.pmp-jump { position: relative; }
.pmp-jump input {
    width: 200px; padding: 7px 12px;
    border: 1px solid var(--pmp-line); border-radius: 7px;
    font-size: 13px; background: #fff;
}
.pmp-jump input:focus { outline: none; border-color: var(--pmp-on); }
.pmp-jump-list {
    display: none; position: absolute; top: calc(100% + 4px); right: 0;
    width: 330px; background: #fff;
    border: 1px solid var(--pmp-line); border-radius: 9px;
    box-shadow: 0 8px 28px rgba(27,36,48,.16); z-index: 1300;
}
.pmp-jump-list.is-open { display: block; }
.pmp-jump-tabs { display: flex; gap: 4px; padding: 7px; border-bottom: 1px solid var(--pmp-line); }
.pmp-jump-tab {
    flex: 1; border: 1px solid transparent; background: transparent;
    padding: 5px 8px; border-radius: 6px; font-size: 12.5px;
    color: var(--pmp-muted); cursor: pointer;
}
.pmp-jump-tab:hover { background: var(--pmp-bg); }
.pmp-jump-tab.is-on { background: var(--pmp-on-soft); border-color: var(--pmp-on); color: var(--pmp-on); font-weight: 620; }
.pmp-jump-scroll { max-height: 340px; overflow-y: auto; padding: 5px; }
.pmp-jump-item {
    display: flex; align-items: center; gap: 9px;
    padding: 7px 10px; border-radius: 6px;
    font-size: 13.5px; color: var(--pmp-ink); text-decoration: none;
}
.pmp-jump-item:hover, .pmp-jump-item.is-active { background: var(--pmp-bg); color: var(--pmp-ink); text-decoration: none; }
.pmp-jump-item.is-current { font-weight: 650; color: var(--pmp-on); }
.pmp-jump-name { flex: 1; }
.pmp-jump-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--pmp-on); flex: none; }
.pmp-jump-dot.is-off { background: #d0d5dd; }
.pmp-jump-item em {
    font-style: normal; font-size: 11.5px; color: var(--pmp-muted);
    font-variant-numeric: tabular-nums; flex: none;
}
.pmp-jump-none { display: none; padding: 14px; text-align: center; color: var(--pmp-muted); font-size: 13px; }

/* Floating save. Fixed, so it is reachable from anywhere in a 111-item list
   without scrolling back to the top. */
.pmp-save-float {
    position: fixed;
    right: 26px;
    bottom: 26px;
    z-index: 1200;
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff;
    border: 1px solid var(--pmp-line);
    border-radius: 12px;
    box-shadow: 0 6px 24px rgba(27, 36, 48, .16);
    padding: 12px 14px;
}
.pmp-save-note { font-size: 12.5px; color: var(--pmp-muted); max-width: 190px; line-height: 1.35; }
.pmp-save-note b { color: var(--pmp-off); font-weight: 650; }
.pmp-save-btn {
    background: var(--pmp-on);
    border: 1px solid var(--pmp-on);
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 620;
    cursor: pointer;
    white-space: nowrap;
}
.pmp-save-btn:hover { background: #0b5f59; border-color: #0b5f59; }
.pmp-save-btn[disabled] { opacity: .55; cursor: default; }

.pmp-warn {
    background: #fef3f2;
    border: 1px solid #fecdca;
    border-radius: 8px;
    padding: 11px 15px;
    font-size: 13.5px;
    margin-bottom: 18px;
}

@media (max-width: 640px) {
    .pmp-save-float { left: 15px; right: 15px; bottom: 15px; justify-content: space-between; }
    .pmp-save-note { max-width: none; }
}
@media (prefers-reduced-motion: reduce) {
    .pmp-item, .pmp-key, .pmp-btn, .pmp-search input { transition: none; }
}
</style>

<form method="POST"
      action="{{ action('\Modules\Superadmin\Http\Controllers\BusinessController@saveModulePermissions', [$business->id, $moduleKey]) }}"
      class="pmp-wrap">
    @csrf

    <div class="pmp-bar">
        <div class="pmp-id">
            <h2>{{ $section['title'] ?? $moduleKey }}</h2>
            {{-- IS2103: show WHICH business is being edited. The name alone is not
                 enough - several businesses share similar names across tenants, and
                 the id in the URL was the only way to tell them apart. --}}
            <p>
                {{ $business->name }}
                <span style="color:#64748b;">— ID {{ $business->id }}</span>
                @if(!empty($business->global_uid))
                    <span style="color:#94a3b8;font-size:11px;" title="Global UID">
                        · {{ \Illuminate\Support\Str::limit($business->global_uid, 13, '…') }}
                    </span>
                @endif
            </p>
        </div>

        @if(($moduleKey ?? '') !== 'banners_management')
            <div class="pmp-count">
                <b id="pmp-on-count">0</b> of <span id="pmp-total">0</span> enabled
            </div>

            <div class="pmp-search">
                <input type="search" id="pmp-filter" placeholder="Find a page or tab..." autocomplete="off">
            </div>

            {{-- Filters by SAVED state, not by what has just been ticked - so a
                 page unticked a moment ago still counts as enabled until saved. --}}
            <div class="pmp-state-tabs">
                <button type="button" class="pmp-state-tab is-on" data-state="all">All</button>
                <button type="button" class="pmp-state-tab" data-state="on">Enabled</button>
                <button type="button" class="pmp-state-tab" data-state="off">Disabled</button>
            </div>
        @endif

        <div class="pmp-actions">
            @if(($moduleKey ?? '') !== 'banners_management')
                <button type="button" class="pmp-btn" id="pmp-all" {{ (!empty($parentControlled) && empty($parentEnabled)) ? 'disabled' : '' }}>Select all</button>
                <button type="button" class="pmp-btn" id="pmp-none" {{ (!empty($parentControlled) && empty($parentEnabled)) ? 'disabled' : '' }}>Clear all</button>
            @endif
            {{-- Switch modules without returning to the index. Configuring
                 several in a row is the normal case. --}}
            {{--
              | Type to find, rather than scroll 129 modules.
              |
              | Matching covers module names AND the names of the pages inside
              | them, so "dip" finds Petro General without knowing which module
              | owns a dip chart.
              |
              | Deliberately not Select2: in this layout it positions its list
              | against the page rather than its container, which put an earlier
              | dropdown off the top of the viewport entirely.
            --}}
            <div class="pmp-jump" id="pmp-jump-wrap">
                <input type="text" id="pmp-jump-search" autocomplete="off"
                       placeholder="Go to module…"
                       value="{{ collect($allModules ?? [])->firstWhere('key', $moduleKey)['title'] ?? '' }}">
                <div class="pmp-jump-list" id="pmp-jump-list">
                    <div class="pmp-jump-tabs">
                        <button type="button" class="pmp-jump-tab is-on" data-state="all">All</button>
                        <button type="button" class="pmp-jump-tab" data-state="on">Enabled</button>
                        <button type="button" class="pmp-jump-tab" data-state="off">Disabled</button>
                    </div>
                    <div class="pmp-jump-scroll">
                        @foreach($allModules ?? [] as $m)
                            <a class="pmp-jump-item {{ $m['key'] === $moduleKey ? 'is-current' : '' }}"
                               data-find="{{ $m['find'] ?? strtolower($m['title']) }}"
                               data-on="{{ ($m['enabled'] ?? true) ? '1' : '0' }}"
                               href="{{ action('\Modules\Superadmin\Http\Controllers\BusinessController@manageModule', [$business->id, $m['key']]) }}">
                                <span class="pmp-jump-dot {{ ($m['enabled'] ?? true) ? '' : 'is-off' }}"></span>
                                <span class="pmp-jump-name">{{ $m['title'] }}</span>
                                @if($m['count'])<em>{{ $m['count'] }}</em>@endif
                            </a>
                        @endforeach
                        <div class="pmp-jump-none" id="pmp-jump-none">No module matches that.</div>
                    </div>
                </div>
            </div>
            <a class="pmp-btn" href="{{ action('\Modules\Superadmin\Http\Controllers\BusinessController@manageModuleIndex', [$business->id]) }}">All modules</a>
        </div>
    </div>

    @if(session('status') && is_array(session('status')))
        <div class="pmp-preview" style="background:{{ session('status')['success'] ? '#ecfdf5' : '#fef3f2' }};border-color:{{ session('status')['success'] ? '#a7f3d0' : '#fecdca' }};">
            <strong>{{ session('status')['success'] ? 'Saved.' : 'Not saved.' }}</strong>
            {{ session('status')['msg'] ?? '' }}
        </div>
    @endif

    @if(($moduleKey ?? '') !== 'banners_management' && !empty($parentControlled))
        <div class="pmp-module" style="border-color:{{ !empty($parentEnabled) ? '#a7f3d0' : '#fecaca' }};background:{{ !empty($parentEnabled) ? '#ecfdf5' : '#fef2f2' }};">
            <span style="width:12px;height:12px;border-radius:50%;display:inline-block;background:{{ !empty($parentEnabled) ? '#16a34a' : '#dc2626' }};"></span>
            <div class="pmp-module-text">
                <strong>Parent module: {{ !empty($parentEnabled) ? 'Enabled' : 'Disabled' }} in Manage Side Bar</strong>
                <span>Level 1 is controlled only from Super Admin → Manage Side Bar. This page controls second-level pages, tabs and features only.</span>
            </div>
        </div>
        @if(empty($parentEnabled))
            <div class="pmp-warn">
                <strong>Second-level permissions are locked while this parent is disabled.</strong>
                Enable the module in Manage Side Bar first. Existing child selections are preserved and will return when the parent is enabled again.
            </div>
        @endif
    @endif

    @if(!empty($settingItems) && $settingItems->count() > 0)
        <div class="pmp-section-head">
            <h3>Settings</h3>
        </div>
        <div class="pmp-grid" style="margin-bottom:18px;">
            @foreach($settingItems as $item)
                <label class="pmp-item" style="display:block;">
                    <span class="pmp-item-label" style="display:block;margin-bottom:7px;">
                        <span class="pmp-name">{{ $item['label'] ?? $item['key'] }}</span>
                    </span>
                    <input
                        type="{{ $item['input_type'] ?? 'text' }}"
                        name="settings[{{ $item['key'] }}]"
                        value="{{ $settingValues[$item['key']] ?? '' }}"
                        @if(isset($item['min'])) min="{{ $item['min'] }}" @endif
                        class="form-control pmp-setting">
                </label>
            @endforeach
        </div>
    @endif

    @if(($moduleKey ?? '') !== 'banners_management')
        <div class="pmp-section-head">
            <h3>{{ $moduleKey === 'other_permissions' ? 'Permissions' : 'Menu pages and tabs' }}</h3>
        </div>

        @if($childItems->count() === 0)
            <div class="pmp-empty">
                This module doesn't publish any pages or tabs yet.
                <br>They're declared in
                <code>Modules/{{ $section['folder'] ?? '…' }}/Config/module_permissions.php</code>
            </div>
        @else
            <p class="pmp-note">{{ $moduleKey === 'other_permissions' ? 'These use the same permission keys as the old Manage page.' : 'Unticking a page hides it from this business, including on its Role screen.' }}</p>

            <div class="pmp-grid" id="pmp-grid">
                @php $pmpCurrentGroup = null; @endphp
                @foreach($childItems as $item)
                    @php
                        $on = $isEnabled($item['key']);
                        $pmpGroup = (string) ($item['group'] ?? '');
                    @endphp
                    @if($moduleKey === 'purchase' && $pmpGroup !== '' && $pmpGroup !== $pmpCurrentGroup)
                        @php $pmpCurrentGroup = $pmpGroup; @endphp
                        <div class="pmp-group-heading" data-pmp-group-heading="{{ strtolower(str_replace(' ', '_', $pmpGroup)) }}">{{ $pmpGroup }}</div>
                    @endif
                    <label class="pmp-item {{ $on ? '' : 'is-off' }}"
                           data-saved-on="{{ $on ? '1' : '0' }}"
                           data-pmp-group="{{ strtolower(str_replace(' ', '_', $pmpGroup)) }}"
                           data-find="{{ strtolower(($item['label'] ?? '') . ' ' . $item['key'] . ' ' . $pmpGroup) }}">
                        <input type="checkbox" class="pmp-check pmp-track"
                            name="permissions[{{ $item['key'] }}]" value="1"
                            {{ $on ? 'checked' : '' }}
                            {{ (!empty($parentControlled) && empty($parentEnabled)) ? 'disabled' : '' }}>
                        <span class="pmp-item-label">
                            <span class="pmp-name">{{ $item['label'] ?? $item['key'] }}@if(($item['type'] ?? '') === 'tab')<span class="pmp-tag">Tab</span>@endif</span>
                            <code class="pmp-key">{{ $item['key'] }}</code>
                        </span>
                    </label>
                @endforeach
            </div>
            <div class="pmp-nomatch" id="pmp-nomatch" style="display:none;">Nothing matches that.</div>
        @endif
    @endif

    {{-- IS2102: Payment Options is its own section now, not part of Other
         Permissions. It was buried there and users could not find it. The
         section key is 'payment_options', which manageNewPermissionSections()
         registers, so it appears in the module switcher like any other. --}}
    @if(($moduleKey ?? '') === 'payment_options')
        @include('superadmin::business.partials.payment_options')
    @endif

    @if(($moduleKey ?? '') === 'banners_management')
        @include('superadmin::business.partials.banner_management')
    @endif

    <div class="pmp-save-float">
        <span class="pmp-save-note" id="pmp-save-note">No changes yet.</span>
        <button type="submit" class="pmp-save-btn" id="pmp-save" {{ (!empty($parentControlled) && empty($parentEnabled)) ? 'disabled' : '' }}>Save</button>
    </div>

</form>

<script>
(function () {
    var grid = document.getElementById('pmp-grid');
    if (!grid) { return; }

    var items  = Array.prototype.slice.call(grid.querySelectorAll('.pmp-item'));
    var filter = document.getElementById('pmp-filter');
    var onEl   = document.getElementById('pmp-on-count');
    var totEl  = document.getElementById('pmp-total');
    var none   = document.getElementById('pmp-nomatch');

    function recount() {
        var visible = items.filter(function (i) { return i.style.display !== 'none'; });
        var on = visible.filter(function (i) { return i.querySelector('.pmp-check').checked; });
        onEl.textContent  = on.length;
        totEl.textContent = visible.length;
        none.style.display = visible.length ? 'none' : 'block';
    }

    var itemState = 'all';

    function applyItemFilter() {
        var q = filter ? filter.value.trim().toLowerCase() : '';
        items.forEach(function (i) {
            var savedOn = i.getAttribute('data-saved-on') === '1';
            var okState = itemState === 'all'
                || (itemState === 'on' && savedOn)
                || (itemState === 'off' && !savedOn);
            var okText = !q || i.getAttribute('data-find').indexOf(q) !== -1;
            i.style.display = (okState && okText) ? '' : 'none';
        });
        Array.prototype.slice.call(grid.querySelectorAll('[data-pmp-group-heading]')).forEach(function (heading) {
            var group = heading.getAttribute('data-pmp-group-heading') || '';
            var hasVisible = items.some(function (item) {
                return item.getAttribute('data-pmp-group') === group && item.style.display !== 'none';
            });
            heading.style.display = hasVisible ? '' : 'none';
        });
        recount();
    }

    if (filter) {
        filter.addEventListener('input', applyItemFilter);
    }

    Array.prototype.forEach.call(document.querySelectorAll('.pmp-state-tab'), function (t) {
        t.addEventListener('click', function () {
            Array.prototype.forEach.call(document.querySelectorAll('.pmp-state-tab'), function (x) {
                x.classList.remove('is-on');
            });
            t.classList.add('is-on');
            itemState = t.getAttribute('data-state');
            applyItemFilter();
        });
    });

    /*
     | Report what the save will do, before it does it.
     |
     | A save that silently switches something off is how Petro, Contacts and
     | Realize Cheque were disabled for a live business without anyone knowing.
     */
    var note = document.getElementById('pmp-save-note');
    var initial = {};
    Array.prototype.forEach.call(document.querySelectorAll('.pmp-track'), function (el) {
        initial[el.name] = el.checked;
    });

    function reportChanges() {
        var off = 0, on = 0;
        Array.prototype.forEach.call(document.querySelectorAll('.pmp-track'), function (el) {
            if (initial[el.name] === el.checked) { return; }
            el.checked ? on++ : off++;
        });

        if (!on && !off) { note.innerHTML = 'No changes yet.'; return; }

        var parts = [];
        if (off) { parts.push('<b>' + off + ' switching off</b>'); }
        if (on) { parts.push(on + ' switching on'); }
        note.innerHTML = parts.join(', ');
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('pmp-track')) {
            var row = e.target.closest('.pmp-item');
            if (row) { row.classList.toggle('is-off', !e.target.checked); }
            reportChanges();
        }
    });

    function setAll(state) {
        items.forEach(function (i) {
            if (i.style.display === 'none') { return; }
            var checkbox = i.querySelector('.pmp-check');
            if (!checkbox || checkbox.disabled) { return; }
            checkbox.checked = state;
            i.classList.toggle('is-off', !state);
        });
        recount();
        reportChanges();
    }
    var all = document.getElementById('pmp-all');
    var clr = document.getElementById('pmp-none');
    if (all) { all.addEventListener('click', function () { setAll(true); }); }
    if (clr) { clr.addEventListener('click', function () { setAll(false); }); }

    recount();

    var jumpBox  = document.getElementById('pmp-jump-search');
    var jumpList = document.getElementById('pmp-jump-list');

    if (jumpBox && jumpList) {
        var jumpItems = Array.prototype.slice.call(jumpList.querySelectorAll('.pmp-jump-item'));
        var jumpNone  = document.getElementById('pmp-jump-none');
        var tabs      = Array.prototype.slice.call(jumpList.querySelectorAll('.pmp-jump-tab'));
        var state     = 'all';
        var cursor    = -1;

        function openList()  { jumpList.classList.add('is-open'); }
        function closeList() { jumpList.classList.remove('is-open'); cursor = -1; mark(); }
        function visible()   { return jumpItems.filter(function (i) { return i.style.display !== 'none'; }); }

        function mark() {
            jumpItems.forEach(function (i) { i.classList.remove('is-active'); });
            var open = visible();
            if (cursor >= 0 && cursor < open.length) {
                open[cursor].classList.add('is-active');
                open[cursor].scrollIntoView({ block: 'nearest' });
            }
        }

        function apply() {
            var q = jumpBox.value.trim().toLowerCase();
            jumpItems.forEach(function (i) {
                var okText = !q || i.getAttribute('data-find').indexOf(q) !== -1;
                var on = i.getAttribute('data-on') === '1';
                var okState = state === 'all' || (state === 'on' && on) || (state === 'off' && !on);
                i.style.display = (okText && okState) ? '' : 'none';
            });
            jumpNone.style.display = visible().length ? 'none' : 'block';
            cursor = -1;
            mark();
        }

        tabs.forEach(function (t) {
            t.addEventListener('click', function () {
                tabs.forEach(function (x) { x.classList.remove('is-on'); });
                t.classList.add('is-on');
                state = t.getAttribute('data-state');
                apply();
                jumpBox.focus();
            });
        });

        jumpBox.addEventListener('focus', function () { this.select(); openList(); });
        jumpBox.addEventListener('input', function () { openList(); apply(); });

        jumpBox.addEventListener('keydown', function (e) {
            var open = visible();
            if (e.key === 'ArrowDown') {
                e.preventDefault(); openList();
                cursor = Math.min(cursor + 1, open.length - 1); mark();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                cursor = Math.max(cursor - 1, 0); mark();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (cursor >= 0 && open[cursor]) { window.location = open[cursor].href; }
                else if (open.length === 1) { window.location = open[0].href; }
            } else if (e.key === 'Escape') {
                closeList(); jumpBox.blur();
            }
        });

        document.addEventListener('click', function (e) {
            var wrap = document.getElementById('pmp-jump-wrap');
            if (wrap && !wrap.contains(e.target)) { closeList(); }
        });
    }
}());
</script>
@endsection
