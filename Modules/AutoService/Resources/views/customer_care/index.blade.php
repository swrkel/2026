@extends('autoservice::layouts.master')
@section('title','Customer Care & Warranty')
@section('autoservice_content')
<div class="row">
    <div class="col-md-3"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $stats['feedback_count'] }}</h3><p>Feedback Records</p></div></div></div>
    <div class="col-md-3"><div class="box box-solid"><div class="box-body text-center"><h3>{{ number_format($stats['avg_service_rating'],2) }}</h3><p>Average Service Rating</p></div></div></div>
    <div class="col-md-3"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $stats['pending_reminders'] }}</h3><p>Pending Reminders</p></div></div></div>
    <div class="col-md-3"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $stats['open_warranty_claims'] }}</h3><p>Open Warranty Claims</p></div></div></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Create Service Reminder</h3></div>
            <form method="post" action="{{ route('autoservice.customer_care.reminders.store') }}">
                @csrf
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6"><label>Vehicle ID</label><input name="vehicle_id" class="form-control" required></div>
                        <div class="col-md-6"><label>Job ID</label><input name="job_id" class="form-control"></div>
                    </div>
                    <div class="row" style="margin-top:10px;">
                        <div class="col-md-6"><label>Due Date</label><input type="date" name="due_date" class="form-control" required></div>
                        <div class="col-md-6"><label>Days Before</label><input type="number" name="days_before" class="form-control" value="7"></div>
                    </div>
                    <div class="row" style="margin-top:10px;">
                        <div class="col-md-6"><label>Channel</label><select name="channel" class="form-control"><option value="sms">SMS</option><option value="whatsapp">WhatsApp</option><option value="email">Email</option><option value="call">Call</option></select></div>
                        <div class="col-md-6"><label>Mobile</label><input name="mobile" class="form-control"></div>
                    </div>
                    <label style="margin-top:10px;">Message</label><textarea name="message" class="form-control" rows="2"></textarea>
                </div>
                <div class="box-footer"><button class="btn btn-primary">Create Reminder</button></div>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border"><h3 class="box-title">Open Warranty Claim</h3></div>
            <form method="post" action="{{ route('autoservice.customer_care.warranty.store') }}">
                @csrf
                <div class="box-body">
                    <label>Job ID</label><input name="job_id" class="form-control" required>
                    <label style="margin-top:10px;">Claim Type</label><select name="claim_type" class="form-control"><option value="service_warranty">Service Warranty</option><option value="parts_warranty">Parts Warranty</option><option value="labour_rework">Labour Rework</option><option value="goodwill">Goodwill</option></select>
                    <label style="margin-top:10px;">Customer Complaint</label><textarea name="customer_complaint" class="form-control" rows="2"></textarea>
                    <label style="margin-top:10px;">Internal Note</label><textarea name="internal_note" class="form-control" rows="2"></textarea>
                </div>
                <div class="box-footer"><button class="btn btn-warning">Open Claim</button></div>
            </form>
        </div>
    </div>
</div>

<div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">Pending Service Reminders</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Send On</th><th>Due Date</th><th>Vehicle</th><th>Channel</th><th>Mobile</th><th>Message</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($reminders as $r)
                <tr><td>{{ $r->send_on }}</td><td>{{ $r->due_date }}</td><td>{{ $r->registration_no }} {{ $r->make }} {{ $r->model }}</td><td>{{ strtoupper($r->channel) }}</td><td>{{ $r->mobile }}</td><td>{{ $r->message }}</td><td><span class="label label-info">{{ $r->status }}</span></td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No pending reminders.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">Warranty Claims</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Claim No</th><th>Job</th><th>Vehicle</th><th>Type</th><th>Status</th><th>Complaint</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($warrantyClaims as $w)
                <tr>
                    <td>{{ $w->claim_no }}</td><td>{{ $w->job_no }}</td><td>{{ $w->registration_no }}</td><td>{{ str_replace('_',' ',ucfirst($w->claim_type)) }}</td><td><span class="label label-warning">{{ $w->status }}</span></td><td>{{ $w->customer_complaint }}</td>
                    <td>
                        <form method="post" action="{{ route('autoservice.customer_care.warranty.status', $w->id) }}" class="form-inline">
                            @csrf
                            <select name="status" class="form-control input-sm"><option value="in_review">In Review</option><option value="approved">Approved</option><option value="rejected">Rejected</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select>
                            <button class="btn btn-xs btn-primary">Update</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No warranty claims.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">Recent Customer Feedback</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Date</th><th>Customer</th><th>Mobile</th><th>Service</th><th>Mechanic</th><th>Workshop</th><th>Comments</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($feedback as $f)
                <tr><td>{{ $f->submitted_at }}</td><td>{{ $f->customer_name }}</td><td>{{ $f->customer_mobile }}</td><td>{{ $f->service_rating }}</td><td>{{ $f->mechanic_rating }}</td><td>{{ $f->workshop_rating }}</td><td>{{ $f->comments }}</td><td>{{ $f->review_status ?? 'open' }}</td></tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">No customer feedback yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
