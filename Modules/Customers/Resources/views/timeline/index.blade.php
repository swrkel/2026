@extends('layouts.app')
@section('title', __('customers::lang.customer_timeline'))
@section('content')
<section class="content-header"><h1>@lang('customers::lang.customer_timeline') <small>{{ $customer->name }}</small></h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border clearfix">
            <h3 class="box-title"><i class="fa fa-clock-o"></i> @lang('customers::lang.timeline')</h3>
            <div class="pull-right"><a href="{{ route('customers.show', $customer->id) }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> @lang('messages.back')</a></div>
        </div>
        <div class="box-body">
            <ul class="timeline">
                @forelse($items as $item)
                    <li>
                        <i class="fa {{ $item['type'] == 'document' ? 'fa-file' : ($item['type'] == 'attachment' ? 'fa-paperclip' : ($item['type'] == 'audit' ? 'fa-shield' : 'fa-clock-o')) }} bg-blue"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fa fa-clock-o"></i> {{ !empty($item['date']) ? date('Y-m-d H:i', strtotime($item['date'])) : '' }}</span>
                            <h3 class="timeline-header"><strong>{{ strtoupper($item['type']) }}</strong> - {{ $item['title'] }}</h3>
                            <div class="timeline-body" style="white-space:pre-wrap;">{{ $item['description'] }}</div>
                        </div>
                    </li>
                @empty
                    <li><i class="fa fa-info bg-gray"></i><div class="timeline-item"><div class="timeline-body text-muted">@lang('customers::lang.no_records_found')</div></div></li>
                @endforelse
            </ul>
        </div>
    </div>
</section>
@endsection
