                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Chequer Module</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'chequer.access', in_array('chequer.access', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Chequer Module
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
