                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang('lang_v1.smsmodule'):</label></h4>
                    </div>
                    <div class="col-md-2">
                        <div class="checkbox">
                            <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'sms_quick_send', in_array('sms_quick_send', $role_permissions), [
                                        'class' => 'input-icheck',
                                    ]) !!} {{ __('lang_v1.sms_quick_send') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'sms_from_file',
                                        in_array('sms_from_file', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('lang_v1.sms_from_file') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'sms_campaign',
                                        in_array('sms_campaign', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('lang_v1.sms_campaign') }}
                                </label>
                            </div>
                        </div>
                        
                        
                    </div>
                </div>
                <hr class="blue-hr">
