                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.issue_customer_bill' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'issue_customer_bill.access', in_array('issue_customer_bill.access',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.issue_customer_bill.access' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'issue_customer_bill.add', in_array('issue_customer_bill.add',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.issue_customer_bill.add' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'issue_customer_bill.view', in_array('issue_customer_bill.view',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.issue_customer_bill.view' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
