@extends('beautysaloons::layout')
@section('beauty_content')
<div class="container-fluid bs-notifications">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>{{ __('beautysaloons::notifications.templates') }}</h3>
        <a href="{{ route('beautysaloons.notifications.templates.create') }}" class="btn btn-primary">Add Template</a>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Channel</th><th>Language</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($templates as $template)
                    <tr>
                        <td>{{ $template->code }}</td>
                        <td>{{ $template->name }}</td>
                        <td>{{ $template->category }}</td>
                        <td>{{ strtoupper($template->channel) }}</td>
                        <td>{{ $template->language }}</td>
                        <td>{{ $template->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No templates found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $templates->links() }}
</div>
@endsection
