@extends('layouts.app')
@section('title', trans('loan::lang.create') . ' ' . trans_choice('loan::general.loan', 1))

@section('css')
    
@endsection

@section('content')

<style>
    .invalid-feedback{
        color: red;
    }
</style>
    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">
                        @lang('loan::lang.create_loan')
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content no-print" id="vue-app">
        <div class="row">

            @component('components.widget')
                @slot('slot')
                    <section class="content">
                        {{-- Multi-part form --}}
                        <form class="msform" method="post" action="{{ url('contact_loan/store') }}" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            
                            {{-- Page one --}}
                            <fieldset>
                                @include('loan::loan.partials.details')
                                
                            </fieldset>

                            {{-- Page two --}}
                            <fieldset>
                                @include('loan::loan.partials.terms')
                                
                            </fieldset>

                            {{-- Page three --}}
                            <fieldset>
                                @include('loan::loan.partials.settings')
                                
                            </fieldset>

                            {{-- Page four --}}
                            <fieldset>
                                @include('loan::loan.partials.accounting')
                                
                            </fieldset>

                            {{-- Page five --}}
                            <fieldset>
                                <button type="submit" name="next"
                                    class="submit next btn btn-primary pull-right">{{ trans('loan::lang.submit') }}</button>
                            </fieldset>
                        </form>
                    </section>
                @endslot
            @endcomponent

        </div>
    </section>

@stop
@section('javascript')
    @php
    $expected_first_repayment_date = old(
        'expected_first_payment_date',
        \Illuminate\Support\Carbon::today()
            ->addMonths(1)
            ->format('Y-m-d'),
    );
    @endphp

@endsection
