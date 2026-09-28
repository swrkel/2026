            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>Spreadsheets</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'access.spreadsheet', in_array('access.spreadsheet', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Access Spreadsheets
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'create.spreadsheet', in_array('create.spreadsheet', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Create Spreadsheet
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'edit.spreadsheet', in_array('edit.spreadsheet', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Edit Spreadsheet
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'delete.spreadsheet', in_array('delete.spreadsheet', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Delete Spreadsheet
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'toggle.spreadsheet', in_array('toggle.spreadsheet', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Enable / Disable Spreadsheet
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'download.spreadsheet', in_array('download.spreadsheet', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Download Spreadsheet
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'create.folder', in_array('create.folder', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Create Folder
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'edit.folder', in_array('edit.folder', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} Edit Folder
                            </label>
                        </div>
                    </div>
                    
                </div>
            </div>
            <hr class="blue-hr">
