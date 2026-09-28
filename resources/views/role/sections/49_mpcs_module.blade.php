                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.MPCS' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'mpcs.access', in_array('mpcs.access',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.mpcs.access' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f9c_form', in_array('f9c_form',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.f9c_form' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f15a9abc_form', in_array('f15a9abc_form',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.f15a9abc_form' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f16a_form', in_array('f16a_form',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.f16a_form' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f21c_form', in_array('f21c_form',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.f21c_form' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f17_form', in_array('f17_form',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.f17_form' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f14b_form', in_array('f14b_form',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.f14b_form' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f20_form', in_array('f20_form',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.f20_form' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f21_form', in_array('f21_form',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.f21_form' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f22_stock_taking_form', in_array('f22_stock_taking_form',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.f22_stock_taking_form' ) }}
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_f22_stock_Taking_form', in_array('edit_f22_stock_Taking_form',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit_f22_stock_Taking_form' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f10_form', in_array('f10_form', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.f10_form' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_f17_form', in_array('edit_f17_form', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit_f17_form' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f10_form_cash_given_button', in_array('f10_form_cash_given_button', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.f10_form_cash_given_button' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f10_form_cash_received_button', in_array('f10_form_cash_received_button', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.f10_form_cash_received_button' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'mpcs_form_settings', in_array('mpcs_form_settings',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.mpcs_form_settings' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'list_opening_values', in_array('list_opening_values',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.list_opening_values' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'signature_approve', in_array('signature_approve',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'Signature-Approve' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'f22_authorized_signature_form', in_array('f22_authorized_signature_form',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'Authorized to sign F22 Stock taking Page' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'stock_taking_approve', in_array('stock_taking_approve',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'Stock-Taking-Approve' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
