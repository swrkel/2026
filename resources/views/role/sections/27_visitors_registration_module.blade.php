            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang('visitors.visitor_registration' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'visitor.registration.create',in_array('visitor.registration.create',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'visitors.visitor_registration_create' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'visitor.registration.view',in_array('visitor.registration.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'visitors.visitor_registration_view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'visitor.registration.edit',in_array('visitor.registration.edit',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'visitors.visitor_registration_edit' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'visitor.registration.delete',in_array('visitor.registration.delete',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'visitors.visitor_registration_delete' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'visitor.settings.view', in_array('visitor.settings.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'visitors.visitor_settings_view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'visitor.settings.edit', in_array('visitor.settings.edit',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'visitors.visitor_settings_edit' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
