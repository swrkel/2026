                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang('bakery::lang.bakery_module'):</label></h4>
                    </div>
                    <div class="col-md-2">
                        <div class="checkbox">
                            <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'bakery.loading_edit', in_array('bakery.loading_edit', $role_permissions), [
                                        'class' => 'input-icheck',
                                    ]) !!} {{ __('bakery::lang.bakery.loading_edit') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
