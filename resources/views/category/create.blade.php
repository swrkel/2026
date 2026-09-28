<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('CategoryController@store'), 'method' => 'post', 'id' => 'category_add_form' ]) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'category.add_category' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'category.category_name' ) . ':*') !!}
        {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __(
        'category.category_name' )]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('short_code', __( 'category.code' ) . ':') !!}
        {!! Form::text('short_code', $new_category_short_code, ['class' => 'form-control', 'placeholder' => __( 'category.code' )]); !!}
        <p class="help-block">{!! __('lang_v1.category_code_help') !!}</p>
      </div>
      
      @if(!empty($parent_categories))
      <div class="form-group">
        <div class="checkbox">
          <label>
            {!! Form::checkbox('add_as_sub_cat', 1, false,[ 'class' => 'toggler', 'data-toggle_id' => 'parent_cat_div',
            'data-toggle_class' => 'parent_cat_div'
            ]); !!} @lang( 'category.add_as_sub_category' ) @if(!empty($help_explanations['add_as_sub_category']))
            @show_tooltip($help_explanations['add_as_sub_category']) @endif
          </label>
        </div>
      </div>
      <div class="form-group hide" id="parent_cat_div">
        {!! Form::label('parent_id', __( 'category.select_parent_category' ) . ':') !!}
        {!! Form::select('parent_id', $parent_categories, null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2 parent-category-select', 'style' => 'width: 100%;']); !!}
      </div>
      @endif

      @if($account_access)
      <div class="form-group add_related_account">
        {!! Form::label('add_related_account', __( 'account.add_related_account' ) .":", ['class' =>
        'add_related_account_label']) !!} @if(!empty($help_explanations['add_related_account_label']))
        @show_tooltip($help_explanations['add_related_account_label']) @endif
        {!! Form::select('add_related_account', ['category_level' => 'Category Level', 'sub_category_level' => 'Sub
        Category Level'], null, ['placeholder' =>
        __('messages.please_select'), 'required','style' => 'width: 100%', 'class' => 'form-control category-native-select']) !!}
      </div>


      <div class="form-group cogs_account">
        {!! Form::label('cogs_account_id', __( 'account.cogs_account' ) .":", ['class' => 'cogs_account_label']) !!}
        @if(!empty($help_explanations['cogs_accounts'])) @show_tooltip($help_explanations['cogs_accounts']) @endif
        {!! Form::select('cogs_account_id', $cogs_accounts, null, ['placeholder' =>
        __('messages.please_select'), 'required', 'style' => 'width: 100%', 'class' => 'form-control category-native-select']) !!}
      </div>
      <div class="form-group sales_income_account">
        {!! Form::label('sales_income_account_id', __( 'account.sales_income_account' ) .":", ['class' =>
        'sales_income_account_label']) !!}@if(!empty($help_explanations['sale_income_accounts']))
        @show_tooltip($help_explanations['sale_income_accounts']) @endif
        {!! Form::select('sales_income_account_id', $sale_income_accounts, null, ['placeholder' =>
        __('messages.please_select'), 'required', 'style' => 'width: 100%', 'class' => 'form-control category-native-select']) !!}
      </div>

      <div class="form-group">
        <div class="checkbox">
          <label>
            {!! Form::checkbox('weight_excess_loss_applicable', 1, false,[ 'class' => 'toggler', 'data-toggle_id' =>
            'weight_excess_loss_applicable', 'id' => 'weight_excess_loss_applicable'
            ]); !!} @lang( 'lang_v1.weight_excess_loss_applicable' )
          </label>
        </div>
      </div>


      <div class="form-group weight_loss_expense_account weight_excess_loss_applicable_field hide">
        {!! Form::label('weight_loss_expense_account_id', __( 'lang_v1.weight_loss_expense_account' ) .":", ['class' =>
        'weight_loss_expense_account_label']) !!}
        {!! Form::select('weight_loss_expense_account_id', $expense_accounts, null, ['placeholder' =>
        __('messages.please_select'), 'style' => 'width: 100%', 'class' => 'form-control select2']) !!}
      </div>
      <div class="form-group weight_excess_income_account weight_excess_loss_applicable_field hide">
        {!! Form::label('weight_excess_income_account_id', __( 'lang_v1.weight_excess_income_account' ) .":", ['class'
        =>
        'weight_excess_income_account_label']) !!}
        {!! Form::select('weight_excess_income_account_id', $income_accounts, null, ['placeholder' =>
        __('messages.please_select'), 'style' => 'width: 100%', 'class' => 'form-control select2']) !!}
      </div>
      @endif

      @if($price_change_module_enabled)
      <div class="form-group parent_cat_div">
        {!! Form::label('price_reduction_acc', __( 'category.price_reduction_acc' ) . ':') !!}<br>
        {!! Form::select('price_reduction_acc', $expense_accounts, null, ['placeholder'
          => __( 'messages.please_select' ), 'style' => 'width: 100%','class' => 'form-control select2']); !!}
      </div>
      
      <div class="form-group parent_cat_div">
        {!! Form::label('price_increment_acc', __( 'category.price_increment_acc' ) . ':') !!}<br>
        {!! Form::select('price_increment_acc', $income_accounts, null, ['placeholder'
          => __( 'messages.please_select' ),'style' => 'width: 100%','class' => 'form-control select2']); !!}
      </div>
      @endif
      
      <!--<div class="form-group">
          {!! Form::label('remaining_stock_adjusts', __( 'category.remaining_stock_adjusts' ) . ':*') !!}
          {!! Form::select('remaining_stock_adjusts', ['1' => __('messages.yes'), '0' => __('messages.no')], null, ['placeholder'
          => __( 'messages.please_select' ), 'required', 'class' => 'form-control']); !!}
        </div>-->
        
        <!--changes made by oseahumen-->
        <div class="form-group">
          {!! Form::label('vat_exempted', __( 'category.vat_exempted' ) . ':*') !!}
         {!! Form::select('vat_exempted', ['Yes' => __('messages.yes'), 'No' => __('messages.no')], null, ['placeholder'
          => __( 'messages.please_select' ), 'required', 'class' => 'form-control category-native-select vat_exempted']); !!}
        </div>
        
        
         <div class="form-group vat_fields">
          {!! Form::label('vat_based_on', __( 'category.vat_based_on' ) . ':*') !!}
          {!! Form::select('vat_based_on', ['vat_not_applicable' => __('category.vat_not_applicable'), 'sale_price' => __('category.sale_price'), 'profit' => __('category.profit'),'profit_percentage' => __('category.profit_percentage')], null, ['placeholder'
           => __( 'messages.please_select' ), 'required', 'class' => 'form-control category-native-select vat_based_on']); !!}
        </div>
        
      <!--end of changes made by oseahumen-->
        
        <div class="form-group vat_fields profit_percentage">
          {!! Form::label('profit_percentage', __( 'category.profit_percentage' ) . ':*') !!}
          {!! Form::text('profit_percentage',  null, ['placeholder'
          => __( 'category.profit_percentage' ),  'class' => 'form-control profit_percentage']); !!}
        </div>

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary" id="save_button" disabled>@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
 
  function initCategoryModalSelects() {
    var $modal = $('.category_modal');

    // Account/VAT selects must remain native for this form.
    $modal.find('.category-native-select').each(function(){
      if ($(this).data('select2')) { $(this).select2('destroy'); }
      $(this).css({'width':'100%', 'display':'block'});
    });

    // Parent category uses Select2. Re-initialize after the sub-category area is shown
    // so the dropdown is clickable and options are visible inside the modal.
    $modal.find('select.select2').not('.category-native-select').each(function(){
      if ($(this).data('select2')) { $(this).select2('destroy'); }
      $(this).select2({ dropdownParent: $modal, width: '100%' });
    });
  }

  initCategoryModalSelects();

   
  function toggleSaveButton() {
    let isFormValid = true;
    
     
    $('input[required]').each(function() {
      if ($(this).val() === '') {
        isFormValid = false;
      }
    });

     
    $('select[required]').each(function() {
      if ($(this).val() === null || $(this).val() === '') {
        isFormValid = false;
      }
    });

     
    if (isFormValid) {
      $('#save_button').prop('disabled', false);
    } else {
      $('#save_button').prop('disabled', true);
    }
  }

   
  $('input[required], select[required]').on('change input', function() {
    toggleSaveButton();
  });

   
  $(document).ready(function() {
    toggleSaveButton();
  });

   
  $(document).off('change.categoryWeight', '#weight_excess_loss_applicable').on('change.categoryWeight', '#weight_excess_loss_applicable', function () {
    if ($(this).prop('checked')) {
      $('.weight_excess_loss_applicable_field').removeClass('hide');
    } else {
      $('.weight_excess_loss_applicable_field').addClass('hide');
    }
  });

  $(document).off('change.categoryRelated', '[name=add_related_account]').on('change.categoryRelated', '[name=add_related_account]', function () {
    if ($(this).val() === 'sub_category_level') {
      $('.cogs_account').addClass('hide');
      $('.sales_income_account').addClass('hide');
    } else {
      $('.cogs_account').removeClass('hide');
      $('.sales_income_account').removeClass('hide');
    }
  });

  $(document).on('change', '.vat_exempted', function() {
    if ($(this).val() == 'Yes') {
      $(".vat_fields").hide();
    } else {
      $(".vat_fields").show();
    }
  });

  $(document).on('change', '.vat_based_on', function() {
    if ($(this).val() == 'profit_percentage') {
      $(".profit_percentage").show();
    } else {
      $(".profit_percentage").hide();
    }
  });

   
  $(document).off('change.categoryParent', '[name=add_as_sub_cat]').on('change.categoryParent', '[name=add_as_sub_cat]', function () {
    var checked = $(this).is(':checked');
    var $parentDiv = $('#parent_cat_div');
    var $parentSelect = $('[name=parent_id]');
    var $shortCodeInput = $('[name=short_code]');

    if (checked) {
      $parentDiv.removeClass('hide').show();
      $parentSelect.prop('required', true);
      $shortCodeInput.val('{{ $new_sub_category_short_code }}');
    } else {
      $parentSelect.val('').trigger('change').prop('required', false);
      $parentDiv.addClass('hide').hide();
      $shortCodeInput.val('{{ $new_category_short_code }}');
    }

    initCategoryModalSelects();
    toggleSaveButton();
  });

</script>
