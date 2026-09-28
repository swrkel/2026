            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.verification_reports' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'monthly_report.view',in_array('monthly_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.monthly_report.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'comparison_report.view',in_array('comparison_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.comparison_report.view' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
