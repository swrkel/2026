@extends('layouts.app')
@section('title', 'Roles & Permissions')

@section('content')
<link rel="stylesheet" href="{{ asset('modules/usermanagementnew/css/roles.css') }}?v=1">
<section class="content-header umn-page-heading">
    <div>
        <span class="umn-eyebrow">USER MANAGEMENT</span>
        <h1>Roles &amp; Permissions</h1>
        <p>Business-specific access based on Manage Side Bar and Manage Page settings.</p>
    </div>
    @can('roles.create')
        <a class="btn btn-primary umn-primary-btn" href="{{ route('user-management-new.roles.create') }}">
            <i class="fa fa-plus"></i> Add Role
        </a>
    @endcan
</section>

<section class="content">
    @if (session('status'))
        <div class="alert {{ data_get(session('status'), 'success') ? 'alert-success' : 'alert-danger' }}">
            {{ data_get(session('status'), 'msg') }}
        </div>
    @endif

    <div class="umn-list-card">
        <div class="umn-list-card__head">
            <div>
                <h3>Business Roles</h3>
                <p>Roles listed here belong only to the current business.</p>
            </div>
            <div class="umn-search">
                <i class="fa fa-search"></i>
                <input type="search" id="umn-role-search" placeholder="Search roles">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table umn-table" id="umn-role-table">
                <thead>
                    <tr><th>Role</th><th>Assigned Users</th><th>Status</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        @php
                            $roleName = preg_replace('/#' . preg_quote((string) $businessId, '/') . '$/', '', $role->name);
                            $managed = $role->permissions()->where('name', \Modules\UserManagementNew\Services\RolePermissionService::MANAGED)->exists();
                        @endphp
                        <tr data-role-name="{{ strtolower($roleName) }}">
                            <td><strong>{{ $roleName }}</strong><small>Business role</small></td>
                            <td><span class="umn-count">{{ (int) $role->users_count }}</span></td>
                            <td><span class="umn-badge {{ $managed ? 'is-managed' : 'is-legacy' }}">{{ $managed ? 'Managed' : 'Legacy' }}</span></td>
                            <td class="text-right">
                                <a class="umn-icon-btn" title="View" href="{{ route('user-management-new.roles.show', $role->id) }}"><i class="fa fa-eye"></i></a>
                                @can('roles.update')
                                    <a class="umn-icon-btn umn-icon-btn-edit" title="Edit" href="{{ route('user-management-new.roles.edit', $role->id) }}"><i class="fa fa-edit"></i></a>
                                @endcan
                                @can('roles.delete')
                                    <form class="umn-inline-delete" method="POST" action="{{ route('user-management-new.roles.destroy', $role->id) }}">
                                        @csrf @method('DELETE')
                                        {{-- S651: a role in use cannot be deleted, and now SAYS so
                                             before it is clicked.

                                             RoleController::destroy() already refuses an assigned or
                                             protected role, but the button looked live and only
                                             explained itself after a round trip. Disabling it here
                                             makes the rule visible; the server check stays as the
                                             real guard, because a disabled button is a courtesy,
                                             not a control. --}}
                                        @php $roleInUse = (int) $role->users_count > 0; @endphp
                                        <button class="umn-icon-btn is-danger"
                                                title="{{ $roleInUse
                                                    ? 'This role cannot be deleted because ' . (int) $role->users_count . ' user(s) are assigned to it.'
                                                    : 'Delete' }}"
                                                type="submit"
                                                data-confirm-role="{{ $roleName }}"
                                                @disabled($roleInUse)><i class="fa fa-trash"></i></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="umn-empty">No roles are available for this business.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('modules/usermanagementnew/js/roles.js') }}?v=1"></script>
@endsection
