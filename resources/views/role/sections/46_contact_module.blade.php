                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'contact.customer_statement' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'contact.delete_customer_statement', in_array('contact.delete_customer_statement', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.contact.delete_customer_statement' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'contact.delete_statement_payment', in_array('contact.delete_statement_payment', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.contact.delete_statement_payment' ) }}
                                </label>
                            </div>
                        </div>
                    
                    
                    
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'enable_separate_customer_statement_no',
                                    in_array('enable_separate_customer_statement_no', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'contact.enable_separate_customer_statement_no' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_customer_statement', in_array('edit_customer_statement',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'contact.edit_customer_statement' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
