@php
    $canEditUser = auth()->user()->can('user.update');
    $canViewUser = auth()->user()->can('user.view');
    $canDeleteUser = auth()->user()->can('user.delete');
@endphp

@if($canEditUser || $canViewUser || $canDeleteUser)
    <div class="btn-group user-management-action-dropdown">
        <button type="button"
                class="btn btn-xs btn-primary dropdown-toggle"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false">
            @lang('messages.action') <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right" role="menu">
            @if($canEditUser)
                <li>
                    <a href="{{ url('users/' . $userId . '/edit') }}" class="erp-action-child erp-action-edit">
                        <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
                    </a>
                </li>
            @endif

            @if($canViewUser)
                <li>
                    <a href="{{ url('users/' . $userId) }}" class="erp-action-child erp-action-view">
                        <i class="fa fa-eye"></i> @lang('messages.view')
                    </a>
                </li>
            @endif

            @if($canDeleteUser)
                @if($deleteBlocked)
                    <li class="disabled user-delete-disabled" title="{{ $deleteBlockedReason }}">
                        <a href="javascript:void(0)"
                           class="erp-action-child erp-action-delete disabled"
                           aria-disabled="true"
                           tabindex="-1"
                           data-toggle="tooltip"
                           title="{{ $deleteBlockedReason }}">
                            <i class="glyphicon glyphicon-trash"></i> @lang('messages.delete')
                            <span class="label label-warning pull-right">{{ $deleteBlockedLabel ?? 'Protected' }}</span>
                        </a>
                    </li>
                @else
                    <li>
                        <a href="javascript:void(0)"
                           data-href="{{ url('users/' . $userId) }}"
                           class="erp-action-child erp-action-delete delete_user_button">
                            <i class="glyphicon glyphicon-trash"></i> @lang('messages.delete')
                        </a>
                    </li>
                @endif
            @endif
        </ul>
    </div>
@endif
