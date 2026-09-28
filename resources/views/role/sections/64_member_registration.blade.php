                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.member_registration')</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'member_registration.access', in_array('member_registration.access',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.member_registration.access' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'add_remarks', in_array('add_remarks',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.add_remarks' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'update_status_of_issue', in_array('update_status_of_issue',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.update_status_of_issue' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
