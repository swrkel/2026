            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'deposits.deposits_module' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'deposit.access', in_array('deposit.access', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'deposits.deposits_module' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'deposit.cash_deposit', in_array('deposit.cash_deposit', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'deposits.cash_deposit' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'deposit.card_deposit', in_array('deposit.card_deposit',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'deposits.card_deposit' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'deposit.cheque_deposit', in_array('deposit.cheque_deposit',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'deposits.cheque_deposit' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'deposit.transfer', in_array('deposit.transfer',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'deposits.transfer' ) }}
                            </label>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'deposit.realize_cheque', in_array('deposit.realize_cheque',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'deposits.realize_cheque' ) }}
                            </label>
                        </div>
                    </div>
                   
                </div>
            </div>
            <hr class="blue-hr">
