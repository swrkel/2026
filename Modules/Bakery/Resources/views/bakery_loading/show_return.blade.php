<div class="modal-dialog modal-xl" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('bakery::lang.returns')</h4>
    </div>
    <div class="modal-body">
      <div class="row">
          <div class="col-sm-4">
              <b>@lang('bakery::lang.return_form_no'): </b> {{ $data->form_no }}<br>
              <b>@lang('bakery::lang.return_loading_form_no'): </b> {{ $data->loading_form_no }}<br>
              <b>@lang('bakery::lang.vehicle'): </b> {{ $data->vehicle_number }}<br>
          </div>
          <div class="col-sm-4">
              <b>@lang('bakery::lang.driver'): </b> {{ $data->driver_name }}<br>
              <b>@lang('bakery::lang.routes_trips'): </b> {{ $data->route_name }}<br>
              <b>@lang('bakery::lang.user_added'): </b> {{ $data->username }}<br>
          </div>
      </div>
      <hr>
      @php
        $total_qty_returned = $products->sum('qty_returned');
        $total_returned = $products->sum('amount_returned');
        $total_settled = $products->sum('received_amount');
        $total_short = $products->sum(function ($product) { return $product->amount_returned - $product->received_amount; });
      @endphp
      <div class="table-responsive">
        <table class="table table-bordered table-striped" style="width:100%;">
          <thead>
            <tr>
              <th>@lang('bakery::lang.product')</th>
              <th class="text-right">@lang('bakery::lang.qty_returned')</th>
              <th class="text-right">@lang('bakery::lang.total_returned')</th>
              <th class="text-right">@lang('bakery::lang.total_settled')</th>
              <th class="text-right">@lang('bakery::lang.total_short')</th>
            </tr>
          </thead>
          <tbody>
            @foreach($products as $product)
              <tr>
                <td>{{ $product->product_name }}</td>
                <td class="text-right">{{ @num_format($product->qty_returned) }}</td>
                <td class="text-right">{{ @num_format($product->amount_returned) }}</td>
                <td class="text-right">{{ @num_format($product->received_amount) }}</td>
                <td class="text-right">{{ @num_format($product->amount_returned - $product->received_amount) }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <td class="text-right text-bold">@lang('sale.total')</td>
              <td class="text-right text-bold">{{ @num_format($total_qty_returned) }}</td>
              <td class="text-right text-bold">{{ @num_format($total_returned) }}</td>
              <td class="text-right text-bold">{{ @num_format($total_settled) }}</td>
              <td class="text-right text-bold">{{ @num_format($total_short) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>
  </div>
</div>
