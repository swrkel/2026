                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.sms')</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'sms.access', in_array('sms.access',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.sms.access' ) }}
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'sms.list', in_array('sms.list',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.sms.list' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
