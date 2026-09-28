                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'lang_v1.enable_cheque_writing' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'enable_cheque_templates', in_array('enable_cheque_templates',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.Templates' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'enable_cheque_add_new_template', in_array('enable_cheque_add_new_template',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.Add new Template' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'enable_cheque_writing', in_array('enable_cheque_writing',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.Write Cheque' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'enable_cheque_manage_stamps', in_array('enable_cheque_manage_stamps',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.Manage Stamps' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'enable_cheque_manage_payee', in_array('enable_cheque_manage_payee',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.Manage Payee' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'enable_cheque_number_list', in_array('enable_cheque_number_list',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.Cheque Number List' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'enable_cheque_delete_numbers', in_array('enable_cheque_delete_numbers',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.Delete Cheque Numbers' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'enable_cheque_printed_details', in_array('enable_cheque_printed_details',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.Printed Cheque Details' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'enable_cheque_default_settings', in_array('enable_cheque_default_settings',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.Default Settings' ) }}
                                </label>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <hr class="blue-hr">
