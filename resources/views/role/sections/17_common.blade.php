            
            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'superadmin::lang.airline_module' ):</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'airline.view_setting', in_array('airline.view_setting', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.airline.view_setting' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'airline.access', in_array('airline.access', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.airline.access' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'airline_edit_invoice', in_array('airline_edit_invoice', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.airline_edit_invoice' ) }}
                                </label>
                            </div>
                        </div>
                    
                </div>
            </div>
            <hr class="blue-hr">
            
            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.user' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'user.view', in_array('user.view', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.user.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'user.create', in_array('user.create', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.user.create' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'user.update', in_array('user.update', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.user.update' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'user.delete', in_array('user.delete', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.user.delete' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
            
            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>Daily Report Review</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'DailyReviewAll', in_array('DailyReviewAll', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Review All
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'DailyReviewOne', in_array('DailyReviewOne', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Review
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'bypass.review', in_array('bypass.review', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Bypass Review
                            </label>
                        </div>
                    </div>
                    
                </div>
            </div>
            <hr class="blue-hr">
            
