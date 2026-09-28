            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.auto_services_and_repair_module' )</label></h4>
                </div>
                <div class="col-md-2">

                </div>
                <div class="col-md-9">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {{-- MA-002 review #6A: the two pages posted four different
                                     names for these two permissions, so anything granted on
                                     one page was not rendered on the other and was stripped
                                     on save. create also had a name/value mismatch, so its
                                     box never showed as ticked. Settled on the two names
                                     that have matching keys in resources/lang/en/role.php -
                                     the other two rendered raw label keys on screen. --}}
                                {!! Form::checkbox('permissions[]', 'auto_services_and_repair_module.access', in_array('auto_services_and_repair_module.access',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.auto_services_and_repair_module.access' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'auto_services_and_repair_module.job_sheet.edit', in_array('auto_services_and_repair_module.job_sheet.edit',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.auto_services_and_repair_module.job_sheet.edit' ) }}
                         </label>
                        </div>
                    </div>

                </div>
            </div>
            <hr class="blue-hr">
