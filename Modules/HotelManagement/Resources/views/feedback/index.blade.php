@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Guest Feedback <small>Reviews, ratings and service recovery</small></h1>
</section>
<section class="content">
    @include('hotelmanagement::partials.nav')
    @if(session('status')) <div class="alert alert-success hm-alert">{{ session('status') }}</div> @endif

    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Feedback</div><div class="hm-kpi-value">{{ $feedback['total'] }}</div><div class="hm-kpi-sub">Latest records</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Average Rating</div><div class="hm-kpi-value">{{ number_format($feedback['average'], 2) }}</div><div class="hm-kpi-sub">Overall guest score</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Open</div><div class="hm-kpi-value">{{ $feedback['open'] }}</div><div class="hm-kpi-sub">Need review</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Resolved</div><div class="hm-kpi-value">{{ $feedback['resolved'] }}</div><div class="hm-kpi-sub">Service recovery done</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Create Feedback Question</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.feedback.question') }}">
                        @csrf
                        <div class="form-group"><label>Question</label><input name="question" class="form-control" required placeholder="How was your check-in experience?"></div>
                        <div class="hm-form-grid" style="grid-template-columns:repeat(3,minmax(120px,1fr))">
                            <div class="form-group"><label>Category</label><select name="category" class="form-control"><option value="general">General</option><option value="room">Room</option><option value="service">Service</option><option value="food">Food</option><option value="cleanliness">Cleanliness</option></select></div>
                            <div class="form-group"><label>Scale</label><input type="number" name="rating_scale" class="form-control" value="5" min="1" max="10"></div>
                            <div class="form-group"><label>Order</label><input type="number" name="display_order" class="form-control" value="0" min="0"></div>
                        </div>
                        <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Question</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Record Guest Feedback</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.feedback.store') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(4,minmax(120px,1fr))">
                            <div class="form-group"><label>Guest ID</label><input type="number" name="guest_id" class="form-control"></div>
                            <div class="form-group"><label>Reservation ID</label><input type="number" name="reservation_id" class="form-control"></div>
                            <div class="form-group"><label>Folio ID</label><input type="number" name="folio_id" class="form-control"></div>
                            <div class="form-group"><label>Date</label><input type="date" name="feedback_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                            <div class="form-group"><label>Source</label><select name="source" class="form-control"><option value="front_desk">Front Desk</option><option value="qr">QR</option><option value="email">Email</option><option value="phone">Phone</option></select></div>
                            <div class="form-group"><label>Overall</label><input type="number" step="0.01" name="overall_rating" class="form-control" required min="0" max="10"></div>
                            <div class="form-group"><label>Room</label><input type="number" step="0.01" name="room_rating" class="form-control" min="0" max="10"></div>
                            <div class="form-group"><label>Service</label><input type="number" step="0.01" name="service_rating" class="form-control" min="0" max="10"></div>
                            <div class="form-group"><label>Food</label><input type="number" step="0.01" name="food_rating" class="form-control" min="0" max="10"></div>
                            <div class="form-group"><label>Cleanliness</label><input type="number" step="0.01" name="cleanliness_rating" class="form-control" min="0" max="10"></div>
                        </div>
                        <div class="form-group"><label>Comments</label><textarea name="comments" class="form-control" rows="3" placeholder="Guest comments / issue details"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-star"></i> Record Feedback</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Feedback Register</h3></div>
        <div class="box-body">
            <div class="hm-toolbar"><input class="form-control hm-search-input" placeholder="Search feedback" style="max-width:260px"><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div>
            <div class="table-responsive"><table class="table hm-table"><thead><tr><th>ID</th><th>Date</th><th>Guest</th><th>Overall</th><th>Room</th><th>Service</th><th>Food</th><th>Cleanliness</th><th>Status</th><th>Comments</th><th>Action</th></tr></thead><tbody>
                @forelse($feedback['feedback'] as $row)
                    <tr>
                        <td>{{ $row->id ?? '' }}</td><td>{{ $row->feedback_date ?? '' }}</td><td>{{ $row->guest_id ?? '-' }}</td><td>{{ $row->overall_rating ?? '0' }}</td><td>{{ $row->room_rating ?? '-' }}</td><td>{{ $row->service_rating ?? '-' }}</td><td>{{ $row->food_rating ?? '-' }}</td><td>{{ $row->cleanliness_rating ?? '-' }}</td><td><span class="hm-badge {{ $row->status ?? '' }}">{{ $row->status ?? '' }}</span></td><td>{{ \Illuminate\Support\Str::limit($row->comments ?? '', 60) }}</td>
                        <td>
                            <form method="POST" action="{{ route('hotel-management.feedback.status', $row->id ?? 0) }}" class="form-inline">
                                @csrf
                                <select name="status" class="form-control input-sm"><option value="reviewed">Reviewed</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select>
                                <input name="resolution_note" class="form-control input-sm" placeholder="Note" style="width:120px">
                                <button class="btn btn-xs hm-btn-add">Update</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11"><div class="hm-empty">No guest feedback found yet.</div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Active Questions</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Order</th><th>Category</th><th>Question</th><th>Scale</th><th>Active</th></tr></thead><tbody>@forelse($feedback['questions'] as $q)<tr><td>{{ $q->display_order ?? 0 }}</td><td>{{ $q->category ?? '' }}</td><td>{{ $q->question ?? '' }}</td><td>{{ $q->rating_scale ?? 5 }}</td><td>{{ !empty($q->is_active) ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="5"><div class="hm-empty">No feedback questions configured yet.</div></td></tr>@endforelse</tbody></table></div><ul>@foreach($feedback['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div></div>
</section>
@endsection
