@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::lang.customer_360'))
@section('leadsnew_content')
<section class="content-header">
    <h1>@lang('leadsnew::lang.customer_360') - {{ $lead->name ?? $lead->lead_no ?? '' }}</h1>
</section>
<section class="content leads-new-page">
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">@lang('leadsnew::lang.timeline')</h3></div>
        <div class="box-body">
            <ul class="timeline">
                @forelse($timeline as $item)
                    <li>
                        <i class="fa fa-clock-o bg-blue"></i>
                        <div class="timeline-item">
                            <span class="time">{{ $item['created_at'] }}</span>
                            <h3 class="timeline-header">{{ $item['title'] }}</h3>
                            <div class="timeline-body">{{ $item['details'] }}</div>
                        </div>
                    </li>
                @empty
                    <li>@lang('leadsnew::lang.no_timeline_records')</li>
                @endforelse
            </ul>
        </div>
    </div>
</section>
@endsection
