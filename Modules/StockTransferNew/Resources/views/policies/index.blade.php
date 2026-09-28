@extends('layouts.app')

@section('title', __('stocktransfernew::policies.title'))

@section('content')
<section class="content-header stn-page-header">
    <h1>{{ __('stocktransfernew::policies.title') }}</h1>
</section>

<section class="content stn-policy-page">
    <div class="row stn-kpi-row">
        <div class="col-md-3"><div class="stn-kpi"><span>{{ __('stocktransfernew::policies.active') }}</span><strong>{{ $summary['active'] }}</strong></div></div>
        <div class="col-md-3"><div class="stn-kpi"><span>{{ __('stocktransfernew::policies.cost_required') }}</span><strong>{{ $summary['cost_required'] }}</strong></div></div>
        <div class="col-md-3"><div class="stn-kpi"><span>{{ __('stocktransfernew::policies.cancel_approval') }}</span><strong>{{ $summary['cancel_approval_required'] }}</strong></div></div>
        <div class="col-md-3"><div class="stn-kpi"><span>{{ __('stocktransfernew::policies.inactive') }}</span><strong>{{ $summary['inactive'] }}</strong></div></div>
    </div>

    <div class="box stn-box">
        <div class="box-header with-border"><h3 class="box-title">{{ __('stocktransfernew::policies.add_policy') }}</h3></div>
        <form method="POST" action="{{ route('stocktransfernew.transfer-policies.store') }}">
            @csrf
            <div class="box-body row">
                <div class="form-group col-md-4">
                    <label>{{ __('stocktransfernew::policies.policy_name') }}</label>
                    <input type="text" name="policy_name" class="form-control" required>
                </div>
                <div class="form-group col-md-2">
                    <label>{{ __('stocktransfernew::policies.priority') }}</label>
                    <select name="priority" class="form-control">
                        <option value="normal">Normal</option><option value="urgent">Urgent</option><option value="critical">Critical</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>{{ __('stocktransfernew::policies.max_value') }}</label>
                    <input type="number" step="0.0001" name="max_transfer_value" class="form-control">
                </div>
                <div class="form-group col-md-2">
                    <label>{{ __('stocktransfernew::policies.status') }}</label>
                    <select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                </div>
                <div class="form-group col-md-2 stn-checks">
                    <label><input type="checkbox" name="requires_cost_allocation" value="1"> {{ __('stocktransfernew::policies.requires_cost') }}</label><br>
                    <label><input type="checkbox" name="requires_cancellation_approval" value="1"> {{ __('stocktransfernew::policies.requires_cancel') }}</label>
                </div>
            </div>
            <div class="box-footer"><button class="btn btn-primary stn-btn">{{ __('stocktransfernew::policies.save') }}</button></div>
        </form>
    </div>

    <div class="box stn-box">
        <div class="box-header with-border"><h3 class="box-title">{{ __('stocktransfernew::policies.policy_list') }}</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn-table">
                <thead><tr><th>#</th><th>{{ __('stocktransfernew::policies.policy_name') }}</th><th>{{ __('stocktransfernew::policies.priority') }}</th><th>{{ __('stocktransfernew::policies.max_value') }}</th><th>{{ __('stocktransfernew::policies.status') }}</th></tr></thead>
                <tbody>
                @forelse($policies as $policy)
                    <tr><td>{{ $policy->id }}</td><td>{{ $policy->policy_name }}</td><td>{{ ucfirst($policy->priority) }}</td><td>{{ number_format((float)$policy->max_transfer_value, 4) }}</td><td>{{ ucfirst($policy->status) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center">{{ __('stocktransfernew::policies.no_records') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $policies->links() }}
        </div>
    </div>
</section>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/transfer_policies.css') }}">
@endpush
@push('javascript')
<script src="{{ asset('modules/stocktransfernew/js/transfer_policies.js') }}"></script>
@endpush
