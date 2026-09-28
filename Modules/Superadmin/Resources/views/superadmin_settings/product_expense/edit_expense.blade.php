<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@updateExpenseCategory', [$category->id]), 'method' => 'put', 'id' =>
    'default_expense_edit_form' ]) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'expense.edit_expense_category' )</h4>
    </div>

    <div class="modal-body">
     
      <div class="form-group">
        {!! Form::label('name', __( 'expense.category_name' ) . ':*') !!}
        {!! Form::text('name', $category->name, ['class' => 'form-control', 'required', 'placeholder' => __(
        'expense.category_name' )]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('code', __( 'expense.category_code' ) . ':') !!}
        {!! Form::text('code', $category->code, ['class' => 'form-control', 'placeholder' => __( 'expense.category_code' )]); !!}
      </div>
      @if($account_access)
      <div class="form-group">
        {!! Form::label('expense_account', __('sale.expense_account') . ':*') !!}
        {!! Form::select('expense_account', $expense_accounts, $category->expense_account, ['class' => 'form-control select2', 'placeholder'
        =>
        __('lang_v1.please_select'), 'style' => 'width: 100%', 'required']) !!}
      </div>
      @else
      <div class="form-group">
        {!! Form::label('expense_account', __('sale.expense_account') . ':*') !!}
        {!! Form::select('expense_account', $expense_accounts, $category->expense_account, ['class' => 'form-control select2',
        'style' => 'width: 100%', 'required']) !!}
      </div>
      @endif
      
      <!-- Add payee field -->
      <div class="form-group">
        {!! Form::label('payee', 'Payee' . ':*') !!}
        {!! Form::select('payee_id', $payees, $category->payee_id, ['class' => 'form-control select2',
        'style' => 'width: 100%']) !!}
      </div>
      
      <div class="form-group">
        <label for="is_sub_category_edit">
          {!! Form::checkbox('is_sub_category', 1, $category->is_sub_category, ['class' => 'input-icheck', 'id' => 'is_sub_category_edit']) !!} @lang('expense.is_sub_category')
        </label>
      </div>
      <div class="form-group {{ $category->is_sub_category ? '' : 'hide' }} parent_category_edit">
        {!! Form::label('parent_id', __('expense.parent_category') . ':*') !!}
        {!! Form::select('parent_id', $expense_categories, $category->parent_id, ['class' => 'form-control select2', 'placeholder'
        =>
        __('lang_v1.please_select'), 'style' => 'width: 100%']) !!}
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
    $('#is_sub_category_edit').change(function(){
      if($(this).prop('checked')){
        $('.parent_category_edit').removeClass('hide');
        $('#parent_id').prop('required', true);
      }else{
        $('.parent_category_edit').addClass('hide');
        $('#parent_id').prop('required', false);
      }
    });
    $('.select2').select2();
</script>
