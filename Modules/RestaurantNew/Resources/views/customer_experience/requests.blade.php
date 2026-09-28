@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.customer_experience'))
@section('content')
<section class="content-header"><h1>{{ __('restaurantnew::lang.customer_experience') }}</h1></section>
<section class="content">
    <div class="rn-command-card">
        <div class="rn-toolbar">
            <h4>{{ __('restaurantnew::lang.table_service_requests') }}</h4>
            <button class="btn btn-primary" data-toggle="modal" data-target="#rnNewRequestModal">{{ __('restaurantnew::lang.new_request') }}</button>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped rn-datatable">
                <thead>
                    <tr>
                        <th>{{ __('restaurantnew::lang.request_no') }}</th>
                        <th>{{ __('restaurantnew::lang.type') }}</th>
                        <th>{{ __('restaurantnew::lang.table') }}</th>
                        <th>{{ __('restaurantnew::lang.order') }}</th>
                        <th>{{ __('restaurantnew::lang.status') }}</th>
                        <th>{{ __('restaurantnew::lang.note') }}</th>
                        <th>{{ __('restaurantnew::lang.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $row)
                        <tr>
                            <td>{{ $row->request_no }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $row->request_type)) }}</td>
                            <td>{{ $row->table_id }}</td>
                            <td>{{ $row->order_id }}</td>
                            <td><span class="label label-info">{{ $row->status }}</span></td>
                            <td>{{ $row->customer_note }}</td>
                            <td>
                                <button class="btn btn-xs btn-success rn-request-status" data-id="{{ $row->id }}" data-status="acknowledged">{{ __('restaurantnew::lang.acknowledge') }}</button>
                                <button class="btn btn-xs btn-primary rn-request-status" data-id="{{ $row->id }}" data-status="completed">{{ __('restaurantnew::lang.complete') }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
</section>
@endsection
