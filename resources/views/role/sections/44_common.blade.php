            
            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'account.account' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'account.access', in_array('account.access', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.access_accounts' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'account.edit', in_array('account.edit', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.edit_accounts' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'account.link_account', in_array('account.link_account',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.link_account' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'account.reconcile', in_array('account.reconcile',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.reconcile' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'account.unreconcile', in_array('account.unreconcile',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.unreconcile' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'account.settings', in_array('account.settings',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.account_settings' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'account.settings.edit', in_array('account.settings.edit',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.account_settings_edit' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'account.deposit_transfer.edit', in_array('account.deposit_transfer.edit',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.deposit_transfer_edit' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'edit.cheque_ob', in_array('edit.cheque_ob',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.edit_cheque_ob' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'delete.cheque_ob', in_array('delete.cheque_ob',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.delete_cheque_ob' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'account.realize_cheque', in_array('account.realize_cheque', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'account.realize_cheque' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
            
