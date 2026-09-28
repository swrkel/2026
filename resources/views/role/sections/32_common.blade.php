            
            <div class="row">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.report' )</label></h4>
                </div>
                <div class="col-md-2">
                </div>
                <div class="col-md-9">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'report.access', in_array('report.access',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.report.access' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
            
