{{--
 | Standalone Stock Reports permission.
 |
 | On the legacy Role screen this is the established application permission.
 | On UserManagementNew the Stock Reports -> View module right above is the
 | authoritative control; RolePermissionService derives stock_report.view from
 | it, so we deliberately avoid rendering a second checkbox there.
--}}
@if (empty($umn_managed_role_form))
    <div class="row check_group">
        <div class="col-md-1">
            <h4><label>Stock Reports</label></h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox(
                            'permissions[]',
                            'stock_report.view',
                            in_array('stock_report.view', $role_permissions ?? [], true),
                            ['class' => 'input-icheck']
                        ) !!}
                        {{ __('role.stock_report.view') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr class="blue-hr">
@endif
