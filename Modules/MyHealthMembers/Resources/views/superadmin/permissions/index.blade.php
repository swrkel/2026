@extends('layouts.app')
@section('title', 'My Health Business Permissions')

@section('content')
<section class="content-header">
    <h1>My Health Business Permissions</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Register</th>
                        <th>Profile</th>
                        <th>Medical</th>
                        <th>Diagnosis</th>
                        <th>Prescription</th>
                        <th>Expiry</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($businesses as $business)
                        @php $permission = $permissions[$business->id] ?? null; @endphp
                        <tr>
                            <td>{{ $business->name }}</td>
                            <td>{{ !empty($permission?->can_register_member) ? 'Yes' : 'No' }}</td>
                            <td>{{ !empty($permission?->can_view_profile) ? 'Yes' : 'No' }}</td>
                            <td>{{ !empty($permission?->can_view_medical_history) ? 'Yes' : 'No' }}</td>
                            <td>{{ !empty($permission?->can_create_diagnosis) ? 'Yes' : 'No' }}</td>
                            <td>{{ !empty($permission?->can_create_prescription) ? 'Yes' : 'No' }}</td>
                            <td>{{ $permission?->access_expiry_date }}</td>
                            <td>
                                <a href="{{ route('myhealth.superadmin.permissions.edit', $business->id) }}" class="btn btn-xs btn-primary">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
