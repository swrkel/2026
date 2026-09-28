@extends('layouts.app')
@section('title', 'Scenario Analysis - New')
@section('content')
<section class="content-header"><h1>Scenario Analysis - New</h1></section>
<section class="content">
@include('financereports::layouts.toolbar', ['title' => 'Scenario Analysis - New'])
<form method="get" class="box box-solid"><div class="box-body row"><div class="col-md-3"><label>Revenue Change %</label><input name="revenue_change" value="{{ $revenue_change }}" class="form-control"></div><div class="col-md-3"><label>Expense Change %</label><input name="expense_change" value="{{ $expense_change }}" class="form-control"></div><div class="col-md-2"><label>&nbsp;</label><button class="btn btn-primary btn-block">Apply</button></div></div></form>
<div class="box box-primary"><div class="box-body"><table class="table table-bordered"><tbody>@foreach($scenario as $label => $value)<tr><th>{{ ucwords(str_replace('_',' ', $label)) }}</th><td>{{ is_numeric($value) ? number_format($value, 2) : $value }}</td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection
