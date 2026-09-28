                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'lang_v1.finance_reports' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'finance_reports.view', in_array('finance_reports.view', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Finance Reports
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
