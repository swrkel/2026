@extends('stocktransfernew::layouts.app')
@section('content')
@include('stocktransfernew::partials.header',['title'=>'Approval Workflow','subtitle'=>'Multi-level approvals, return for correction, and rejection tracking'])
<div class="stn-card">
    @include('stocktransfernew::partials.list_toolbar',['title'=>'Approval Queue'])
    <div class="table-responsive">
        <table class="table table-bordered table-striped stn-table">
            <thead><tr><th>Transfer No</th><th>Date</th><th>Status</th><th>Current Step</th><th>Timeline</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($transfers as $transfer)
                <tr>
                    <td>{{ $transfer->transfer_no ?? $transfer->id }}</td>
                    <td>{{ optional($transfer->transfer_date)->format('Y-m-d') }}</td>
                    <td><span class="badge badge-info">{{ str_replace('_',' ',ucfirst($transfer->status)) }}</span></td>
                    <td>{{ $transfer->current_approval_step }}</td>
                    <td>
                        @foreach($transfer->approvalSteps as $step)
                            <span class="stn-step stn-step-{{ $step->status }}">Step {{ $step->step_order }}: {{ ucfirst($step->status) }}</span>
                        @endforeach
                    </td>
                    <td>
                        <form method="POST" action="{{ route('stock-transfer-new.approval-workflow.approve-step',$transfer) }}" class="d-inline">@csrf<input name="remarks" class="form-control form-control-sm mb-1" placeholder="Remarks"><button class="btn btn-sm btn-success">Approve Step</button></form>
                        <form method="POST" action="{{ route('stock-transfer-new.approval-workflow.return',$transfer) }}" class="d-inline">@csrf<input type="hidden" name="remarks" value="Returned for correction"><button class="btn btn-sm btn-warning">Return</button></form>
                        <form method="POST" action="{{ route('stock-transfer-new.approval-workflow.reject',$transfer) }}" class="d-inline">@csrf<input type="hidden" name="remarks" value="Rejected"><button class="btn btn-sm btn-danger">Reject</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No transfers waiting for approval.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $transfers->links() }}
</div>
@endsection
