<div style="gap:1rem; flex-wrap:wrap;">

  <!-- Other Income -->
  @if(isset($is_other_income) && $is_other_income != 0)
  <div style="flex:1; min-width:300px;">
    <div class="card-custom">
      <div class="card-header">Other Income</div>
      <div class="card-body">

        <form id="other-income-form" class="form-row-custom align-items-end" style="margin-bottom: 10px;">
          <div class="form-group-custom" style="flex: 2;">
            {!! Form::label('other_income_product_id', __('SettlementSW::lang.service')) !!}
            {!! Form::select('other_income_product_id', $services, !empty($temp_data->other_income_product_id) ? $temp_data->other_income_product_id : null, [
                'class' => 'form-control other_income_fields check_pumper other_income_product',
                'required',
                'placeholder' => __('SettlementSW::lang.please_select')
            ]) !!}
          </div>

          <div class="form-group-custom" style="flex: 2;">
            {!! Form::label('other_income_reason', __('SettlementSW::lang.details')) !!}
            {!! Form::text('other_income_reason', !empty($temp_data->other_income_reason) ? $temp_data->other_income_reason : null, [
                'class' => 'form-control other_income_fields check_pumper other_income_reason',
                'required',
                'placeholder' => __('SettlementSW::lang.details')
            ]) !!}
          </div>

          <div class="form-group-custom" style="flex: 1;">
            {!! Form::label('other_income_qty', __('SettlementSW::lang.amount')) !!}
            {!! Form::text('other_income_qty', !empty($temp_data->other_income_qty) ? $temp_data->other_income_qty : null, [
                'class' => 'form-control other_income_fields check_pumper other_income_qty input_number',
                'required', 'readonly', 'id' => 'other_income_price',
                'placeholder' => __('SettlementSW::lang.amount')
            ]) !!}
          </div>

          <div class="form-group-custom" style="display:flex; align-items:flex-end; justify-content:center;">
            @can('edit_other_income_prices')
            <button type="button" class="btn btn-warning edit_price_other_income" data-toggle="modal" data-target="#edit_price_other_income" style="margin-right: 10px;">
              @lang('settlementsw::lang.edit_price')
            </button>
            @endcan
            <button type="button" class="btn-add" id="add-other-income"><i class="fa fa-plus"></i></button>
          </div>

          <input type="hidden" value="{{ $other_income_final_total ?? 0 }}" name="other_income_total" id="other_income_total">
        </form>

        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Service</th>
              <th>Details</th>
              <th>Amount</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="other-income-tbody"></tbody>
          <tfoot>
            <tr>
              <td colspan="7" style="text-align: right; font-weight: bold;">Total :</td>
              <td colspan="3" style="text-align: left; font-weight: bold;" id="other-income-total">0.00</td>
            </tr>
          </tfoot>
        </table>

      </div>
    </div>
  </div>

  <!-- Edit Price Modal -->
  <div class="modal" tabindex="-1" role="dialog" data-backdrop="false" id="edit_price_other_income" style="background: rgba(0, 0, 0, 0.5);">
    <div class="modal-dialog" role="document" style="max-width: 550px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">@lang('settlementsw::lang.edit_price')</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <label for="other_income_edit_price">@lang('settlementsw::lang.price'):</label>
          <input type="text" value="0" name="other_income_edit_price" id="other_income_edit_price" placeholder="Price" class="form-control">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id="save_edit_price_other_income_btn">Save</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Customer Payments -->
  @if(isset($is_customer_payments) && $is_customer_payments != 0)
  <div style="flex:1; min-width:300px;">
    <div class="card-custom">
      <div class="card-header">Customer Payments</div>
      <div class="card-body">

        <form id="cust-payments-form" class="form-row-custom align-items-end">
          <div class="d-flex" style="width: 100%;">

            <div class="form-group" style="flex: 0 0 18%; margin: 5px;">
              {!! Form::label('settlement_customer_payment_no', __('SettlementSW::lang.settlement_customer_payment_no') . ':') !!}
              {!! Form::text('settlement_customer_payment_no', $customer_payment_settlement_no ?? '', ['class' => 'form-control', 'readonly']) !!}
            </div>

            <div class="form-group-custom" style="flex: 0 0 18%; margin: 5px;">
              {!! Form::label('customer_payment_customer_id', __('SettlementSW::lang.customer').':') !!}
              {!! Form::select('customer_payment_customer_id', $customers, !empty($temp_data->customer_payment_customer_id) ? $temp_data->customer_payment_customer_id : null, [
                'class' => 'form-control select2', 'style' => 'width: 100%;'
              ]) !!}
            </div>

            <div class="form-group-custom" style="flex: 0 0 17%; margin: 5px;">
              {!! Form::label('customer_payment_payment_method', __('SettlementSW::lang.payment_method').':') !!}
              {!! Form::select('customer_payment_payment_method', ['cash' => 'Cash', 'card' => 'Card', 'cheque' => 'Cheque', 'bank' => 'Bank'], !empty($temp_data->customer_payment_payment_method) ? $temp_data->customer_payment_payment_method : null, [
                'class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('SettlementSW::lang.please_select')
              ]) !!}
            </div>

            <div class="form-group-custom hide card_div" style="flex: 0 0 12.5%; margin: 5px;">
              {!! Form::label('customer_payment_account_module', __('lang_v1.payment_account').':') !!}
              {!! Form::select('customer_payment_account_module', $account_modules, !empty($temp_data->customer_payment_payment_method) ? $temp_data->customer_payment_payment_method : null, [
                'class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('SettlementSW::lang.please_select')
              ]) !!}
            </div>

            <div class="form-group-custom hide bank_div" style="flex: 0 0 12.5%; margin: 5px;">
              {!! Form::label('customer_payment_account_module', __('lang_v1.payment_account').':') !!}
              {!! Form::select('customer_payment_account_module', $account_modules_bank, !empty($temp_data->customer_payment_payment_method) ? $temp_data->customer_payment_payment_method : null, [
                'class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('SettlementSW::lang.please_select')
              ]) !!}
            </div>

            <div class="form-group-custom hide cheque_divs" style="flex: 0 0 11.5%; margin: 5px;">
              {!! Form::label('customer_payment_bank_name', __('SettlementSW::lang.bank_name')) !!}
              {!! Form::text('customer_payment_bank_name', !empty($temp_data->customer_payment_bank_name) ? $temp_data->customer_payment_bank_name : null, [
                'class' => 'form-control customer_payment_fields bank_name',
                'placeholder' => __('SettlementSW::lang.bank_name')
              ]) !!}
            </div>

            <div class="form-group-custom hide cheque_divs bank_div" style="flex: 0 0 9%; margin: 5px;">
              {!! Form::label('customer_payment_cheque_date', __('SettlementSW::lang.cheque_date')) !!}
              {!! Form::text('customer_payment_cheque_date', !empty($temp_data->customer_payment_cheque_date) ? $temp_data->customer_payment_cheque_date : null, [
                'class' => 'form-control cheque_date', 'placeholder' => __('SettlementSW::lang.cheque_date')
              ]) !!}
            </div>

            <div class="form-group-custom hide cheque_divs bank_div" style="flex: 0 0 9%; margin: 5px;">
              {!! Form::label('customer_payment_cheque_number', __('SettlementSW::lang.cheque_number')) !!}
              {!! Form::text('customer_payment_cheque_number', !empty($temp_data->customer_payment_cheque_number) ? $temp_data->customer_payment_cheque_number : null, [
                'class' => 'form-control customer_payment_fields cheque_number',
                'placeholder' => __('SettlementSW::lang.cheque_number')
              ]) !!}
            </div>

            <div class="form-group-custom" style="flex: 0 0 10.5%; margin: 5px;">
              {!! Form::label('customer_payment_amount', __('SettlementSW::lang.amount')) !!}
              {!! Form::text('customer_payment_amount', !empty($temp_data->customer_payment_amount) ? $temp_data->customer_payment_amount : null, [
                'class' => 'form-control customer_payment_fields customer_payment',
                'required', 'id' => 'customer_payment_amount',
                'placeholder' => __('SettlementSW::lang.amount')
              ]) !!}
            </div>

            <div class="form-group-custom" style="display:flex; align-items:flex-end; justify-content:center; flex: 0 0 2.5%; margin: 8px;">
              <button type="button" class="btn-add" id="add-payment"><i class="fa fa-plus"></i></button>
            </div>

          </div>

          <input type="hidden" value="{{ $customer_payment_total ?? 0 }}" name="customer_payment_total" id="customer_payment_total">
        </form>

        
        
<!-- Customer Payments Table -->
<div id="business-payments-cust-wrapper" class="d-none">
    <table id="cust-payments-list-table" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Payment Method</th>
                <th>Amount</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="cust-payments-list">
            <!-- Rows will be dynamically appended here -->
            <!-- Each row should have class 'custum-name' and data-id="${row.id}" -->
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">Total</th>
                <th id="cust-payments-total">0.00</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</div>


      </div>
    </div>
  </div>
  @endif

</div>
