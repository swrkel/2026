<div class="modal-dialog" role="document" style="width: 60%">
    <div class="modal-content">
  
      {!! Form::open(['url' => action('DepositsController@postRealizeChequeDeposit'), 'method' => 'post', 'id' => 'deposit_form',
      'enctype' => 'multipart/form-data' ]) !!}
  
      <div class="modal-header text-center">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">@lang( 'account.realize_cheque' )</h4>
      </div>
  
      <div class="modal-body">
         
          <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  {!! Form::label('realize_cheque_date', __('account.cheque_date') . ':') !!}
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                    {!! Form::text('realize_cheque_date', null, ['class' => 'form-control', 'readonly', 'placeholder' => __('account.cheque_date')]) !!}
                  </div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  {!! Form::label('realize_date', __('account.realize_date') . ':') !!}
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                    {!! Form::text('realize_date', null, ['class' => 'form-control', 'readonly', 'placeholder' => __('account.realize_date')]) !!}
                  </div>
                </div>
              </div>
              
              <div class="col-sm-4">
                    <div class="form-group">
                      {!! Form::label('customer_cheque_no', __('lang_v1.customer_cheque_number').':') !!}
                      <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-exchange"></i></span><!-- @eng START 13/2 -->
                        {!! Form::select('customer_cheque_no', [], null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.all'), 'id' => "cheque_customer_cheque_no"]) !!}
                      </div><!-- @eng END 13/2 -->
                </div>
            </div>
            
            <div class="col-sm-4">
                <div class="form-group">
                  {!! Form::label('customer_amount', __('lang_v1.amount').':') !!}
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-exchange"></i></span><!-- @eng START 13/2 -->
                    {!! Form::select('customer_amount', [], null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.all'), 'id' => "cheque_customer_amount"]) !!}
                  </div> <!-- @eng END 13/2 -->
                </div>
            </div>
            
              <div class="col-sm-4">
                <div class="form-group">
                  {!! Form::label('realize_cheque_bank', __( 'account.bank' ) .":") !!}
                  {!! Form::select('realize_cheque_bank', $to_accounts, null, ['class' => 'form-control select2',  'placeholder' =>
                  __('messages.please_select') ]); !!}
                </div>
            </div>
              
          </div>
          
          
            
         
          <div class="clearfix"></div>
          <table class="table table-bordered table-striped" id="realize_cheque_list_table">
            <thead>
             <tr>
             <th>@lang('account.select')</th>
             <th>@lang('lang_v1.name')</th>
             <th>@lang('account.cheque_no')</th>
             <th>@lang('account.cheque_date')</th>
             <th>@lang('account.bank')</th>
             <th>@lang('account.amount')</th>
            </tr>
          </thead>
          <tbody></tbody>
          </table>

  
      </div>
  
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary submit_btn">@lang( 'messages.submit' )</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
      </div>
  
      {!! Form::close() !!}
  
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
  
  <script type="text/javascript">
    // Function to load realize cheque list
    function get_realize_cheques_list(){
        if($('#realize_cheque_date').val()){
            start_date = $('input#realize_cheque_date').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#realize_cheque_date').data('daterangepicker').endDate.format('YYYY-MM-DD');
            
            cheque_no = $('#cheque_customer_cheque_no').val();
            amount = $('#cheque_customer_amount').val();
            realize_cheque_bank = $('#realize_cheque_bank').val();
            
            $.ajax({
                method: 'get',
                url: '{{action("DepositsController@getRealizeChequeList")}}',
                data: { start_date, end_date, cheque_no, amount, realize_cheque_bank },
                contentType: 'html',
                success: function(result) {
                    $('#realize_cheque_list_table tbody').empty().append(result);
                    
                    // Initialize iCheck for the newly loaded checkboxes
                    setTimeout(function() {
                        $('#realize_cheque_list_table .input-icheck').each(function() {
                            var self = $(this);
                            self.iCheck({
                                checkboxClass: 'icheckbox_square-blue',
                                radioClass: 'iradio_square-blue',
                                increaseArea: '20%'
                            });
                        });
                    }, 100);
                },
            });
        }
    }
  
    $(document).ready( function(){
        
        $('#cheque_customer_cheque_no, #cheque_customer_amount,#realize_cheque_bank').change(function(){
            get_realize_cheques_list();
        })
    
    
        $.ajax({
            method: 'get',
            url: '/customer-payment-information/all/cheque_no',
            data: {},
            success: function (result) {
                var options = result.data;
                var selectElement = $('#cheque_customer_cheque_no');
                
                // Clear existing options
                selectElement.empty();
                
                // Add new options
                selectElement.append($('<option></option>').attr('value', "").text("@lang('lang_v1.all')"));
                $.each(options, function(index, value) {
                  selectElement.append($('<option></option>').attr('value', value).text(value));
                });
            },
        });
        $.ajax({
            method: 'get',
            url: '/customer-payment-information/all/amount',
            data: {},
            success: function (result) {
                
                if (result.data.length === 0 || result.data[0] !== '') {
                  result.data.unshift('');
                }
                
                var options = result.data;
                                
                var selectElement = $('#cheque_customer_amount');
                
                // Clear existing options
                selectElement.empty();
                
                // Add new options
                selectElement.append($('<option></option>').attr('value', "").text("@lang('lang_v1.all')"));
                $.each(options, function(index, value) {
                  selectElement.append($('<option></option>').attr('value', value).text(value));
                });
            },
        }); 
    
    
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        defaultDate: new Date() // Set this to the default date and time you want
    });

      
      
      
      
      
    });

    $('#realize_cheque_date').daterangepicker(
      dateRangeSettings,
      function (start, end) {
        $('#realize_cheque_date').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));

        get_realize_cheques_list();
        }
    );
    
    $('#realize_cheque_date')
                .data('daterangepicker')
                .setStartDate(moment().startOf('month'));
    $('#realize_cheque_date')
        .data('daterangepicker')
        .setEndDate(moment().endOf('month'));

    $('#realize_cheque_date').trigger('change');
    
    $('#realize_date').daterangepicker(
      dateRangeSettings,
      function (start, end) {
        $('#realize_date').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));

        get_realize_cheques_list();
        }
    );
    
    $('#realize_date')
                .data('daterangepicker')
                .setStartDate(moment().startOf('month'));
    $('#realize_date')
        .data('daterangepicker')
        .setEndDate(moment().endOf('month'));

    $('#realize_date').trigger('change');
    
    $('.select2').select2();
    
    // Initialize the cheque list on page load
    get_realize_cheques_list();
    
    // Prevent multiple form submissions and sync iCheck values
    $(document).on('submit', '#deposit_form', function(e) {
        e.preventDefault();
        
        var submitBtn = $(this).find('.submit_btn');
        if (submitBtn.prop('disabled')) {
            return false;
        }
        
        // Manually sync all iCheck checkboxes to their actual input values
        $('#realize_cheque_list_table .input-icheck').each(function() {
            var $checkbox = $(this);
            var $icheckbox = $checkbox.closest('.icheckbox_square-blue');
            
            if ($icheckbox.hasClass('checked')) {
                $checkbox.prop('checked', true);
            } else {
                $checkbox.prop('checked', false);
            }
        });
        
        // Check if at least one checkbox is checked
        var checkedCount = $('#realize_cheque_list_table .input-icheck:checked').length;
        if (checkedCount === 0) {
            toastr.error('Please select at least one cheque to realize.');
            return false;
        }
        
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fa fa-spinner fa-spin"></i> @lang("messages.processing")');
        
        // Submit the form
        this.submit();
    });
  </script>