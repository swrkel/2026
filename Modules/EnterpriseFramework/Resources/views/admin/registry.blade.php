@extends('enterpriseframework::layout', ['title' => 'Enterprise Report Registry'])

@section('efw_content')
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Registered Reports</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Module</th><th>Category</th><th>Report</th><th>Route</th><th>Branch</th><th>Consolidated</th><th>Export</th></tr></thead>
            <tbody>
                @foreach($reports as $report)
                    <tr>
                        <td>{{ $report['module'] }}</td><td>{{ $report['category'] }}</td><td>{{ $report['name'] }}</td><td>{{ $report['route'] }}</td>
                        <td>{{ !empty($report['supports_branch']) ? 'Yes' : 'No' }}</td><td>{{ !empty($report['supports_consolidated']) ? 'Yes' : 'No' }}</td><td>{{ !empty($report['supports_export']) ? 'Yes' : 'No' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
