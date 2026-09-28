@extends('stocktransfernew::layouts.app')
@section('content')
@include('stocktransfernew::partials.header',['title'=>'Approval Matrix','subtitle'=>'Business, location, store, value and role based approval setup'])
<div class="row">
    <div class="col-md-4"><div class="stn-card"><h4>Create Matrix</h4><form method="POST" action="{{ route('stock-transfer-new.approval-matrix.store') }}">@csrf
        <label>Mode</label><select name="approval_mode" class="form-control"><option value="sequential">Sequential</option><option value="parallel">Parallel</option></select>
        <label class="mt-2">Minimum Amount</label><input name="min_amount" class="form-control" value="0">
        <label class="mt-2">Maximum Amount</label><input name="max_amount" class="form-control">
        <div class="stn-approval-steps mt-3">
            <label>Approval Steps</label>
            @for($i=0;$i<3;$i++)
            <div class="row mb-2"><div class="col"><input name="role_name[]" class="form-control" placeholder="Role name"></div><div class="col"><input name="approver_user_id[]" class="form-control" placeholder="User ID"></div></div>
            @endfor
        </div>
        <button class="btn btn-primary mt-2">Save Matrix</button>
    </form></div></div>
    <div class="col-md-8"><div class="stn-card"><h4>Existing Matrices</h4><table class="table table-bordered stn-table"><thead><tr><th>Mode</th><th>Amount Range</th><th>Steps</th><th>Active</th><th></th></tr></thead><tbody>
        @forelse($matrices as $matrix)<tr><td>{{ ucfirst($matrix->approval_mode) }}</td><td>{{ $matrix->min_amount }} - {{ $matrix->max_amount ?? 'Any' }}</td><td>@foreach($matrix->steps as $step)<span class="badge badge-secondary">{{ $step->step_order }}. {{ $step->role_name ?: 'User '.$step->approver_user_id }}</span> @endforeach</td><td>{{ $matrix->is_active ? 'Yes' : 'No' }}</td><td><form method="POST" action="{{ route('stock-transfer-new.approval-matrix.destroy',$matrix) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Delete</button></form></td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No approval matrix configured.</td></tr>@endforelse
    </tbody></table>{{ $matrices->links() }}</div></div>
</div>
@endsection
