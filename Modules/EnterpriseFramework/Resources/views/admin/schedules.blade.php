@extends('enterpriseframework::layout', ['title' => 'Scheduled Reports'])

@section('efw_content')
<div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered"><thead><tr><th>Name</th><th>Frequency</th><th>Format</th><th>Status</th></tr></thead><tbody>@foreach($schedules as $schedule)<tr><td>{{ $schedule['name'] }}</td><td>{{ $schedule['frequency'] }}</td><td>{{ $schedule['format'] }}</td><td>{{ $schedule['status'] }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
