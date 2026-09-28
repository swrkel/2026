            <div class="row">
                <div class="col-md-3">
                    <h4><label>@lang( 'lang_v1.access_selling_price_groups' )</label></h4>
                </div>
                <div class="col-md-9">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'access_default_selling_price',
                                in_array('access_default_selling_price',
                                $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __('lang_v1.default_selling_price') }}
                            </label>
                        </div>
                    </div>
                    @if(count($selling_price_groups) > 0)
                        @foreach($selling_price_groups as $selling_price_group)
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('spg_permissions[]', 'selling_price_group.' . $selling_price_group->id,
                                        in_array('selling_price_group.' . $selling_price_group->id, $role_permissions),
                                        [ 'class' => 'input-icheck']); !!} {{ $selling_price_group->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            <hr class="blue-hr">
