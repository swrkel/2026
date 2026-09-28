<div class="modal-dialog modal-xl" role="document">
  <div class="modal-content">

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'bakery::lang.loading' )</h4>
    </div>

    <div class="modal-body">
      <div class="row">
          <div class="col-sm-4">
              <b>@lang('bakery::lang.date'): </b> {{@format_date($data->date)}}<br>
              <b>@lang('bakery::lang.form_no'): </b> {{$data->form_no}}<br>
              <b>@lang('bakery::lang.vehicle'): </b> {{$data->vehicle_number}}<br>
              
          </div>
          <div class="col-sm-4">
              <b>@lang('bakery::lang.driver'): </b> {{$data->driver_name}}<br>
              <b>@lang('bakery::lang.route'): </b> {{$data->route_name}}<br>
              <b>@lang('bakery::lang.user_added'): </b> {{$data->username}}<br>
          </div>
          
          <div class="col-sm-4 amountDetails">
                <b>@lang('bakery::lang.total_amount'): </b> 
                <span id="total_amount_display">0.00</span><br>
            
                <b>@lang('bakery::lang.settled_amount'): </b>
                <span id="settled_amount_display">0.00</span><br>
            
                <b>@lang('bakery::lang.short_amount'): </b>
                <span id="short_amount_display">0.00</span><br>
            </div>
      </div>
      
          <hr>
      <div class="col-sm-12">
          <div class="table-responsive">
          <table class="table table-bordered table-striped" id="show_loading_product_table" style="width: 100%;">
                <thead>
                <tr>
                  <th>@lang('bakery::lang.product')</th>
                  <th>@lang('bakery::lang.unit_cost')</th>
                  <th>@lang('bakery::lang.quantity_issued')</th>
                  <th>@lang('bakery::lang.due_for_the_product')</th>
                  <th>@lang('bakery::lang.returned_qty')</th>
                  <th>@lang('bakery::lang.returned_qty_amt')</th>
                  <th>@lang('bakery::lang.settled_amt')</th>
                  <th>@lang('bakery::lang.short_amt')</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                  <td colspan="3" class="text-right text-bold">@lang('bakery::lang.total_due')</td>
                  <td class="text-right text-bold"><span id="show_loading_total_due">0.00</span></td>
                  <td colspan="4"></td>
                </tr>
                </tfoot>
              </table>
            </div>
      </div>
      
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
   
    
    $(document).ready(function () {
    show_loading_product_table = $('#show_loading_product_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'desc']],
        ajax: {
            url: '{{action('\Modules\Bakery\Http\Controllers\BakeryLoadingController@getProductsShow',[$data->id])}}',
            data: function (d) {
                // You can pass extra filters if needed
            }
        },
        @include('layouts.partials.datatable_export_button')
        columns: [
            { data: 'name', name: 'name' },
            { data: 'unit_cost', name: 'unit_cost' , className: 'text-right' },
            { data: 'qty', name: 'qty' ,searchable: false, className: 'text-right' },
            { data: 'total_amount', name: 'total_amount' , className: 'text-right' },
            { data: 'returned_qty', name: 'returned_qty' ,searchable: false, className: 'text-right' },
            { data: 'returned_qty_amt', name: 'returned_qty_amt' ,searchable: false, className: 'text-right' },
            { data: 'settled_amt', name: 'settled_amt' ,searchable: false, className: 'text-right' },
            { data: 'short_amt', name: 'short_amt' ,searchable: false, className: 'text-right' },
        ],
        fnDrawCallback: function(oSettings) {
            $(".table_entered_qty").trigger('input');

            // Get API instance
            var api = this.api();

            // Helper to parse numbers (removing commas, currency symbols, etc.)
            var intVal = function (i) {
                return typeof i === 'string' ?
                    parseFloat(i.replace(/[\$,]/g, '')) :
                    typeof i === 'number' ?
                        i : 0;
            };

            // Sum for total_amount column (index 3)
            var total_amount = api.column(3, {page:'current'} ).data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Sum for settled_amt column (index 6)
            var settled_amount = api.column(6, {page:'current'} ).data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Sum for short_amt column (index 7)
            var short_amount = api.column(7, {page:'current'} ).data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Update HTML
            $('#total_amount_display').text(total_amount.toFixed(2));
            $('#settled_amount_display').text(settled_amount.toFixed(2));
            $('#short_amount_display').text(short_amount.toFixed(2));
            $('#show_loading_total_due').text(total_amount.toFixed(2));
        },
    });
});

</script>
