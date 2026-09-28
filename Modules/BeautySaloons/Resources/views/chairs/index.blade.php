@php($title = $title ?? 'Beauty Saloons')
<section class="content-header"><h1>{{ $title }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<div class="box box-primary">
 <div class="box-header with-border"><h3 class="box-title">Chairs</h3><a href="{{ route('beautysaloons.chairs.create') }}" class="btn btn-primary pull-right">Add Chairs</a></div>
 <div class="box-body table-responsive">
  <table class="table table-bordered table-striped"><thead><tr><th>chair_code</th><th>chair_name</th><th>chair_type</th><th>floor_name</th><th>status</th><th>Action</th></tr></thead><tbody>
  @forelse($records as $record)<tr><td>{{ $record->chair_code ?? '' }}</td><td>{{ $record->chair_name ?? '' }}</td><td>{{ $record->chair_type ?? '' }}</td><td>{{ $record->floor_name ?? '' }}</td><td>{{ $record->status ?? '' }}</td><td><a href="{{ route('beautysaloons.chairs.edit', $record->id) }}" class="btn btn-xs btn-info">Edit</a></td></tr>@empty<tr><td colspan="6">No records found</td></tr>@endforelse
  </tbody></table>{{ $records->links() }}
 </div>
</div>
</section>

