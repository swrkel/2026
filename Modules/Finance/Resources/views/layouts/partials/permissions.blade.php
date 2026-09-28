@php
    $financePermissions = require module_path('Finance', 'Config/permissions.php');
@endphp

<div class="finance-permissions-wrapper">
    <h4>{{ (($label = __('finance::account.finance')) === 'finance::account.finance') ? 'Finance Module' : $label }}</h4>
    <div class="row">
        @foreach($financePermissions as $permissionKey => $permissionLabel)
            <div class="col-md-4 col-sm-6">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}"
                            @if(!empty($role_permissions) && in_array($permissionKey, $role_permissions)) checked @endif>
                        {{ $permissionLabel }}
                    </label>
                </div>
            </div>
        @endforeach
    </div>
</div>
