                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'business.product' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'product.view', in_array('product.view', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.product.view' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'product.create', in_array('product.create', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.product.create' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'product.update', in_array('product.update', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.product.update' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'product.delete', in_array('product.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.product.delete' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'product.opening_stock', in_array('product.opening_stock',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.add_opening_stock' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'view_purchase_price', in_array('view_purchase_price',
                                    $role_permissions),['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.view_purchase_price') }}
                                </label>
                                @show_tooltip(__('lang_v1.view_purchase_price_tooltip'))
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'product.price_section', in_array('product.price_section',
                                    $role_permissions),['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.product.price_section') }}
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
                <hr class="blue-hr">
