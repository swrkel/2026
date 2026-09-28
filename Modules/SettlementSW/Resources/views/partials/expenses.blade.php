          <div class="card-custom">

          <div class="card-header">Expense</div>

            <div class="card-body">

              <div class="row">

                  <div class="col-md-12">

                  <div class="col-md-2">

                      <div class="form-group">

                          {!! Form::label('sw_expense_number', __( 'settlementsw::lang.expense_number' )) !!}

                          {!! Form::text('sw_expense_number', $expense_payment_settlement_no, [

                              'class' => 'form-control check_pumper sw_expense_number expense_fields2',

                              'required',

                              'readonly',

                              'placeholder' => __( 'settlementsw::lang.expense_number' )

                          ]) !!}

                      </div>

                  </div>

                      <div class="col-sm-2">

                          <div class="form-group">

                              {!! Form::label('sw_expense_category', __('settlementsw::lang.category').':') !!}

                              {!! Form::select('sw_expense_category', $expense_categories, null, ['class' => 'form-control check_pumper select2 expense_fields2', 'style' => 'width: 100%;',

                              'placeholder' => __('settlementsw::lang.please_select')]); !!}

                          </div>

                      </div>

                      <div class="col-md-2">

                          <div class="form-group">

                              {!! Form::label('sw_reference_no', __( 'settlementsw::lang.reference_no' )) !!}

                              {!! Form::text('sw_reference_no', null, ['class' => 'form-control check_pumper sw_reference_no expense_fields2', 'required',

                              'placeholder' => __(

                              'settlementsw::lang.reference_no' ) ]); !!}

                          </div>

                      </div>

                      <div class="col-md-2">

                          <div class="form-group">

                              {!! Form::label('sw_expense_amount', __( 'settlementsw::lang.amount' )) !!}

                              {!! Form::text('sw_expense_amount', null, ['class' => 'form-control check_pumper sw_expense_amount expense_fields2', 'required',

                              'placeholder' => __(

                              'settlementsw::lang.amount' ) ]); !!}

                          </div>

                      </div>

                      <div class="col-md-2">

                          <div class="form-group">

                              {!! Form::label('sw_expense_reason', __( 'settlementsw::lang.reason' )) !!}

                              {!! Form::text('sw_expense_reason', null, ['class' => 'form-control check_pumper sw_expense_reason expense_fields2', 'required',

                              'placeholder' => __(

                              'settlementsw::lang.reason' ) ]); !!}

                          </div>

                      </div>

                      <div class="col-sm-2">

                          <div class="form-group">

                              {!! Form::label('sw_expense_account', __('settlementsw::lang.expense_account').':') !!}

                              {!! Form::select('sw_expense_account', $expense_accounts, null, ['class' => 'form-control check_pumper select2 expense_fields2', 'style' => 'width: 100%;',

                              'placeholder' => __('settlementsw::lang.please_select')]); !!}

                          </div>

                      </div>

                      <div class="form-group-custom " style="display:flex;align-items:flex-end;justify-content:center;">

                        <button type="submit" class="btn-add sw_expense_add"><i class="fa fa-plus"></i></button>

                      </div>

                      

                  </div>

              </div>

            </div>

          <br>

          <br>

          <div class="row">

              <div class="col-md-12">

              <table class="table table-bordered table-striped" id="expense_table_new">

                      <thead>

                          <tr>

                              <th>Expense Number</th>

                              <th>Expense Category</th>

                              <th>Reference No</th>

                              <th>Expense Account</th>

                              <th>Reason</th>

                              <th>Amount</th>

                              <th>Action</th>

                          </tr>

                      </thead>

                      <tbody id="expense-tbody">



                      </tbody>



                      <tfoot>

                          <tr>

                              <td colspan="7" style="text-align: right; font-weight: bold;">Total :</td>

                              <td colspan="3" style="text-align: left; font-weight: bold;" class="sw_expense_total">0.00</td>

                          </tr>

                      </tfoot>

                  </table>

              </div>

          </div>

          </div>