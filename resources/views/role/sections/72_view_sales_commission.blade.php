                <div class="row">
                    <div class="col-md-3">
                        <h4><label>@lang( 'lang_v1.sales_commission_agents_create' )</label></h4>
                    </div>
                    <div class="col-md-9">
                        @can('sales-commission-agents.create')
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'sales-commission-agents.create',
                                        in_array('sales-commission-agents.create',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('lang_v1.sales_commission_agents_create') }}
                                    </label>
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>
                <hr class="blue-hr">
