            <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'essentials::lang.essentials' )</label></h4>
                    </div>
                    <div class="col-md-2">
                        <div class="checkbox">
                            <input type="checkbox" class="check_all input-icheck"> {{ __( 'role.select_all' ) }}
                        </div>
                    </div>
                    <div class="col-md-9">
                        
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.crud_all_attendance', in_array('essentials.crud_all_attendance',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.crud_all_attendance') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.view_own_attendance', in_array('essentials.view_own_attendance',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.view_own_attendance') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.allow_users_for_attendance_from_web', in_array('essentials.allow_users_for_attendance_from_web',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.allow_users_for_attendance_from_web') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.view_allowance_and_deduction', in_array('essentials.view_allowance_and_deduction',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.view_allowance_and_deduction') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.crud_all_leave', in_array('essentials.crud_all_leave',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.crud_all_leave') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.crud_own_leave', in_array('essentials.crud_own_leave',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.crud_own_leave') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.crud_leave_type', in_array('essentials.crud_leave_type',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.crud_leave_type') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.view_all_payroll', in_array('essentials.view_all_payroll',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.view_all_payroll') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.create_payroll', in_array('essentials.create_payroll',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.create_payroll') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.update_payroll', in_array('essentials.update_payroll',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.update_payroll') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.delete_payroll', in_array('essentials.delete_payroll',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.delete_payroll') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.access_sales_target', in_array('essentials.access_sales_target',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.access_sales_target') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.edit_todos', in_array('essentials.edit_todos',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.edit_todos') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.delete_todos', in_array('essentials.delete_todos',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.delete_todos') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.add_todos', in_array('essentials.add_todos',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.add_todos') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'edit_essentials_settings', in_array('edit_essentials_settings',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.edit_essentials_settings') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.crud_department', in_array('essentials.crud_department',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.crud_department') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.crud_designation', in_array('essentials.crud_designation',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.crud_designation') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'add_essentials_leave_type', in_array('add_essentials_leave_type',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.add_essentials_leave_type') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.create_message', in_array('essentials.create_message',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.create_message') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.view_message', in_array('essentials.view_message',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.view_message') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.approve_leave', in_array('essentials.approve_leave',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.approve_leave') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.assign_todos', in_array('essentials.assign_todos',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.assign_todos') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'essentials.add_allowance_and_deduction', in_array('essentials.add_allowance_and_deduction',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('essentials::lang.add_allowance_and_deduction') }}
                                    </label>
                                </div>
                            </div>
                         
                    </div>
                </div>
            <hr class="blue-hr">
