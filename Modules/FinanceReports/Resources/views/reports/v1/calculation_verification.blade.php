@extends('layouts.app')
@section('title', 'Calculation Verification - New')
@section('content')
<section class="content-header"><h1>Calculation Verification - New</h1></section>
<section class="content">
<div class="box box-warning"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Area</th><th>Verification Required</th></tr></thead><tbody>
@foreach($verification as $row)
<tr><td>{{ $row['area'] }}</td><td>{{ $row['check'] }}</td></tr>
@endforeach
</tbody></table>
<p class="text-muted">Use this page during UAT to compare the new Finance Reports figures with the trusted existing Finance module figures.</p>
</div></div>
</section>
@endsection
