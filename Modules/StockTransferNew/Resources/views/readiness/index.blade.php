@extends('layouts.app')

@section('title', __('stocktransfernew::lang.stock_transfer_new_readiness'))

@section('content')
<section class="content-header stn-final-header">
    <h1>{{ __('stocktransfernew::lang.stock_transfer_new_readiness') }}</h1>
</section>

<section class="content stn-final-page">
    <div class="row">
        @foreach($summary as $label => $value)
            <div class="col-md-3 col-sm-6">
                <div class="stn-final-card">
                    <span>{{ ucwords(str_replace('_', ' ', $label)) }}</span>
                    <strong>{{ $value }}</strong>
                </div>
            </div>
        @endforeach
    </div>

    <div class="box box-solid stn-final-box">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('stocktransfernew::lang.final_readiness_checks') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('stocktransfernew::lang.area') }}</th>
                        <th>{{ __('stocktransfernew::lang.status') }}</th>
                        <th>{{ __('stocktransfernew::lang.note') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($checks as $check)
                        <tr>
                            <td>{{ $check['area'] }}</td>
                            <td><span class="label label-success">{{ $check['status'] }}</span></td>
                            <td>{{ $check['note'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('modules/stocktransfernew/js/stocktransfernew-final.js') }}"></script>
@endsection
