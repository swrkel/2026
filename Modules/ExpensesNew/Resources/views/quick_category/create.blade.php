{{--
    ExpensesNew - inline "quick add expense category" modal.

    MA-002: BARE MODAL MARKUP, NO @extends. That is the whole point of this
    file. Core's expense_category/create.blade.php opens with
    <div class="modal-dialog"> and that is why 26 screens across Fleet,
    Property, SettlementSW, AutoRepairServices, EVCharging and the Petro
    modules can drop it straight into a .btn-modal.

    This module's own categories/form.blade.php starts with
        @extends('expensesnew::layouts.app', ...)
    so it renders a full page - sidebar, header and all - which inside a modal
    box looks broken. Mirroring core's structure here is what lets those
    screens switch across without any change to their own markup or JS.
--}}
<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open([
        'url'    => action('\Modules\ExpensesNew\Http\Controllers\QuickCategoryController@store'),
        'method' => 'post',
        'id'     => 'expensesnew_quick_category_form',
    ]) !!}

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">@lang('expense.add_expense_category')</h4>
      </div>

      <div class="modal-body">
        <div class="row">

          <div class="col-md-12">
            <div class="form-group">
              {!! Form::label('name', __('expense.expense_category') . ':*') !!}
              {!! Form::text('name', null, [
                  'class'       => 'form-control',
                  'required',
                  'placeholder' => __('expense.expense_category'),
              ]) !!}
            </div>
          </div>

          <div class="col-md-12">
            <div class="form-group">
              {!! Form::label('code', __('lang_v1.code') . ':') !!}
              {!! Form::text('code', null, [
                  'class'       => 'form-control',
                  'placeholder' => __('lang_v1.code'),
              ]) !!}
              <p class="help-block">
                {{ __('messages.leave_blank_to_autogenerate') ?? 'Leave blank to generate automatically.' }}
              </p>
            </div>
          </div>

          <div class="col-md-12">
            <div class="form-group">
              {!! Form::label('expense_account', __('account.account') . ':') !!}
              {!! Form::select('expense_account', $expense_accounts, null, [
                  'class'       => 'form-control select2',
                  'placeholder' => __('messages.please_select'),
                  'style'       => 'width:100%;',
              ]) !!}
            </div>
          </div>

          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('is_sub_category', 1, false, ['id' => 'expensesnew_is_sub_category']) !!}
                @lang('lang_v1.add_as_sub_category')
              </label>
            </div>
          </div>

          <div class="col-md-12 expensesnew_parent_wrapper" style="display:none;">
            <div class="form-group">
              {!! Form::label('parent_id', __('lang_v1.parent_category') . ':') !!}
              {!! Form::select('parent_id', $parents, null, [
                  'class'       => 'form-control select2',
                  'placeholder' => __('messages.please_select'),
                  'style'       => 'width:100%;',
              ]) !!}
            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
      </div>

    {!! Form::close() !!}

  </div>
</div>

<script type="text/javascript">
  $(document).ready(function () {
      if ($.fn.select2) {
          $('#expensesnew_quick_category_form .select2').select2({
              // Bind to the modal so the dropdown is not clipped by it. The
              // LA-1131 deposit calendar failed for exactly the opposite
              // reason - a widget appended to the body and detached from the
              // modal that owned the clicks.
              dropdownParent: $('#expensesnew_quick_category_form').closest('.modal'),
              width: '100%'
          });
      }

      $('#expensesnew_is_sub_category').on('change', function () {
          $('.expensesnew_parent_wrapper').toggle($(this).is(':checked'));
      });
  });
</script>
