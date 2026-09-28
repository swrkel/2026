                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.leads')</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'leads.view', in_array('leads.view',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.leads.view' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'leads.create', in_array('leads.create',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.leads.create' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'leads.edit', in_array('leads.edit',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.leads.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'leads.delete', in_array('leads.delete',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.leads.delete' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'leads.import', in_array('leads.import',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.leads.import' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'leads.settings', in_array('leads.settings',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.leads.settings' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                
                @if(!empty($get_permissions['day_count']) && $get_permissions['day_count'])
                <div class="row">
                    <div class="col-md-3">
                        <h4><label>@lang( 'role.day_count' )</label></h4>
                    </div>
                    <div class="col-md-9">
                        @can('day_count')
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'day_count', in_array('day_count',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('role.day_count') }}
                                    </label>
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>
                <hr class="blue-hr">
                @endif
