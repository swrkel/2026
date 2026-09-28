{{--
    Super Admin Permission Search / Explorer.
    $section_heading_only=true is used by the Business Manage page so the
    result list contains module/section headings only. Package pages keep
    their existing permission-level search.
--}}
@php
    $sectionHeadingOnly = !empty($section_heading_only);
@endphp
<link rel="stylesheet" href="{{ asset('css/sa-permission-search.css') }}?v=055">

<div class="sa-permission-explorer" id="sa_permission_explorer" data-search-mode="{{ $sectionHeadingOnly ? 'sections' : 'permissions' }}">
    <div class="sa-permission-search-line">
        <span class="sa-search-icon"><i class="fa fa-search"></i></span>
        <input type="text"
               id="sa_permission_search_input"
               class="sa-permission-search-input"
               autocomplete="off"
               spellcheck="false"
               placeholder="{{ $sectionHeadingOnly ? 'Search section heading' : 'Search permission' }}">
        <button type="button" id="sa_permission_search_clear" class="sa-permission-clear" title="Clear search">
            <i class="fa fa-times"></i>
        </button>
        <button type="button" id="sa_permission_toggle_panel" class="sa-permission-mini-btn sa-permission-toggle-btn" aria-expanded="true" title="Minimize search details">Minimize</button>
    </div>
    <div class="sa-search-meta">
        <span id="sa_permission_search_counter">Ready</span>
        <span class="sa-search-help">{{ $sectionHeadingOnly ? 'Type a section heading, then click it to open the complete section.' : 'Type a permission, then click a result to open it.' }}</span>
    </div>
    <div class="sa-search-actions">
        <button type="button" id="sa_permission_expand_all" class="sa-permission-mini-btn">Expand All</button>
        <button type="button" id="sa_permission_collapse_all" class="sa-permission-mini-btn">Collapse All</button>
    </div>
    <div id="sa_permission_search_results" class="sa-permission-search-results" style="display:none;"></div>
</div>

<script src="{{ asset('js/sa-permission-search.js') }}?v=055"></script>
