            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'superadmin::lang.vat_module' ):</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'vat.delete_customer_statement', in_array('vat.delete_customer_statement', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.vat.delete_customer_statement' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'vat.delete_statement_payment', in_array('vat.delete_statement_payment', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.vat.delete_statement_payment' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'vat_sale', in_array('vat_sale', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'superadmin::lang.vat_sale' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'list_vat_sale', in_array('list_vat_sale', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'superadmin::lang.list_vat_sale' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'vat_purchase', in_array('vat_purchase', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'superadmin::lang.vat_purchase' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'list_vat_purchase', in_array('list_vat_purchase', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'superadmin::lang.list_vat_purchase' ) }}
                            </label>
                        </div>
                    </div>
                    
                     <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'vat_expense', in_array('vat_expense', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'superadmin::lang.vat_expense' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'list_vat_expense', in_array('list_vat_expense', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'superadmin::lang.list_vat_expense' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'vat_products', in_array('vat_products', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'superadmin::lang.vat_products' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'vat_contacts', in_array('vat_contacts', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'superadmin::lang.vat_contacts' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'edit.vat_statement', in_array('edit.vat_statement', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'vat::lang.edit.vat_statement' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'vat_edit_invoice127', in_array('vat_edit_invoice127', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.vat_invoice127' ) }}
                            </label>
                        </div>
                    </div>
                    
                </div>
            </div>
            <hr class="blue-hr">
