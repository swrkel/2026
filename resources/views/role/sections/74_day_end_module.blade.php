                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'lang_v1.day_end' )</label></h4>
                    </div>
                    <div class="col-md-2">
                        <div class="checkbox">

                            <input type="checkbox" class="check_all input-icheck"> {{ __( 'role.select_all' ) }}

                        </div>
                    </div>
                    <div class="col-md-9">
                        @can('day_end.view')
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'day_end.view', in_array('day_end.view',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('lang_v1.day_end_view') }}
                                    </label>
                                </div>
                            </div>
                        @endcan
                        @can('day_end.bypass')
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('permissions[]', 'day_end.bypass', in_array('day_end.bypass',
                                        $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ __('lang_v1.day_end_bypass') }}
                                    </label>
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>
                <hr class="blue-hr">
