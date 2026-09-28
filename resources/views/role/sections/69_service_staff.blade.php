                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'restaurant.bookings' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'crud_all_bookings', in_array('crud_all_bookings',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'restaurant.add_edit_view_all_booking' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'crud_own_bookings', in_array('crud_own_bookings',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'restaurant.add_edit_view_own_booking' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
