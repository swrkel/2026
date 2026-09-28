@php($title = $title ?? 'Beauty Saloons')
<section class="content-header"><h1>{{ $title }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<div class="box box-primary">
 <div class="box-header with-border"><h3 class="box-title">Resources</h3><a href="{{ route('beautysaloons.resources.create') }}" class="btn btn-primary pull-right">Add Resources</a></div>
 <div class="box-body table-responsive">
  <table class="table table-bordered table-striped"><thead><tr><th>resource_code</th><th>resource_name</th><th>resource_type</th><th>capacity</th><th>status</th><th>Action</th></tr></thead><tbody>
  @forelse($records as $record)<tr><td>{{ $record->resource_code ?? '' }}</td><td>{{ $record->resource_name ?? '' }}</td><td>{{ $record->resource_type ?? '' }}</td><td>{{ $record->capacity ?? '' }}</td><td>{{ $record->status ?? '' }}</td><td><a href="{{ route('beautysaloons.resources.edit', $record->id) }}" class="btn btn-xs btn-info">Edit</a></td></tr>@empty<tr><td colspan="6">No records found</td></tr>@endforelse
  </tbody></table>{{ $records->links() }}
 </div>
</div>
</section>

