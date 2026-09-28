@extends('tailoring::layouts.app')
@section('page_title', 'Production Planning')
@section('tailoring_content')
<div class="row">
@foreach($summary as $label => $value)<div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $value }}</h3><p>{{ ucwords(str_replace('_',' ', $label)) }}</p></div></div></div>@endforeach
</div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Production Plan</h3></div><div class="box-body">
<form method="POST" action="{{ route('tailoring.production.planning.store') }}">@csrf
<div class="row"><div class="col-md-2"><input type="date" class="form-control" name="plan_date" value="{{ date('Y-m-d') }}" required></div><div class="col-md-2"><select name="plan_type" class="form-control"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></div><div class="col-md-2"><input type="number" class="form-control" name="planned_qty" placeholder="Qty"></div><div class="col-md-2"><select name="priority" class="form-control"><option value="normal">Normal</option><option value="urgent">Urgent</option><option value="rush">Rush</option></select></div><div class="col-md-4"><input class="form-control" name="notes" placeholder="Notes"></div></div><br><button class="btn btn-primary">Save Plan</button>
</form><hr><table class="table table-bordered"><thead><tr><th>Date</th><th>Type</th><th>Planned</th><th>Completed</th><th>Priority</th></tr></thead><tbody>@forelse($plans as $p)<tr><td>{{ optional($p->plan_date)->format('Y-m-d') }}</td><td>{{ $p->plan_type }}</td><td>{{ $p->planned_qty }}</td><td>{{ $p->completed_qty }}</td><td>{{ $p->priority }}</td></tr>@empty<tr><td colspan="5" class="text-center">No plans</td></tr>@endforelse</tbody></table>
</div></div>
@endsection
