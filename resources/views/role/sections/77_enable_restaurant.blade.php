                <div class="row">
                    <div class="col-md-3">
                        <h4><label>@lang( 'lang_v1.restaurant' )</label></h4>
                    </div>
                    <div class="col-md-9">
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'restaurant.access', in_array('restaurant.access',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __('lang_v1.access_restaurant') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
