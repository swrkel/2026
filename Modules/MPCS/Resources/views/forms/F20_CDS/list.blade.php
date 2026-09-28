@extends('layouts.app')
@section('title', 'List F 20 Form – CDS')
@section('content')
<section class="content-header">
    <h1>List F 20 Form – CDS</h1>
</section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <a href="{{ action('\\Modules\\MPCS\\Http\\Controllers\\F20CDSFormController@index') }}" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-plus"></i> Add F 20 CDS
            </a>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Form No</th>
                        <th>Date</th>
                        <th>Society / Branch</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($forms as $form)
                        <tr>
                            <td>
                                <a class="btn btn-xs btn-primary" href="{{ action('\\Modules\\MPCS\\Http\\Controllers\\F20CDSFormController@print', [$form->id]) }}">
                                    <i class="fa fa-print"></i> View / Print
                                </a>
                            </td>
                            <td>{{ $form->form_no }}</td>
                            <td>{{ $form->form_date }}</td>
                            <td>{{ $form->society_name }}</td>
                            <td>{{ ucfirst($form->status) }}</td>
                            <td>{{ $form->created_at }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No F 20 CDS records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if(method_exists($forms, 'links'))
                {{ $forms->links() }}
            @endif
        </div>
    </div>
</section>
@endsection
