@extends('layouts.app')
@section('title', 'My Health Notification Templates')

@section('content')
<section class="content-header"><h1>Notification Templates</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Templates</h3><div class="box-tools"><a href="{{ route('myhealth.notifications.templates.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Template</a></div></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped"><thead><tr><th>Code</th><th>Name</th><th>Channel</th><th>Status</th><th>Action</th></tr></thead><tbody>
                @forelse($templates as $template)
                    <tr><td>{{ $template->code }}</td><td>{{ $template->name }}</td><td>{{ strtoupper($template->channel) }}</td><td>{{ $template->is_active ? 'Active' : 'Inactive' }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('myhealth.notifications.templates.edit', $template) }}">Edit</a></td></tr>
                @empty
                    <tr><td colspan="5" class="text-center">No templates found.</td></tr>
                @endforelse
            </tbody></table>
            {{ $templates->links() }}
        </div>
    </div>
</section>
@endsection
