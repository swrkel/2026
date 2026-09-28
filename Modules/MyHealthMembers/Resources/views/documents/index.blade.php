@extends('layouts.app')
@section('title', 'My Health Documents')

@section('content')
<section class="content-header"><h1>Documents - {{ $member->name }}</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header"><h3 class="box-title">Upload Document</h3></div>
        <div class="box-body">
            <form method="POST" action="{{ route('myhealth.documents.store', $member->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group"><label>Document Type</label><input type="text" name="document_type" class="form-control"></div>
                <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control"></div>
                <div class="form-group"><label>File *</label><input type="file" name="file" class="form-control" required></div>
                <button class="btn btn-primary">Upload</button>
            </form>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Date</th><th>Type</th><th>Title</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach($documents as $document)
                        <tr>
                            <td>{{ $document->created_at }}</td>
                            <td>{{ $document->document_type }}</td>
                            <td>{{ $document->title }}</td>
                            <td><a class="btn btn-xs btn-primary" href="{{ route('myhealth.documents.download', $document->id) }}">Download</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $documents->links() }}
        </div>
    </div>
</section>
@endsection
