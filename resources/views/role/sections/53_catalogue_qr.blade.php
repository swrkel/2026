            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.catalogue_qr' )</label></h4>
                </div>
                <div class="col-md-2">

                </div>
                <div class="col-md-9">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'catalogue.access', in_array('catalogue.access',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.catalogue.access' ) }}
                            </label>
                        </div>
                    </div>

                </div>
            </div>
            <hr class="blue-hr">
