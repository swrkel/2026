                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.supplier' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'supplier.view', in_array('supplier.view', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.supplier.view' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'supplier.create', in_array('supplier.create', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.supplier.create' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'supplier.update', in_array('supplier.update', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.supplier.update' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'supplier.delete', in_array('supplier.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.supplier.delete' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'supplier_pay_due', in_array('supplier_pay_due', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.supplier_pay_due' ) }}
                                </label>
                            </div>
                        </div>
                        
                        {{-- MA-002 review #3: the Customer section was nested INSIDE the
                             Supplier block here, which had two consequences:

                             1. A business with customers but no suppliers lost the whole
                                Customer permission section - it was gated on
                                contact_supplier and could not be granted at all.
                             2. The row became col-md-9 + col-md-2 + col-md-9 = twenty
                                columns in a twelve-column grid, so the layout broke from
                                this point down the page.

                             Split into two sections, matching role/create.blade.php. --}}
                    </div>
                </div>
                <hr class="blue-hr">
