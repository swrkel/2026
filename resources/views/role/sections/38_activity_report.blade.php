            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.activity_report' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'sales_report.view', in_array('sales_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.sales_report.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'purchase_and_slae_report.view',
                                in_array('purchase_and_slae_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase_and_slae_report.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'expense_report.view', in_array('expense_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.expense_report.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'sales_representative.view', in_array('sales_representative.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.sales_representative.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'tax_report.view', in_array('tax_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_report.view' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
