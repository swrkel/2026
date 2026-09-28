            <div class="row check_group">
                <div class="col-md-2">
                    <h4><label>Customized Reports:</label></h4>
                </div>
                <div class="col-md-2">
                    <div class="checkbox">
                        <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="col-md-4">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'cr.add_lioc_statement', in_array('cr.add_lioc_statement', $role_permissions), ['class' => 'input-icheck']) !!}
                                Add LIOC Statement
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'cr.list_lioc_statements', in_array('cr.list_lioc_statements', $role_permissions), ['class' => 'input-icheck']) !!}
                                List LIOC Statements
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'cr.prefix_numbers', in_array('cr.prefix_numbers', $role_permissions), ['class' => 'input-icheck']) !!}
                                Prefix &amp; Numbers
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'cr.edit_lioc_statement', in_array('cr.edit_lioc_statement', $role_permissions), ['class' => 'input-icheck']) !!}
                                Edit List LIOC Statements
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
