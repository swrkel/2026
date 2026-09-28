@extends('beautysaloons::portal.layout')
@section('portal_title', 'My Appointments')
@section('portal_content')
<a href="{{ route('beautysaloons.portal.appointments.create') }}" class="btn btn-success mb-3">Book Appointment</a>
<table class="table table-bordered table-striped">
    <thead><tr><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    @forelse($appointments as $appointment)
        <tr>
            <td>{{ $appointment->appointment_date ?? '' }}</td>
            <td>{{ $appointment->start_time ?? '' }}</td>
            <td>{{ ucfirst($appointment->status ?? '') }}</td>
            <td>
                <form method="POST" action="{{ route('beautysaloons.portal.appointments.cancel', $appointment) }}">
                    @csrf
                    <button class="btn btn-sm btn-danger" type="submit">Cancel</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="4" class="text-center">No appointments found.</td></tr>
    @endforelse
    </tbody>
</table>
{{ method_exists($appointments, 'links') ? $appointments->links() : '' }}
@endsection
