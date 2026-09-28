@extends('layouts.app')
@section('title', 'Enterprise Report Builder - New')
@section('content')
<section class="content-header"><h1>Enterprise Report Builder - New <small>Finance Reports Enterprise v3.0</small></h1></section>
<section class="content">
@include('financereports::layouts.toolbar')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Report builder structure</h3></div><div class="box-body">
<div class="row">@foreach($fields as $group => $items)<div class="col-md-3"><h4>{{ ucwords(str_replace('_', ' ', $group)) }}</h4><ul>@foreach($items as $item)<li>{{ $item }}</li>@endforeach</ul></div>@endforeach</div><hr><h4>Starter Templates</h4><table class="table table-bordered"><thead><tr><th>Name</th><th>Group By</th><th>Output</th></tr></thead><tbody>@foreach($templates as $template)<tr><td>{{ $template['name'] }}</td><td>{{ $template['group_by'] }}</td><td>{{ $template['output'] }}</td></tr>@endforeach</tbody></table>
</div></div>
</section>
@endsection
