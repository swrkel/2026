@extends('layouts.app')
@section('title', __('beautysaloons::gift_vouchers.voucher_sales'))
@section('content')
<section class="content-header"><h1>{{ __('beautysaloons::gift_vouchers.voucher_sales') }}</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
    <form method="POST">@csrf
        <div class="row">
            <div class="col-md-4"><div class="form-group"><label>Reference</label><input type="text" name="reference" class="form-control"></div></div>
            <div class="col-md-4"><div class="form-group"><label>Amount</label><input type="text" name="amount" class="form-control input_number"></div></div>
            <div class="col-md-4"><div class="form-group"><label>Expiry Date</label><input type="text" name="expiry_date" class="form-control datepicker"></div></div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">Save</button>
    </form>
</div></div></section>
@endsection
