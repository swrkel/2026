@extends('layouts.app')
@section('title', 'Restaurant Table Operations')

@section('content')
<section class="content-header restaurant-new-page-header">
    <h1>Restaurant Table Operations <small>Audit trail for transfer, merge and waiter changes</small></h1>
</section>
<section class="content restaurant-new-page">
    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">Operation History</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped rn-datatable">
                <thead>
                    <tr>
                        <th>Date</th><th>Order</th><th>Operation</th><th>From Table</th><th>To Table</th><th>From Waiter</th><th>To Waiter</th><th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($operations as $operation)
                    <tr>
                        <td>{{ optional($operation->created_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ optional($operation->order)->order_no }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $operation->operation_type)) }}</td>
                        <td>{{ $operation->from_table_id }}</td>
                        <td>{{ $operation->to_table_id }}</td>
                        <td>{{ $operation->from_waiter_id }}</td>
                        <td>{{ $operation->to_waiter_id }}</td>
                        <td>{{ $operation->remarks }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $operations->links() }}
        </div>
    </div>
</section>
@endsection
