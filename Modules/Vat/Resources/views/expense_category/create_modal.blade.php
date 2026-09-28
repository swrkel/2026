<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseCategoryController@store'), 'method' => 'post', 'id' => 'expense_category_add_form']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title"><i class="fa fa-plus-circle text-primary"></i> @lang('expense.add_expense_category')</h4>
    </div>
    <div class="modal-body">
        @include('vat::expense_category._form_fields')
    </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> @lang('messages.save')</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
<script>$('.expense_category_modal .select2').select2({dropdownParent: $('.expense_category_modal'), width:'100%'});</script>
