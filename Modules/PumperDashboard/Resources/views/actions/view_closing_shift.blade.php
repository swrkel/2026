@extends('layouts.'.$layout)
@section('title', __('pumperdashboard::lang.closing_meter'))
<style>
    .side-label {
        font-size: 21px;
        font-weight: bold;
        padding-top: 5px;
    }

    #key_pad input {
        border: none
    }

    #key_pad button {
        height: 80px;
        width: 80px;
        font-size: 25px;
        margin: 2px 1px;
        border: none !important;
    }

    :focus {
        outline: 0 !important
    }
</style>
@section('content')
<div class="container">

    {{--
        A missing day entry must not produce a stack trace.

        This page is reached by pumper_day_entries.id. If that row has gone -
        deleted, or repointed to another shift, both of which happened during
        the settlement corrections of 22 August - the lookup returns null and
        every $pump-> below threw

            Attempt to read property "pump_no" on null

        on the operator's screen, with nothing to say what was wrong.

        The record still cannot be shown, because there is nothing to show. But
        the operator now sees why, and has a way back.
    --}}
    @if(empty($pump))
        <br>
        <div class="alert alert-warning" style="margin-top:30px;">
            <h4 style="margin-top:0;">
                <i class="fa fa-exclamation-triangle"></i>
                This closing meter record is no longer available.
            </h4>
            <p style="margin-bottom:0;">
                It may have been moved to another shift or removed during a
                settlement correction. Please go back and open the shift again.
            </p>
        </div>

        <a href="{{ url('pumper-dashboard/pump-operators/dashboard') }}"
           class="btn btn-primary btn-lg btn-flat">Back</a>
    @else
   
    <div class="row">
    <div class="col-md-12">
        <br>
        <br>
        <br>
       
        <div class="row">
            <div class="col-md-5">
                <div class="row">
                    <div class="col-md-5">
                        {!! Form::label('pump_no', __('pumperdashboard::lang.pump_no') .':', ['class' => 'side-label']) !!}
                    </div>
                    <div class="col-md-7">
                        {!! Form::text('pump_no', $pump->pump_no, ['class' => 'form-control input-lg', 'readonly']) !!}
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-5">
                        {!! Form::label('sale_price', __('pumperdashboard::lang.sale_price') .':', ['class' => 'side-label']) !!}
                    </div>
                    <div class="col-md-7">
                        {!! Form::text('sale_price', $pump->sell_price_inc_tax, ['class' => 'form-control input-lg',
                        'readonly']) !!}
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-5">
                        {!! Form::label('starting_meter', __('pumperdashboard::lang.starting_meter') .':', ['class' =>
                        'side-label']) !!}
                    </div>
                    <div class="col-md-7">
                        {!! Form::text('starting_meter', number_format($pump->starting_meter,3,".",""), ['class' => 'form-control input-lg',
                        'readonly', 'required']) !!}
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                
                <div class="row">
                    <div class="col-md-5">
                        {!! Form::label('testing_ltr', __('pumperdashboard::lang.testing_liters') .':', ['class' =>
                        'side-label']) !!}
                    </div>
                    <div class="col-md-7">
                        {!! Form::text('testing_ltr', number_format($pump->testing_ltr,3,".",""), ['class' => 'form-control input-lg inputcalculater','disabled'
                        ]) !!}
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-5">
                        {!! Form::label('closing_meter', __('pumperdashboard::lang.closing_meter') .':', ['class' =>
                        'side-label']) !!}
                    </div>
                    <div class="col-md-7">
                        {!! Form::text('closing_meter', number_format($pump->closing_meter,3,".",""), ['class' => 'form-control input-lg inputcalculater', 'disabled'
                            ]) !!}
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-5">
                        {!! Form::label('amount', __('pumperdashboard::lang.total_amount') .':', ['class' => 'side-label
                        text-red']) !!}
                    </div>
                    <div class="col-md-7">
                        {!! Form::text('amount', number_format($pump->amount,3,".",""), ['class' => 'form-control input-lg', 'readonly' , 'required'])
                        !!}
                    </div>
                    <input type="hidden" name="sold_ltr" id="sold_ltr" value="{{number_format($pump->sold_ltr,3,".","")}}">
                    <input type="hidden" name="amount_hidden" id="amount_hidden" value="{{number_format($pump->amount,3,".","")}}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="row">
                    <a href="{{url('pumper-dashboard/pump-operators/dashboard')}}" class="btn btn-primary btn-block btn-lg btn-flat">Back</a>
                </div>
            </div>
            
        </div>
    </div>
</div>
</div>

    @endif

@endsection





