@extends('layouts.app')
@section('title', 'Lab Records')

@section('content')
<section class="content-header"><h1>Lab Records - {{ $member->name }}</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header"><h3 class="box-title">New Lab Request</h3></div>
        <div class="box-body">
            <form method="POST" action="{{ route('myhealth.lab.request.store', $member->id) }}">
                @csrf
                <div class="form-group"><label>Request Date *</label><input type="date" name="request_date" value="{{ date('Y-m-d') }}" class="form-control" required></div>
                <div class="form-group"><label>Test Name *</label><input type="text" name="test_name" class="form-control" required></div>
                <div class="form-group"><label>Clinical Notes</label><textarea name="clinical_notes" class="form-control"></textarea></div>
                <button class="btn btn-primary">Save Request</button>
            </form>
        </div>
    </div>

    @foreach($labRequests as $labRequest)
        <div class="box box-info">
            <div class="box-header">
                <h3 class="box-title">{{ $labRequest->lab_request_no }} - {{ $labRequest->test_name }} ({{ $labRequest->status }})</h3>
            </div>
            <div class="box-body">
                <p><b>Request Date:</b> {{ $labRequest->request_date }}</p>
                <p><b>Notes:</b> {{ $labRequest->clinical_notes }}</p>

                @if($labRequest->result)
                    <p><b>Result Date:</b> {{ $labRequest->result->result_date }}</p>
                    <p><b>Summary:</b> {{ $labRequest->result->result_summary }}</p>
                    <p><b>Details:</b> {{ $labRequest->result->result_details }}</p>
                @else
                    <form method="POST" action="{{ route('myhealth.lab.result.store', $labRequest->id) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group"><label>Result Date *</label><input type="date" name="result_date" value="{{ date('Y-m-d') }}" class="form-control" required></div>
                        <div class="form-group"><label>Result Summary</label><textarea name="result_summary" class="form-control"></textarea></div>
                        <div class="form-group"><label>Result Details</label><textarea name="result_details" class="form-control"></textarea></div>
                        <div class="form-group"><label>Result File</label><input type="file" name="file" class="form-control"></div>
                        <button class="btn btn-success">Save Result</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach

    {{ $labRequests->links() }}
</section>
@endsection
