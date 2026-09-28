@extends('ran::layouts.app', ['title' => $title])
@section('ran-content')
<x-ran::page-header :title="$title" subtitle="Ran module master data">
    <a href="{{ route($routeBase.'.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add {{ $title }}</a>
</x-ran::page-header>
<div class="ran-card">
<div class="table-responsive"><table class="table table-bordered table-hover ran-table">
<thead><tr><th>#</th>@foreach($fields as $name=>$field)<th>{{ $field['label'] }}</th>@endforeach<th class="text-center">Action</th></tr></thead>
<tbody>@forelse($records as $record)<tr><td>{{ $record->id }}</td>@foreach($fields as $name=>$field)<td>@if(($field['type'] ?? '')==='checkbox'){{ $record->{$name} ? 'Yes' : 'No' }}@else{{ $record->{$name} }}@endif</td>@endforeach<td class="text-center"><a class="btn btn-xs btn-info" href="{{ route($routeBase.'.edit', $record->id) }}">Edit</a> <form class="inline-form" method="POST" action="{{ route($routeBase.'.destroy', $record->id) }}" onsubmit="return confirm('Delete this record?')">@csrf @method('DELETE')<button class="btn btn-xs btn-danger">Delete</button></form></td></tr>@empty<tr><td colspan="{{ count($fields)+2 }}" class="text-center text-muted">No records available.</td></tr>@endforelse</tbody>
</table></div>{{ $records->links() }}
</div>
@endsection
