@extends('restaurantnew::layouts.app')

@section('title', $bill->bill_no)

@section('content')
<div class="restaurantnew-page restaurantnew-bill-show">
    @include('restaurantnew::partials.toolbar', [
        'title' => $bill->bill_no,
        'buttons' => [
            ['label' => __('restaurantnew::lang.receipt'), 'url' => route('restaurantnew.billing.receipt', $bill->id), 'class' => 'btn btn-dark', 'target' => '_blank']
        ]
    ])

    <div class="row">
        <div class="col-md-8">
            @include('restaurantnew::billing.partials.bill-lines')
        </div>
        <div class="col-md-4">
            @include('restaurantnew::billing.partials.summary-card')
            @include('restaurantnew::billing.partials.add-payment')
            @include('restaurantnew::billing.partials.refund-void')
        </div>
    </div>
</div>
@endsection
