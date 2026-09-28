@extends('communicationhub::layout')

@section('communicationhub_title', 'Communication Audit')
@section('communicationhub_content')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Audit Readiness</h3></div><div class="box-body table-responsive"><table class="table table-bordered"><tbody>@foreach($audit as $label=>$value)<tr><td>{{ ucwords(str_replace('_',' ', $label)) }}</td><td>@if(is_bool($value))<span class="label label-{{ $value ? 'success' : 'danger' }}">{{ $value ? 'Yes' : 'No' }}</span>@else{{ $value }}@endif</td></tr>@endforeach</tbody></table></div></div>
@endsection
