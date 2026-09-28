
            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.expense' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'expense.create', in_array('expense.create', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.expense.create' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'expense.update', in_array('expense.update', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.expense.update' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'expense.delete', in_array('expense.delete', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.expense.delete' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'expense.add_payment', in_array('expense.add_payment',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.expense.add_payment' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">

