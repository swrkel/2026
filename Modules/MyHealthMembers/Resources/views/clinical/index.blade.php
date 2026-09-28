@extends('layouts.app')
@section('title', 'Clinical Decision Support')

@section('content')
<section class="content-header">
    <h1>Clinical Decision Support <small>{{ $member->name ?? $member->member_name ?? 'Member' }}</small></h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">Active Clinical Alerts</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Severity</th>
                                <th>Alert Type</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alerts as $alert)
                                <tr>
                                    <td><span class="label label-{{ in_array($alert->severity, ['critical','high']) ? 'danger' : ($alert->severity == 'medium' ? 'warning' : 'info') }}">{{ ucfirst($alert->severity) }}</span></td>
                                    <td>{{ ucwords(str_replace('_', ' ', $alert->alert_type)) }}</td>
                                    <td>{{ $alert->title }}</td>
                                    <td>{{ $alert->message }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('myhealth.clinical.alerts.acknowledge', $alert->id) }}">
                                            @csrf
                                            <button class="btn btn-xs btn-success" type="submit">Acknowledge</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No active alerts found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">Allergies</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('myhealth.clinical.allergies.store', $member->id) }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group"><label>Allergy Name *</label><input type="text" name="allergy_name" class="form-control" required></div>
                            <div class="col-md-6 form-group"><label>Severity</label><select name="severity" class="form-control"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
                            <div class="col-md-6 form-group"><label>Type</label><input type="text" name="allergy_type" class="form-control" placeholder="Drug / Food / Other"></div>
                            <div class="col-md-6 form-group"><label>Reaction</label><input type="text" name="reaction" class="form-control"></div>
                            <div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                        </div>
                        <button class="btn btn-primary">Add Allergy</button>
                    </form>
                    <hr>
                    <ul class="list-group">
                        @forelse($allergies as $allergy)
                            <li class="list-group-item"><strong>{{ $allergy->allergy_name }}</strong> <span class="label label-warning">{{ ucfirst($allergy->severity) }}</span><br><small>{{ $allergy->reaction }} {{ $allergy->notes }}</small></li>
                        @empty
                            <li class="list-group-item text-muted">No allergies recorded.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">Chronic Conditions</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('myhealth.clinical.conditions.store', $member->id) }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group"><label>Condition *</label><input type="text" name="condition_name" class="form-control" required></div>
                            <div class="col-md-6 form-group"><label>Diagnosed Date</label><input type="date" name="diagnosed_date" class="form-control"></div>
                            <div class="col-md-6 form-group"><label>Severity</label><select name="severity" class="form-control"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
                            <div class="col-md-6 form-group"><label>Status</label><input type="text" name="status" class="form-control" placeholder="Active / Controlled"></div>
                            <div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                        </div>
                        <button class="btn btn-primary">Add Condition</button>
                    </form>
                    <hr>
                    <ul class="list-group">
                        @forelse($conditions as $condition)
                            <li class="list-group-item"><strong>{{ $condition->condition_name }}</strong> <span class="label label-warning">{{ ucfirst($condition->severity) }}</span><br><small>{{ $condition->status }} {{ $condition->notes }}</small></li>
                        @empty
                            <li class="list-group-item text-muted">No chronic conditions recorded.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
