                <div class="row">
                    <div class="col-md-2">
                        <h4><label>@lang( 'lang_v1.user_type' )</label></h4>
                    </div>
                    <div class="col-md-9 col-md-offset-1">
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('is_service_staff', 1, isset($role) ? $role->is_service_staff : false,
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'restaurant.service_staff' ) }}
                                </label>
                                @show_tooltip(__('restaurant.tooltip_service_staff'))
                            </div>
                        </div>
                    </div>
                </div>
