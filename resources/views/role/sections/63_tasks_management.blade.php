                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.tasks_management' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'tasks_management.access', in_array('tasks_management.access',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.tasks_management.access' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'tasks_management.tasks', in_array('tasks_management.tasks',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.tasks_management.tasks' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'tasks_management.reminder', in_array('tasks_management.reminder',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.tasks_management.reminder' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
