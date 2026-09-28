@extends('layouts.app')
@section('title', 'Consultations')

@section('content')
<section class="content-header"><h1>Consultations - {{ $member->name }}</h1></section>
<section class="content">
    <a href="{{ route('myhealth.consultations.create', $member->id) }}" class="btn btn-success mb-3">Add Consultation</a>
    <div class="box box-primary">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>No</th><th>Date</th><th>Type</th><th>Status</th><th>Complaint</th><th>Summary</th></tr></thead>
                <tbody>
                    @foreach($consultations as $consultation)
                        <tr>
                            <td>{{ $consultation->consultation_no }}</td>
                            <td>{{ $consultation->consultation_date }}</td>
                            <td>{{ $consultation->visit_type }}</td>
                            <td>{{ $consultation->status }}</td>
                            <td>{{ $consultation->chief_complaint }}</td>
                            <td>{{ $consultation->summary }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $consultations->links() }}
        </div>
    </div>
</section>
@endsection
