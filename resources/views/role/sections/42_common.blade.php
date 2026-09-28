            
            <div class="row">
                <div class="col-md-3">
                    <h4><label>@lang( 'role.dashboard' )</label></h4>
                </div>
                <div class="col-md-9">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'dashboard.data', in_array('dashboard.data', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.dashboard.data' ) }}
                            </label>
                        </div>
                    </div>
                    
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'DashboardSummaryCards', in_array('DashboardSummaryCards', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} 
                                Summary Cards Report
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'DashboardPaymentMethods', in_array('DashboardPaymentMethods', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} 
                                Payment Methods Report
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'DashboardProductCategories', in_array('DashboardProductCategories', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} 
                                Product Categories Report
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'DashboardGlance', in_array('DashboardGlance', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} 
                                Dashboard Glance Chart
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'DashboardCurrentPastGraph', in_array('DashboardCurrentPastGraph', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} 
                                Current & Previous Selection Report
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'DashboardCurrentPastPayments', in_array('DashboardCurrentPastPayments', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} 
                                Current & Previous Payments Report
                            </label>
                        </div>
                    </div>
                    
                </div>
            </div>
            <hr class="blue-hr">
            
