            <div class="row">
                <div class="col-md-3">
                    <h4><label>PayRoll</label></h4>
                </div>
                <div class="col-md-9">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'payday', in_array('payday', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} PayRoll
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
