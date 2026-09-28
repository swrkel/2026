                @if(auth()->user()->can('sms.view'))
                    <div class="row">
                        <div class="col-md-3">
                            <h4><label>@lang( 'lang_v1.sms' )</label></h4>
                        </div>
                        <div class="col-md-9">
                            @can('sms.view')
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            {!! Form::checkbox('permissions[]', 'sms.view', in_array('sms.view',
                                            $role_permissions),
                                            [ 'class' => 'input-icheck']); !!} {{ __('lang_v1.sms_view') }}
                                        </label>
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </div>
                    <hr class="blue-hr">
                @endif
