            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.management_reports' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'daily_report.view', in_array('daily_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.daily_report.view' ) }}
                            </label>
                        </div>
                    </div>

                    {{--
                      | LA-1194. The Management Report module's sidebar checks a
                      | user_permission on every menu item, but those permissions
                      | were never offered here - so no role could hold them and
                      | the menu stayed hidden for everyone except Super Admins,
                      | who bypass permission checks.
                      |
                      | The labels are written out rather than translated: there
                      | are no role.management_report.* language keys, and a
                      | missing key renders as the raw key, which reads badly.
                    --}}
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'management_report.view', in_array('management_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Management Report - Dashboard
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'management_report.generate', in_array('management_report.generate',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Management Report - Generate Daily Report
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'management_report.view_saved', in_array('management_report.view_saved',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Management Report - Saved Reports
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'management_report.view_delivery_history', in_array('management_report.view_delivery_history',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Management Report - Delivery History
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'management_report.settings', in_array('management_report.settings',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Management Report - Settings
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'daily_summary_report.view', in_array('daily_summary_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.daily_summary_report.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'register_report.view', in_array('register_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.register_report.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'profit_loss_report.view', in_array('profit_loss_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.profit_loss_report.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'credit_status.view', in_array('credit_status.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.credit_status.view' ) }}
                            </label>
                        </div>
                    </div>

                </div>
            </div>
            <hr class="blue-hr">
