@extends('layouts.app')
@section('title', $role->exists ? 'Edit Role' : 'Add Role')

@section('content')
<link rel="stylesheet" href="{{ asset('modules/usermanagementnew/css/roles.css') }}?v=5">
@php
    $isEdit = $role->exists;
    $formAction = $isEdit
        ? route('user-management-new.roles.update', $role->id)
        : route('user-management-new.roles.store');
    $permissionService = app(\Modules\UserManagementNew\Services\RolePermissionService::class);
@endphp

<section class="content-header umn-page-heading">
    <div>
        <span class="umn-eyebrow">USER MANAGEMENT</span>
        <h1>{{ $isEdit ? 'Edit Role' : 'Create Role' }}</h1>
        <p>Only modules, menu pages and tabs allowed for this business are shown.</p>
    </div>
    <a class="btn btn-default" href="{{ route('user-management-new.roles.index') }}"><i class="fa fa-arrow-left"></i> Back</a>
</section>

<section class="content">
    @if ($errors->any())
        <div class="alert alert-danger"><strong>Please correct the highlighted fields.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="umn-role-form">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="umn-role-summary">
            <div class="form-group">
                <label for="umn-role-name">Role name <span>*</span></label>
                <input id="umn-role-name" name="name" class="form-control" required maxlength="191"
                       value="{{ old('name', $displayName) }}" placeholder="Example: Branch Manager">
            </div>
            <label class="umn-switch-row">
                <input type="checkbox" name="is_service_staff" value="1" {{ old('is_service_staff', $role->is_service_staff) ? 'checked' : '' }}>
                <span>Service staff role</span>
                <small>Use this only when the role is assigned to service staff.</small>
            </label>
        </div>

        <div class="umn-toolbar">
            <div class="umn-permission-search-panel">
                <label for="umn-permission-search">
                    <i class="fa fa-search"></i> Search permissions
                </label>
                <div class="umn-search-wrap">
                    <div class="umn-search umn-search--primary">
                        <i class="fa fa-search"></i>
                        <input id="umn-permission-search" type="search"
                               placeholder="Type or select a module, menu page or tab..."
                               autocomplete="off"
                               role="combobox"
                               aria-expanded="false"
                               aria-controls="umn-permission-search-results">
                        <span class="umn-search-hint">Type &amp; auto filter</span>
                        <button type="button" id="umn-permission-search-clear"
                                class="umn-search-clear" hidden
                                title="Clear search" aria-label="Clear search">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div id="umn-permission-search-results"
                         class="umn-search-dropdown"
                         role="listbox"
                         hidden></div>
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-default" data-umn-expand="all"><i class="fa fa-angle-double-down"></i> Expand all</button>
                <button type="button" class="btn btn-default" data-umn-collapse="all"><i class="fa fa-angle-double-up"></i> Collapse all</button>
            </div>
        </div>

        @forelse ($sections as $section)
            @php $moduleKey = $section['module_key']; @endphp
            <article class="umn-module-card is-collapsed" data-search="{{ strtolower($section['title'].' '.collect($section['items'])->pluck('label')->implode(' ')) }}">
                <header class="umn-module-card__header">
                    <button type="button" class="umn-module-toggle" aria-expanded="false">
                        <span class="umn-module-icon"><i class="fa fa-cubes"></i></span>
                        <span><strong>{{ $section['title'] }}</strong><small>{{ count($section['items']) }} menu page(s) / tab(s)</small></span>
                        <i class="fa fa-chevron-down"></i>
                    </button>
                    <label class="umn-select-module"><input type="checkbox" data-umn-module-all> Select entire module</label>
                </header>
                <div class="umn-module-card__body">
                    <section>
                        <h4>Module action rights</h4>
                        <p class="umn-help">These rights apply to actions throughout this module.</p>
                        <div class="umn-right-grid">
                            @foreach ($rights as $rightKey => $right)
                                @php $permission = $permissionService->modulePermission($moduleKey, $rightKey); @endphp
                                <label class="umn-permission-tile">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                                           {{ old('permissions') ? (in_array($permission, old('permissions', []), true) ? 'checked' : '') : (isset($selected[$permission]) ? 'checked' : '') }}>
                                    <span><i class="fa {{ $right['icon'] }}"></i><strong>{{ $right['label'] }}</strong></span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                    <section class="umn-pages-section">
                        <h4>Menu pages &amp; tabs</h4>
                        <p class="umn-help">Each page or tab has its own access permission.</p>
                        <div class="umn-page-grid">
                            @forelse ($section['items'] as $item)
                                @php $permission = $permissionService->pagePermission($item['key']); @endphp
                                <label class="umn-page-tile">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                                           {{ old('permissions') ? (in_array($permission, old('permissions', []), true) ? 'checked' : '') : (isset($selected[$permission]) ? 'checked' : '') }}>
                                    <span class="umn-page-type">{{ strtoupper($item['type']) }}</span>
                                    <span><i class="fa {{ $item['type'] === 'tab' ? 'fa-folder-o' : 'fa-file-o' }}"></i> {{ $item['label'] }}</span>
                                </label>
                            @empty
                                <div class="umn-empty-inline">No enabled menu pages or tabs are registered for this module.</div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </article>
        @empty
            <div class="umn-empty-state"><i class="fa fa-lock"></i><h3>No assignable modules</h3><p>Enable modules in Manage Side Bar, then enable their pages in Manage Page.</p></div>
        @endforelse

        {{--
         | S640/S642: the application permissions.
         |
         | The module rights above create umn.* permissions, which only ever HIDE
         | and DENY - the sidebar and EnforceManagedRolePermissions use them for
         | that. They grant nothing. Everything in this application authorises on
         | its own permission names, so a role saved here previously held umn.*
         | and nothing else, and the user could not do anything.
         |
         | These are the same partials the legacy Role screen renders - one list,
         | maintained in one place (resources/views/role/_sections.php), so the two
         | screens cannot drift apart the way create and edit did before the
         | MA-002 review.
         |
         | The marker below tells RoleController that these sections were actually
         | rendered. Without it the controller preserves whatever the role already
         | holds, so an older cached form cannot strip every permission on save.
        --}}
        <input type="hidden" name="application_permissions_rendered" value="1">

        <article class="umn-module-card">
            <div class="umn-module-head">
                <h3><i class="fa fa-key"></i> Application Permissions</h3>
                <p>What this role can actually do. The module rights above control what it can see.</p>
            </div>
            <div class="umn-module-body">
                @include('role._driver', ['mode' => $mode, 'umn_managed_role_form' => true])
            </div>
        </article>

        <button class="btn btn-primary umn-floating-save" type="submit"
                title="{{ $isEdit ? 'Update Role' : 'Save Role' }}">
            <i class="fa fa-save"></i>
            <span>{{ $isEdit ? 'Update Role' : 'Save Role' }}</span>
        </button>

        <div class="umn-save-bar">
            <span><i class="fa fa-shield"></i> Permissions apply only to this business.</span>
            <button class="btn btn-primary umn-primary-btn" type="submit"><i class="fa fa-save"></i> {{ $isEdit ? 'Update Role' : 'Save Role' }}</button>
        </div>
    </form>
</section>
@endsection

@section('javascript')
<script src="{{ asset('modules/usermanagementnew/js/roles.js') }}?v=4"></script>
@endsection
