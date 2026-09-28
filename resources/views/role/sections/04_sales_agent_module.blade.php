                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang('lang_v1.sales_agents'):</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'sales_agent.view', in_array('sales_agent.view', $role_permissions), [
                                        'class' => 'input-icheck',
                                    ]) !!} {{ __('lang_v1.view_sales_agent') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'sales_agent.add', in_array('sales_agent.add', $role_permissions), [
                                        'class' => 'input-icheck',
                                    ]) !!} {{ __('lang_v1.add_sales_agent') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'sales_agent.edit', in_array('sales_agent.edit', $role_permissions), [
                                        'class' => 'input-icheck',
                                    ]) !!} {{ __('lang_v1.edit_sales_agent') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'sales_agent.delete', in_array('sales_agent.delete', $role_permissions), [
                                        'class' => 'input-icheck',
                                    ]) !!} {{ __('messages.delete') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'sales_agent.add_commission', in_array('sales_agent.add_commission', $role_permissions), [
                                        'class' => 'input-icheck',
                                    ]) !!} {{ __('lang_v1.add_commission') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
