@extends('autoservice::layouts.master')
@section('content')
<section class="content-header"><h1>Auto Service Documents</h1></section>
<section class="content"><a href="{{ route('autoservice.documents.create') }}" class="btn btn-primary">Add Document</a><div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Type</th><th>Title</th><th>File</th><th>Customer Visible</th></tr></thead><tbody>@foreach($documents as $doc)<tr><td>{{ @format_datetime($doc->created_at) }}</td><td>{{ $doc->document_type }}</td><td>{{ $doc->title }}</td><td>@if($doc->file_path)<a target="_blank" href="{{ asset('storage/'.$doc->file_path) }}">{{ $doc->file_name }}</a>@endif</td><td>{{ $doc->visible_to_customer ? 'Yes' : 'No' }}</td></tr>@endforeach</tbody></table>{{ $documents->links() }}</div></div></section>
@endsection
