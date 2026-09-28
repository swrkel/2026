                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.customer_settings' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'customer_settings.access', in_array('customer_settings.access',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer_settings.access' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'approve_sell_over_limit', in_array('approve_sell_over_limit',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.approve_sell_over_limit' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
