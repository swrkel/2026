@extends('layouts.app')
@section('title', __('hms::lang.hms'))
@section('content')
 
    <section class="content no-print">
        <div class="row">
            <div class="col-md-4">
                {{-- <div class="box box-solid">
                <div class="box-body p-10">
                    <table class="table no-margin">
                        <tr>
                            <th style="font-size: 20px !important">@lang('hms::lang.rooms_booked_today')</th>
                            <td class="!text-[20px]">{{ $room_count->booked_rooms ?? 0 }}</td>
                        </tr>
                        <tr>
                            <th>@lang('hms::lang.pending_rooms_today')</th>
                            <td>{{ $room_count->pending_rooms ?? 0 }}</td>
                        </tr>
                        <tr>
                            <th>@lang('hms::lang.available_rooms_today')</th>
                            <td>{{ $room_count->unbooked_rooms ?? 0 }}</td>
                        </tr>
                    </table>
                </div>
            </div> --}}

                @component('components.widget')
                    <table class="table no-margin">
                        <tr>
                            <th class="font-size-20">@lang('hms::lang.rooms_booked_today')</th>
                            <td class="font-size-20">{{ $room_count->booked_rooms ?? 0 }}</td>
                        </tr>
                        <tr>
                            <th class="font-size-20">@lang('hms::lang.pending_rooms_today')</th>
                            <td class="font-size-20">{{ $room_count->pending_rooms ?? 0 }}</td>
                        </tr>
                        <tr>
                            <th class="font-size-20">@lang('hms::lang.available_rooms_today')</th>
                            <td class="font-size-20">{{ $room_count->unbooked_rooms ?? 0 }}</td>
                        </tr>
                    </table>
                @endcomponent

                @component('components.widget', ['title' => __('hms::lang.available_rooms_by_type')])
                    <table class="table no-margin">
                        @foreach ($unbooked_rooms_by_type as $types)
                            <tr>
                                <th>{{ $types->room_type }}</th>
                                <td>{{ $types->unbooked_count ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endcomponent
                @component('components.widget', ['title' => __('hms::lang.guests')])
                    <table class="table no-margin">
                        <tr>
                            <th class="font-size-20">@lang('hms::lang.staying_tonight')</th>
                            <td class="font-size-20">{{ $guest_count_tonight->sum('adult_guests') + $guest_count_tonight->sum('child_guests') }}</td>
                        </tr>
                        <tr>
                            <td class="font-size-20">@lang('hms::lang.adults')</td>
                            <td class="font-size-20">{{ $guest_count_tonight->sum('adult_guests') ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td class="font-size-20">@lang('hms::lang.childrens')</td>
                            <td class="font-size-20">{{ $guest_count_tonight->sum('child_guests') ?? 0 }}</td>
                        </tr>
                    </table>
                    <table class="table no-margin">
                        <tr>
                            <th class="font-size-20">@lang('hms::lang.arriving_today')</th>
                            <td class="font-size-20">{{ $arrive_today->sum('adult_guests') + $arrive_today->sum('child_guests') }}</td>
                        </tr>
                        <tr>
                            <td class="font-size-20">@lang('hms::lang.adults')</td>
                            <td class="font-size-20">{{ $arrive_today->sum('adult_guests') ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td class="font-size-20">@lang('hms::lang.childrens')</td>
                            <td class="font-size-20">{{ $arrive_today->sum('child_guests') ?? 0 }}</td>
                        </tr>
                    </table>
                    <table class="table no-margin">
                        <tr>
                            <th class="font-size-20">@lang('hms::lang.leaving_today')</th>
                            <td class="font-size-20">{{ $leave_today->sum('adult_guests') + $leave_today->sum('child_guests') }}</td>
                        </tr>
                        <tr>
                            <td class="font-size-20">@lang('hms::lang.adults')</td>
                            <td class="font-size-20">{{ $leave_today->sum('adult_guests') ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td class="font-size-20">@lang('hms::lang.childrens')</td>
                            <td class="font-size-20">{{ $leave_today->sum('child_guests') ?? 0 }}</td>
                        </tr>
                    </table>
                @endcomponent
            </div>
            <div class="col-md-4">

                @component('components.widget')
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active text-success">
                                <a href="#cn_1" class="text-success" data-toggle="tab" aria-expanded="true" >
                                    @lang('hms::lang.arrivals')
                                </a>
                            </li>
                            <li>
                                <a href="#cn_2" class="text-danger" data-toggle="tab" aria-expanded="true">
                                    @lang('hms::lang.departures')
                                </a>
                            </li>
                            <li>
                                <a href="#cn_3" class="text-info" data-toggle="tab" aria-expanded="true">
                                    @lang('hms::lang.latest')
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="cn_1">
                                @forelse ($today_arrivales as $info)
                                    @include('hms::dashboard.partial.booking_info')
                                @empty
                                    @lang('hms::lang.no_arrivals_today')
                                @endforelse
                            </div>
                            <div class="tab-pane" id="cn_2">
                                @forelse ($today_departure as $info)
                                    @include('hms::dashboard.partial.booking_info')
                                @empty
                                    @lang('hms::lang.no_departures_today')
                                @endforelse
                            </div>
                            <div class="tab-pane" id="cn_3">
                                @forelse ($latest_bookig as $info)
                                    @include('hms::dashboard.partial.booking_info')
                                @empty
                                    @lang('hms::lang.no_latest')
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>

            <div class="col-md-4">

                @component('components.widget')
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="#chat_1" data-toggle="tab" class="text-success" aria-expanded="true">
                                    @lang('hms::lang.upcoming_bookings')
                                </a>
                            </li>
                            <li>
                                <a href="#chat_2" class="text-danger" data-toggle="tab" aria-expanded="true">
                                    @lang('hms::lang.past_bookings')
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="chat_1">
                                {!! $booking_chart->container() !!}
                            </div>
                            <div class="tab-pane" id="chat_2">
                                {!! $past_booking_chart->container() !!}
                            </div>
                        </div>
                    </div>
                @endcomponent


            </div>
        </div>
        </div>
    @endsection

    @section('javascript')
        {!! $booking_chart->script() !!}
        {!! $past_booking_chart->script() !!}
    @endsection
