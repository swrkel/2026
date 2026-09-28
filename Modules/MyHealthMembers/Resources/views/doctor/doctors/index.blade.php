@extends('layouts.app')
@section('title', 'My Health Doctors')

@section('content')
<section class="content-header"><h1>My Health Doctors</h1></section>
<section class="content">
    <a href="{{ route('myhealth.doctors.create') }}" class="btn btn-success mb-3">Add Doctor</a>
    <div class="box box-primary">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Code</th><th>Name</th><th>Registration No</th><th>Specialization</th><th>Mobile</th></tr></thead>
                <tbody>
                    @foreach($doctors as $doctor)
                        <tr>
                            <td>{{ $doctor->doctor_code }}</td>
                            <td>{{ $doctor->name }}</td>
                            <td>{{ $doctor->registration_no }}</td>
                            <td>{{ $doctor->specialization }}</td>
                            <td>{{ $doctor->mobile }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $doctors->links() }}
        </div>
    </div>
</section>
@endsection
