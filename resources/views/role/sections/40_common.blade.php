            
            <div class="row">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.trending_products' )</label></h4>
                </div>
                <div class="col-md-2">

                </div>
                <div class="col-md-9">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'trending_products.view', in_array('trending_products.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.trending_products.view' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
            
            <div class="row">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.user_activity' )</label></h4>
                </div>
                <div class="col-md-2">

                </div>
                <div class="col-md-9">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'user_activity.view', in_array('user_activity.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.user_activity.view' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">

            <div class="row">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.payment_received' )</label></h4>
                </div>
                <div class="col-md-2">

                </div>
                <div class="col-md-9">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'payment_received.view', in_array('payment_received.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.payment_received.view' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
            
            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.settings' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'business_settings.access', in_array('business_settings.access',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.business_settings.access' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'barcode_settings.access', in_array('barcode_settings.access',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.barcode_settings.access' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'invoice_settings.access', in_array('invoice_settings.access',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.invoice_settings.access' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'expense.access', in_array('expense.access', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.expense.access' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'backup', in_array('backup', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.backup' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'backup.restore', in_array('backup.restore', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.restore' ) }} 
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'backup.upload', in_array('backup.upload', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.upload' ) }} 
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
            
