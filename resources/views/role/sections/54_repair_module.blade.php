            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.repair' )</label></h4>
                </div>
                <div class="col-md-2">

                </div>
                <div class="col-md-9">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'repair.access', in_array('repair.access',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.repair.access' ) }}
                            </label>
                        </div>
                    </div>

                </div>
            </div>
            <hr class="blue-hr">
