            
             <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'customer_payments.customer_payments' ):</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'list_customer_payments.edit', in_array('list_customer_payments.edit', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer_payments.edit' ) }}
                            </label>
                        </div>
                    </div>                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'list_customer_payments.delete', in_array('list_customer_payments.delete', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'customer_payments.delete' ) }}
                            </label>
                        </div>
                    </div>                    
                </div>
            </div>
            <hr class="blue-hr">
             <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.Edit_Customer_Cheque_Return' ):</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'edit_customer_cheque_Return.edit', in_array('edit_customer_cheque_Return.edit',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit_customer_cheque_Return.edit' ) }}
                            </label>
                        </div>
                    </div>                    
                </div>
            </div>
            <hr class="blue-hr">
            
