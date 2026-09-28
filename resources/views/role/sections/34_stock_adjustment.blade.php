            <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.stock_adjustments' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'stockAdjustment.add',
                                    in_array('stockAdjustment.add',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.stockAdjustment.add') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'stockAdjustment.edit', in_array('stockAdjustment.edit',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.stockAdjustment.edit') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'stockAdjustment.delete', in_array('stockAdjustment.delete',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.stockAdjustment.delete') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'stockAdjustment.list', in_array('stockAdjustment.list',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.stockAdjustment.list') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            <hr class="blue-hr">
