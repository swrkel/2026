{{--
    Permission Search / Explorer
    The role editor uses a dedicated non-collapsing context so Role Name and
    assigned permissions can never be hidden by the Manage-page explorer logic.
--}}
@php
    $permissionSearchInlineMode = !empty($permission_search_inline);
    $permissionSearchContext = $permission_search_context
        ?? ($permissionSearchInlineMode ? 'inline' : 'manage');
    $permissionSearchRoleMode = $permissionSearchContext === 'role';
@endphp

<link rel="stylesheet" href="{{ asset('css/sa-permission-search.css') }}?v=059">
@if(!$permissionSearchRoleMode)
    <link rel="stylesheet" href="{{ asset('css/sa-module-permission-hierarchy.css') }}?v=060">
@endif

<div class="sa-permission-explorer{{ $permissionSearchInlineMode ? ' sa-permission-explorer-inline' : '' }}{{ $permissionSearchRoleMode ? ' sa-permission-explorer-role' : '' }}"
     id="sa_permission_explorer"
     data-sa-inline="{{ $permissionSearchInlineMode ? '1' : '0' }}"
     data-sa-context="{{ $permissionSearchContext }}">
    <div class="sa-permission-search-line">
        <span class="sa-search-icon"><i class="fa fa-search"></i></span>
        <input type="text"
               id="sa_permission_search_input"
               class="sa-permission-search-input"
               autocomplete="off"
               spellcheck="false"
               placeholder="Search permissions by module, page or tab">
        <button type="button" id="sa_permission_search_clear" class="sa-permission-clear" title="Clear search">
            <i class="fa fa-times"></i>
        </button>
        <button type="button"
                id="sa_permission_toggle_panel"
                class="sa-permission-mini-btn sa-permission-toggle-btn"
                aria-expanded="true"
                title="Minimize search details">Minimize</button>
    </div>

    <div class="sa-search-meta">
        <span id="sa_permission_search_counter">Ready</span>
        <span class="sa-search-help">
            @if($permissionSearchRoleMode)
                All permission options remain visible. Search only navigates to and highlights a permission.
            @else
                All sections stay collapsed. Type anywhere, then click a result to open only that section.
            @endif
        </span>
    </div>

    @unless($permissionSearchRoleMode)
        <div class="sa-search-actions">
            <button type="button" id="sa_permission_expand_all" class="sa-permission-mini-btn">Expand All</button>
            <button type="button" id="sa_permission_collapse_all" class="sa-permission-mini-btn">Collapse All</button>
        </div>
    @endunless

    <div id="sa_permission_search_results" class="sa-permission-search-results" style="display:none;"></div>
</div>

<script src="{{ asset('js/sa-permission-search.js') }}?v=059"></script>
@if(!$permissionSearchRoleMode)
    <script src="{{ asset('js/sa-module-permission-hierarchy.js') }}?v=060"></script>
@endif
