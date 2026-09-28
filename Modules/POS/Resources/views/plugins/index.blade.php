@extends('pos::layouts.app')
@section('content')
<h3>POS Plugin Registry</h3>
<p>Core POS remains standalone. Business plugins add optional behavior without sharing core files.</p>
<table class="table table-bordered"><thead><tr><th>Plugin</th><th>Status</th><th>Purpose</th></tr></thead><tbody>
<tr><td>Core POS</td><td><span class="label label-success">Enabled</span></td><td>Sales, payments, returns, receipts, shifts</td></tr>
<tr><td>Beauty Saloons</td><td><span class="label label-default">Optional</span></td><td>Service/package redemption hooks</td></tr>
<tr><td>Hotel</td><td><span class="label label-default">Optional</span></td><td>Room charge posting hooks</td></tr>
<tr><td>Restaurant</td><td><span class="label label-default">Optional</span></td><td>Table/kitchen hooks</td></tr>
<tr><td>Retail</td><td><span class="label label-default">Optional</span></td><td>Standard product retail hooks</td></tr>
</tbody></table>
@endsection
