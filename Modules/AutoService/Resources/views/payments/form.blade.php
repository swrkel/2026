@extends('autoservice::layouts.master')
@section('title','Auto Service Payment')
@section('autoservice_content')
{!! Form::open(['url'=>$payment->exists ? route('autoservice.payments.update',$payment->id) : route('autoservice.payments.store'), 'method'=>$payment->exists ? 'PUT':'POST']) !!}
<div class="box"><div class="box-header with-border"><h3 class="box-title">{{ $payment->exists ? 'Edit' : 'Add' }} Payment</h3></div><div class="box-body"><div class="row">
<div class="col-md-4"><div class="form-group">{!! Form::label('invoice_id','Invoice') !!}<select name="invoice_id" class="form-control"><option value="">Please Select</option>@foreach($invoices as $i)<option value="{{ $i->id }}" {{ $payment->invoice_id==$i->id?'selected':'' }}>{{ $i->invoice_no }} - Balance {{ number_format($i->balance_amount,2) }}</option>@endforeach</select></div></div>
<div class="col-md-4"><div class="form-group">{!! Form::label('payment_date','Date') !!}{!! Form::date('payment_date',$payment->payment_date,['class'=>'form-control','required']) !!}</div></div>
<div class="col-md-4"><div class="form-group">{!! Form::label('payment_method','Method') !!}{!! Form::select('payment_method',['cash'=>'Cash','card'=>'Card','bank'=>'Bank Transfer','cheque'=>'Cheque','credit'=>'Credit'], $payment->payment_method, ['class'=>'form-control','placeholder'=>'Please Select','required']) !!}</div></div>
<div class="col-md-4"><div class="form-group">{!! Form::label('reference_no','Reference No') !!}{!! Form::text('reference_no',$payment->reference_no,['class'=>'form-control']) !!}</div></div>
<div class="col-md-4"><div class="form-group">{!! Form::label('amount','Amount') !!}{!! Form::number('amount',$payment->amount,['class'=>'form-control','step'=>'0.0001','required']) !!}</div></div>
<div class="col-md-4"><div class="form-group">{!! Form::label('status','Status') !!}{!! Form::select('status',['received'=>'Received','void'=>'Void'], $payment->status ?: 'received', ['class'=>'form-control']) !!}</div></div>
<div class="col-md-12"><div class="form-group">{!! Form::label('note','Note') !!}{!! Form::textarea('note',$payment->note,['class'=>'form-control','rows'=>3]) !!}</div></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save</button><a href="{{ route('autoservice.payments.index') }}" class="btn btn-default">Cancel</a></div></div>
{!! Form::close() !!}
@endsection
