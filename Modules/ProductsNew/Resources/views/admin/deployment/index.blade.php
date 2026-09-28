@extends('productsnew::layouts.app')
@section('title', 'Products New Deployment Readiness')

@section('productsnew_content')
<section class="content-header productsnew-page-header">
    <h1>Products New <small>Deployment Readiness</small></h1>
</section>

<section class="content productsnew-content">
    <div class="row">
        <div class="col-md-4">
            <div class="productsnew-card">
                <h4>Tenant Status</h4>
                <table class="table table-condensed productsnew-table">
                    <tbody>
                    @foreach($status as $key => $value)
                        <tr>
                            <th>{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                            <td>{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-4">
            <div class="productsnew-card">
                <h4>Acceptance Checklist</h4>
                <ul class="productsnew-check-list">
                    @foreach($checklist as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="col-md-4">
            <div class="productsnew-card">
                <h4>Rollback Safety</h4>
                <ul class="productsnew-check-list">
                    @foreach($rollback_steps as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>
@endsection
