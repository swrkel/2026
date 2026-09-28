@extends('ezylaw::layouts.module')
@section('ezylaw_title','Chronology - '.$matter->matter_no)
@section('ezylaw_content')
<div class="law-subnav"><a class="btn btn-default" href="{{ route('ezylaw.matters.show',$matter) }}">Back to Matter</a><a class="btn btn-default" href="{{ route('ezylaw.parties.index',$matter) }}">Parties / Counsel</a></div>
<div class="row"><div class="col-md-4"><div class="box box-primary"><div class="box-header"><h3 class="box-title">Add Chronology Entry</h3></div><form method="post" action="{{ route('ezylaw.chronology.store',$matter) }}"><div class="box-body">@csrf
<label>Date & Time</label><input type="datetime-local" class="form-control" name="event_at" value="{{ now()->format('Y-m-d\TH:i') }}" required>
<label>Event Type</label><select class="form-control" name="event_type"><option value="general">General</option><option value="filing">Filing</option><option value="hearing">Hearing</option><option value="correspondence">Correspondence</option><option value="evidence">Evidence</option><option value="order">Order / Judgment</option><option value="deadline">Deadline</option></select>
<label>Title</label><input class="form-control" name="title" required>
<label>Description</label><textarea class="form-control" name="description" rows="5"></textarea>
<label class="checkbox-inline"><input type="checkbox" name="is_key_event" value="1"> Key Event</label>
</div><div class="box-footer"><button class="btn btn-primary">Add Entry</button></div></form></div></div>
<div class="col-md-8"><div class="box"><div class="box-header"><h3 class="box-title">Case Chronology</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date/Time</th><th>Type</th><th>Event</th><th>Key</th><th>Action</th></tr></thead><tbody>@forelse($entries as $e)<tr><td>{{ optional($e->event_at)->format('Y-m-d H:i') }}</td><td>{{ ucfirst(str_replace('_',' ',$e->event_type)) }}</td><td><b>{{ $e->title }}</b><br><small>{{ $e->description }}</small></td><td>{{ $e->is_key_event?'Yes':'No' }}</td><td><form method="post" action="{{ route('ezylaw.chronology.destroy',[$matter,$e]) }}">@csrf @method('DELETE')<button class="btn btn-xs btn-danger" onclick="return confirm('Remove this entry?')">Delete</button></form></td></tr>@empty<tr><td colspan="5">No chronology entries yet.</td></tr>@endforelse</tbody></table>{{ $entries->links() }}</div></div></div></div>
@endsection
