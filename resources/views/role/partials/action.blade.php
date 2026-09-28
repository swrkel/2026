@php
    $canViewRole = auth()->user()->can('roles.view');
    $canEditRole = auth()->user()->can('roles.update') && $canModifyRole && $editRoleEnabled;
    $canDeleteRole = auth()->user()->can('roles.delete') && $deleteRoleEnabled;
@endphp

@if($canViewRole || $canEditRole || $canDeleteRole)
    <div class="btn-group user-management-action-dropdown">
        <button type="button"
                class="btn btn-xs btn-primary dropdown-toggle"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false">
            @lang('messages.action') <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right" role="menu">
            @if($canViewRole)
                <li>
                    <a href="{{ url('roles/' . $roleId) }}" class="erp-action-child erp-action-view">
                        <i class="fa fa-eye"></i> @lang('messages.view')
                    </a>
                </li>
            @endif

            @if($canEditRole)
                <li>
                    <a href="{{ url('roles/' . $roleId . '/edit') }}" class="erp-action-child erp-action-edit">
                        <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
                    </a>
                </li>
            @endif

            @if($canDeleteRole)
                @if($deleteBlocked)
                    <li class="disabled role-delete-disabled" title="{{ $deleteBlockedReason }}">
                        <a href="javascript:void(0)"
                           class="erp-action-child erp-action-delete disabled"
                           aria-disabled="true"
                           tabindex="-1"
                           data-toggle="tooltip"
                           title="{{ $deleteBlockedReason }}">
                            <i class="glyphicon glyphicon-trash"></i> @lang('messages.delete')
                            @if($assignedUsersCount > 0)
                                <span class="label label-warning pull-right">In use</span>
                            @endif
                        </a>
                    </li>
                @else
                    <li>
                        <a href="javascript:void(0)"
                           data-href="{{ url('roles/' . $roleId) }}"
                           class="erp-action-child erp-action-delete delete_role_button">
                            <i class="glyphicon glyphicon-trash"></i> @lang('messages.delete')
                        </a>
                    </li>
                @endif
            @endif
        </ul>
    </div>
@endif
