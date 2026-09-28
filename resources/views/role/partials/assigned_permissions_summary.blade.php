@php
    $summaryPermissions = collect($permissions ?? []);
    $summaryPermissionNames = $summaryPermissions
        ->map(function ($permission) {
            return is_object($permission) ? $permission->name : (string) $permission;
        })
        ->filter()
        ->unique()
        ->values();
@endphp

<script type="application/json" id="role_initial_assigned_permissions">@json($summaryPermissionNames)</script>

<div class="role-assigned-permissions-card role-stable-card" id="role_assigned_permissions_card">
    <div class="role-card-heading">
        <div>
            <h4>{{ $summaryTitle ?? 'Assigned Permissions' }}</h4>
            <p>{{ $summaryHelp ?? 'The permissions currently selected for this role are listed below. You can change any checkbox and save the role.' }}</p>
        </div>
        <span class="role-permission-count-badge">
            <strong id="role_selected_permission_count">{{ $summaryPermissionNames->count() }}</strong>
            selected
        </span>
    </div>

    <div id="role_assigned_permissions_list" class="role-assigned-permissions-list" aria-live="polite">
        @forelse($summaryPermissions as $permission)
            @php
                $permissionName = is_object($permission) ? $permission->name : (string) $permission;
                $permissionLabel = \Illuminate\Support\Str::of($permissionName)
                    ->replace(['.', '_', '-'], ' ')
                    ->squish()
                    ->title();
            @endphp
            <span class="role-permission-chip" data-permission-name="{{ $permissionName }}" title="{{ $permissionName }}">
                <i class="fa fa-check-circle"></i> {{ $permissionLabel }}
            </span>
        @empty
            <span class="role-no-permissions">No permissions selected yet.</span>
        @endforelse
    </div>
</div>
