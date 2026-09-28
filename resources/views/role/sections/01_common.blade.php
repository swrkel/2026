            
            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>Offline Features:</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'offline.access', in_array('offline.access', $role_permissions),
                                [ 'class' => 'input-icheck']) !!} Enable offline mode & queuing
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'offline.sync.manage', in_array('offline.sync.manage', $role_permissions),
                                [ 'class' => 'input-icheck']) !!} Manage auto-sync interval
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
            <div class="row">
                <div class="col-md-3">
                    <label>@lang( 'user.permissions' ):</label>
                </div>
            </div>
            
            <!-- new module permissions-->
            
            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang('lang_v1.customer_loans'):</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'edit_customer_loan', in_array('edit_customer_loan', $role_permissions), [
                                    'class' => 'input-icheck',
                                ]) !!} {{ __('lang_v1.edit_customer_loan') }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox(
                                    'permissions[]',
                                    'delete_customer_loan',
                                    in_array('delete_customer_loan', $role_permissions),
                                    ['class' => 'input-icheck'],
                                ) !!} {{ __('lang_v1.delete_customer_loan') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
            
