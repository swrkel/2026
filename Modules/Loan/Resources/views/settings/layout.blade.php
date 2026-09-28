@extends('layouts.app')
@section('title')
    @yield('tab-title') {{ __('messages.settings') }}
@endsection

@section('content')

    
    {{-- Content Header (Page header) --}}
    <section class="content-header">
        <h1>
            {{ trans_choice('loan::general.loan', 1) }} {{ __('messages.settings') }}
            <small>@yield('tab-title') @yield('tooltip')</small>
        </h1>
    </section>

    {{-- Main content --}}
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                {{-- <pos-tab-container> --}}
                <div class="col-xs-12 pos-tab-container">
                    <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 pos-tab-menu">
                        {{-- <div class="list-group"> --}}
                        <div class="list-group-link">
                            @php
                                $nav_items = [
                                    'loan/collateral_type' => [
                                        'label' => trans_choice('loan::general.collateral', 1) . ' ' . trans_choice('loan::general.type', 2),
                                        'tooltip' => __('loan::lang.tooltip_loancollateraltypessettings'),
                                    ],
                                    'loan/purpose' => [
                                        'label' => trans_choice('loan::general.loan', 1) . ' ' . trans_choice('loan::general.purpose', 2),
                                        'tooltip' => __('loan::lang.tooltip_loanpurposessettings'),
                                    ],
                                    'loan/status' => [
                                        'label' => trans_choice('loan::general.loan', 1) . ' ' . trans_choice('loan::general.status', 2),
                                    ],
                                    'loan/charge' => [
                                        'label' => trans_choice('loan::general.loan', 1) . ' ' . trans_choice('loan::general.fee', 2),
                                        'tooltip' => __('loan::lang.tooltip_loan_charge'),
                                    ],
                                ];
                                
                                if (!function_exists('isActiveTab')) {
                                    function isActiveTab($url)
                                    {
                                        $first_two_segments = request()->segment(1) . '/' . request()->segment(2);
                                        return $first_two_segments == $url;
                                    }
                                }
                            @endphp

                            {{-- Loop through the nav items to print the side nav --}}
                            @foreach ($nav_items as $url => $nav_item)
                                <a href="{{ url($url) }}" class="list-group-item text-center @if (isActiveTab($url)) active @endif">
                                    {{ $nav_item['label'] }}
                                    @if (array_key_exists('tooltip', $nav_item))
                                        @show_tooltip($nav_item['tooltip'])
                                    @endif
                                </a>
                            @endforeach
                        </div>

                    </div>
                    <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
                        @yield('tab-content')
                    </div>
                </div>
                {{-- </pos-tab-container> --}}
            </div>
        </div>
    </section>
    {{-- /.content --}}
@stop
@section('javascript')
    @yield('tab-javascript')
    <script>
         $(document).ready( function(){
            $('.page-container').removeClass('sbar_collapsed');
        });
    </script>
@endsection
