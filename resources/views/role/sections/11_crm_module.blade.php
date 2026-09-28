            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'superadmin::lang.crm_module' ):</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'crm.access', in_array('crm.access', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.access' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.access_all_leads', in_array('crm.access_all_leads', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.access_all_leads' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.access_own_leads', in_array('crm.access_own_leads', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.access_own_leads' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.access_all_schedule', in_array('crm.access_all_schedule', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.access_all_schedule' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.access_own_schedule', in_array('crm.access_own_schedule', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.access_own_schedule' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.access_all_campaigns', in_array('crm.access_all_campaigns', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.access_all_campaigns' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.access_own_campaigns', in_array('crm.access_own_campaigns', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.access_own_campaigns' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.access_contact_login', in_array('crm.access_contact_login', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.access_contact_login' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.view_all_call_log', in_array('crm.view_all_call_log', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.view_all_call_log' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.view_own_call_log', in_array('crm.view_own_call_log', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.view_own_call_log' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.view_reports', in_array('crm.view_reports', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.view_reports' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.access_resources', in_array('crm.access_resources', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.access_resources' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.access_life_stage', in_array('crm.access_life_stage', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.access_life_stage' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.add_proposal_template', in_array('crm.add_proposal_template', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.add_proposal_template' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.access_proposal', in_array('crm.access_proposal', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.access_proposal' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
