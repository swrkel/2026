@extends('layouts.app')
@section('title', __('messages.view') . ' ' . __('user.role'))

@section('content')
<link rel="stylesheet" href="{{ asset('css/role-permission-editor.css') }}?v=059">

<section class="content-header role-view-header">
    <h1>
        @lang('messages.view') @lang('user.role')
        <small>{{ $displayRoleName }}</small>
    </h1>
    <div class="role-view-header-actions no-print">
        <a href="{{ action('RoleController@index') }}" class="btn btn-default">
            <i class="fa fa-arrow-left"></i> @lang('lang_v1.back')
        </a>
        @can('roles.update')
            @if($canModifyRole && $editRoleEnabled)
                <a href="{{ action('RoleController@edit', [$role->id]) }}" class="btn btn-primary">
                    <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
                </a>
            @endif
        @endcan
    </div>
</section>

<section class="content role-view-page">
    <div class="role-view-summary-grid">
        <div class="role-view-summary-card role-view-summary-primary">
            <span class="role-view-summary-icon"><i class="fa fa-id-badge"></i></span>
            <div>
                <span class="role-view-summary-label">@lang('user.role_name')</span>
                <strong>{{ $displayRoleName }}</strong>
            </div>
        </div>
        <div class="role-view-summary-card">
            <span class="role-view-summary-icon"><i class="fa fa-check-square-o"></i></span>
            <div>
                <span class="role-view-summary-label">@lang('user.permissions')</span>
                <strong>{{ $role->permissions->count() }}</strong>
            </div>
        </div>
        <div class="role-view-summary-card">
            <span class="role-view-summary-icon"><i class="fa fa-users"></i></span>
            <div>
                <span class="role-view-summary-label">Assigned Users</span>
                <strong>{{ $assignedUsers->count() }}</strong>
            </div>
        </div>
        <div class="role-view-summary-card">
            <span class="role-view-summary-icon"><i class="fa fa-shield"></i></span>
            <div>
                <span class="role-view-summary-label">Role Type</span>
                <strong>{{ $role->is_default ? 'System / Default' : 'Custom' }}</strong>
            </div>
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary role-view-permissions-box', 'title' => 'Permitted Permissions'])
        <p class="role-view-section-help">
            Every permission currently assigned to this role is listed automatically below.
        </p>

        <div class="role-view-permission-list" id="role_view_permission_list">
            @forelse($role->permissions as $permission)
                @php
                    $permissionLabel = \Illuminate\Support\Str::of($permission->name)
                        ->replace(['.', '_', '-'], ' ')
                        ->squish()
                        ->title();
                @endphp
                <div class="role-view-permission-item">
                    <span class="role-view-permission-check"><i class="fa fa-check"></i></span>
                    <span>
                        <strong>{{ $permissionLabel }}</strong>
                        <small>{{ $permission->name }}</small>
                    </span>
                </div>
            @empty
                <div class="role-view-empty-state">
                    <i class="fa fa-info-circle"></i>
                    <span>No permissions are currently assigned to this role.</span>
                </div>
            @endforelse
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-info role-view-users-box', 'title' => 'Users Assigned to this Role'])
        @if($assignedUsers->isEmpty())
            <div class="role-view-empty-state">
                <i class="fa fa-user-times"></i>
                <span>No users are currently assigned to this role.</span>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped role-view-users-table">
                    <thead>
                        <tr>
                            <th>@lang('user.name')</th>
                            <th>@lang('business.username')</th>
                            <th>@lang('business.email')</th>
                            <th>@lang('sale.status')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignedUsers as $assignedUser)
                            <tr>
                                <td>{{ trim($assignedUser->first_name . ' ' . $assignedUser->last_name) ?: '—' }}</td>
                                <td>{{ $assignedUser->username ?: '—' }}</td>
                                <td>{{ $assignedUser->email ?: '—' }}</td>
                                <td>
                                    @if(method_exists($assignedUser, 'trashed') && $assignedUser->trashed())
                                        <span class="label label-default">Deleted</span>
                                    @else
                                        <span class="label label-success">Active</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endcomponent
</section>
@endsection
