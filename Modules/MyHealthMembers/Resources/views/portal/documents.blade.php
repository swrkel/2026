@extends('myhealthmembers::portal.layout')
@section('title', 'Documents')
@section('content')
<h3 class="page-title">Documents</h3>
<div class="mh-card"><div class="mh-card-header">My Documents</div><div class="mh-card-body">
@if(isset($documents) && count($documents))
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Title</th><th>Type</th><th>Date</th><th class="text-center">Action</th></tr></thead>
        <tbody>@foreach($documents as $doc)<tr>
            <td>{{ $doc->title ?? $doc->document_name ?? 'Document' }}</td>
            <td>{{ $doc->document_type ?? '-' }}</td>
            <td>{{ $doc->created_at ?? '-' }}</td>
            <td class="text-center"><a class="btn btn-xs btn-mh" href="{{ route('myhealth.member.portal.documents.download', $doc->id) }}"><i class="fa fa-download"></i> Download</a></td>
        </tr>@endforeach</tbody>
    </table></div>
@else
    <div class="empty-state"><i class="fa fa-folder-open"></i><div>No documents found.</div></div>
@endif
@if(isset($documents) && method_exists($documents, 'links')) <div class="text-center">{!! $documents->links() !!}</div> @endif
</div></div>
@endsection
