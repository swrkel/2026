            <div class="row">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.contact_report' )</label></h4>
                </div>
                <div class="col-md-2">

                </div>
                <div class="col-md-9">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'contact_report.view', in_array('contact_report.view',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.contact_report.view' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
