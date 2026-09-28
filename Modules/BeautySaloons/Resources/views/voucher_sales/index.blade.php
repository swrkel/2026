@extends('layouts.app')
@section('title', __('beautysaloons::gift_vouchers.voucher_sales'))
@section('content')
<section class="content-header"><h1>{{ __('beautysaloons::gift_vouchers.voucher_sales') }}</h1></section>
<section class="content">
    <div class="box box-primary"><div class="box-body table-responsive">
        <table class="table table-bordered table-striped bs-voucher-table" style="width:100%">
            <thead><tr><th>#</th><th>Reference</th><th>Amount</th><th>Balance</th><th>Status</th><th>Action</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</section>
@endsection
