                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'sale.sale' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'sell.view', in_array('sell.view', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.view' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'sell.create', in_array('sell.create', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.create' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'sell.update', in_array('sell.update', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.update' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'sell.delete', in_array('sell.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.delete' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'direct_sell.access', in_array('direct_sell.access', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.direct_sell.access' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'list_drafts', in_array('list_drafts', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.list_drafts' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'list_quotations', in_array('list_quotations', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.list_quotations' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'view_own_sell_only', in_array('view_own_sell_only', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.view_own_sell_only' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'sell.payments', in_array('sell.payments', $role_permissions), ['class'
                                    => 'input-icheck']); !!}
                                    {{ __('lang_v1.sell.payments') }}
                                </label>
                                @show_tooltip(__('lang_v1.sell_payments'))
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_product_price_from_sale_screen',
                                    in_array('edit_product_price_from_sale_screen', $role_permissions), ['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.edit_product_price_from_sale_screen') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_product_price_from_pos_screen',
                                    in_array('edit_product_price_from_pos_screen', $role_permissions), ['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.edit_product_price_from_pos_screen') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_product_price_below_purchase_price',
                                    in_array('edit_product_price_below_purchase_price', $role_permissions), ['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.edit_product_price_below_purchase_price') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_product_discount_from_sale_screen',
                                    in_array('edit_product_discount_from_sale_screen', $role_permissions), ['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.edit_product_discount_from_sale_screen') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_product_discount_from_pos_screen',
                                    in_array('edit_product_discount_from_pos_screen', $role_permissions), ['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.edit_product_discount_from_pos_screen') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'discount.access', in_array('discount.access', $role_permissions),
                                    ['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.discount.access') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'access_shipping', in_array('access_shipping', $role_permissions),
                                    ['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.access_shipping') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'pos_page_return', in_array('pos_page_return', $role_permissions),
                                    ['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.pos_page_return') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'status_order', in_array('status_order', $role_permissions),
                                    ['class' => 'input-icheck']); !!}
                                    {{ __('lang_v1.status_order') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
