@extends('layouts.app')
@section('title', __('distributionnew::lang.installation_checklist'))

@section('content')
<section class="content-header disnew-pos-header">
    <h1>{{ __('distributionnew::lang.installation_checklist') }}</h1>
</section>
<section class="content disnew-pos-page">
    <div class="box disnew-pos-card">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped disnew-datatable">
                <thead>
                    <tr>
                        <th>{{ __('distributionnew::lang.step') }}</th>
                        <th>{{ __('distributionnew::lang.status') }}</th>
                        <th>{{ __('distributionnew::lang.notes') }}</th>
                        <th>{{ __('distributionnew::lang.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($steps as $step)
                        <tr>
                            <td>{{ $step->step_title }}</td>
                            <td><span class="label label-{{ $step->status == 'completed' ? 'success' : 'warning' }}">{{ ucfirst($step->status) }}</span></td>
                            <td>{{ $step->notes }}</td>
                            <td>
                                @if($step->status !== 'completed')
                                    {!! Form::open(['route' => ['distributionnew.install.complete', $step->id], 'method' => 'post']) !!}
                                    <button type="submit" class="btn btn-primary btn-xs">{{ __('distributionnew::lang.mark_completed') }}</button>
                                    {!! Form::close() !!}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
