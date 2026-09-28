            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.unfinished_form' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'unfinished_form.purchase', in_array('unfinished_form.purchase',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unfinished_form.purchase' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'unfinished_form.sale', in_array('unfinished_form.sale',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unfinished_form.sale' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'unfinished_form.pos', in_array('unfinished_form.pos',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unfinished_form.pos' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'unfinished_form.stock_adjustment',
                                in_array('unfinished_form.stock_adjustment',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unfinished_form.stock_adjustment' ) }}
                            </label>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'unfinished_form.stock_transfer',
                                in_array('unfinished_form.stock_transfer',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unfinished_form.stock_transfer' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'unfinished_form.expense', in_array('unfinished_form.expense',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unfinished_form.expense' ) }}
                            </label>
                        </div>
                    </div>


                </div>
            </div>
            <hr class="blue-hr">
