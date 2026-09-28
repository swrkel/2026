                <div class="row">
                    <div class="col-md-3">
                        <h4><label>@lang( 'lang_v1.min_sell_price' )</label></h4>
                    </div>
                    <div class="col-md-9">
                        @can('product.set_min_sell_price')
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'product.set_min_sell_price', in_array('product.set_min_sell_price',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('lang_v1.min_sell_price') }}
                                    </label>
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>
                <hr class="blue-hr">
