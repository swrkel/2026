@php($title = $title ?? 'Beauty Saloons')
<section class="content-header"><h1>{{ $title }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<div class="box box-primary">
 <div class="box-header with-border"><h3 class="box-title">Branches</h3><a href="{{ route('beautysaloons.branches.create') }}" class="btn btn-primary pull-right">Add Branches</a></div>
 <div class="box-body table-responsive">
  <table class="table table-bordered table-striped"><thead><tr><th>branch_code</th><th>name</th><th>manager_name</th><th>phone</th><th>status</th><th>Action</th></tr></thead><tbody>
  @forelse($records as $record)<tr><td>{{ $record->branch_code ?? '' }}</td><td>{{ $record->name ?? '' }}</td><td>{{ $record->manager_name ?? '' }}</td><td>{{ $record->phone ?? '' }}</td><td>{{ $record->status ?? '' }}</td><td><a href="{{ route('beautysaloons.branches.edit', $record->id) }}" class="btn btn-xs btn-info">Edit</a></td></tr>@empty<tr><td colspan="6">No records found</td></tr>@endforelse
  </tbody></table>{{ $records->links() }}
 </div>
</div>
</section>

