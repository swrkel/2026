            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.product_reports' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'stock_adjustment_report.view',
                                in_array('stock_adjustment_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.stock_adjustment_report.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'item_report.view', in_array('item_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.item_report.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'product_purchase_report.view',
                                in_array('product_purchase_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.product_purchase_report.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'product_sell_report.view', in_array('product_sell_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.product_sell_report.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'product_transaction_report.view',
                                in_array('product_transaction_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.product_transaction_report.view' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
