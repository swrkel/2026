@extends('beautysaloons::layout')

@section('beauty_content')
<div class="container-fluid bs-release-page">
    <h3>Beauty Saloons {{ $version }}</h3>
    <p class="text-muted">Production release candidate checklist and deployment readiness.</p>

    <div class="card">
        <div class="card-header"><strong>Readiness Checklist</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Item</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($readinessRows as $row)
                        <tr>
                            <td>{{ $row['item'] }}</td>
                            <td><span class="badge {{ $row['status'] === 'Pass' ? 'badge-success' : 'badge-warning' }}">{{ $row['status'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
