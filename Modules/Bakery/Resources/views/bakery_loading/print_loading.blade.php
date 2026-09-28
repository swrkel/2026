@extends('layouts.app')
@section('title', __('bakery::lang.loading'))

@section('content')
<section class="content-header no-print">
    <h1>@lang('bakery::lang.loading')</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            <div class="row">
                <div class="col-sm-4">
                    <b>@lang('bakery::lang.date'):</b> {{ @format_date($data->date) }}<br>
                    <b>@lang('bakery::lang.form_no'):</b> {{ $data->form_no }}<br>
                    <b>@lang('bakery::lang.vehicle'):</b> {{ $data->vehicle_number }}<br>
                </div>
                <div class="col-sm-4">
                    <b>@lang('bakery::lang.driver'):</b> {{ $data->driver_name }}<br>
                    <b>@lang('bakery::lang.route'):</b> {{ $data->route_name }}<br>
                    <b>@lang('bakery::lang.user_added'):</b> {{ $data->username }}<br>
                </div>
            </div>

            <hr>

            @php
                $total_due = $products->sum('total_amount');
            @endphp

            <div class="table-responsive">
                <table class="table table-bordered table-striped" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>@lang('bakery::lang.product')</th>
                            <th class="text-right">@lang('bakery::lang.unit_cost')</th>
                            <th class="text-right">@lang('bakery::lang.quantity_issued')</th>
                            <th class="text-right">@lang('bakery::lang.due_for_the_product')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td>{{ $product->product_name }}</td>
                                <td class="text-right">{{ @num_format($product->unit_cost) }}</td>
                                <td class="text-right">{{ @num_format($product->qty) }}</td>
                                <td class="text-right">{{ @num_format($product->total_amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right text-bold">@lang('bakery::lang.total_due')</td>
                            <td class="text-right text-bold">{{ @num_format($total_due) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        window.print();
    });
</script>
@endsection
