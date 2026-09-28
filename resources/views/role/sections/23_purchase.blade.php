                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.purchase' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'purchase.view', in_array('purchase.view', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.view' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'purchase.create', in_array('purchase.create', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.create' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'purchase.update', in_array('purchase.update', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.update' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'purchase.delete', in_array('purchase.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.delete' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {{-- MA-002 review #4: the third argument is the CHECKED
                                         state. This passed the whole $role_permissions array,
                                         which is always truthy, so the box showed as ticked for
                                         every role whether or not it had been granted. Every
                                         other checkbox on this page uses in_array(). --}}
                                    {!! Form::checkbox('permissions[]', 'purchase.zero', in_array('purchase.zero', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.zero' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'purchase.update_status', in_array('purchase.update_status',
                                    $role_permissions),['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.update_status') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'add.payments', in_array('add.payments',
                                    $role_permissions),['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.add.payments') }}
                                </label>
                            </div>
                        </div>
                        
                         <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'purchase.edit.payments', in_array('purchase.edit.payments',
                                    $role_permissions),['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.edit_payments') }}
                                </label>
                            </div>
                        </div>
                        
                         <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'purchase.delete.payments', in_array('purchase.delete.payments',
                                    $role_permissions),['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.delete_payments') }}
                                </label>
                            </div>
                        </div>
                        {{-- IS2347: Product SKU maintenance is not a Purchase permission.
                             Keep it on the legacy Role screen for backward compatibility,
                             but do not show it inside User Management New > Purchase. --}}
                        @if (empty($umn_managed_role_form))
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'product.edit_sku', in_array('product.edit_sku',
                                        $role_permissions),['class' => 'input-icheck']); !!}
                                        {{ __('lang_v1.edit_product_sku') }}
                                    </label>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <hr class="blue-hr">
