                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'contact.edit_received_outstanding' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'add_received_outstanding', in_array('add_received_outstanding',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'contact.add_received_outstanding' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
