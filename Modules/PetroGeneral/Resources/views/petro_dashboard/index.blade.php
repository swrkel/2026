@extends('layouts.app')
@section('title', __('petrogeneral::lang.petro_dashboard'))

@section('css')
<style>
    /* Petro General / Petro Dashboard - self-contained tank status gauges. */
    .pgd-page {
        padding-top: 8px;
        background: #fff;
        border-radius: 8px;
    }

    .pgd-message {
        margin: 0 0 18px;
        padding-top: 10px;
        text-align: center;
    }

    .pgd-tank-grid {
        display: flex;
        flex-wrap: wrap;
    }

    .pgd-tank-column {
        position: relative;
        min-height: 335px;
        margin-bottom: 20px;
        padding-top: 12px;
    }

    .pgd-tank-divider {
        width: 50%;
        height: 1px;
        margin: 0 auto 10px;
        background: #edf0f2;
    }

    .pgd-gauge-wrap {
        width: 100%;
        min-height: 180px;
        display: flex;
        align-items: flex-start;
        justify-content: center;
    }

    .pgd-gauge-svg {
        display: block;
        width: 100%;
        max-width: 198px;
        height: auto;
        overflow: visible;
    }

    .pgd-tank-name {
        margin: 1px 0 8px;
        color: #1f2937;
        font-size: 21px;
        font-weight: 500;
        line-height: 1.25;
    }

    .pgd-tank-detail {
        margin: 6px 0;
        color: #374151;
        font-size: 17px;
        font-weight: 400;
        line-height: 1.35;
    }

    .pgd-tank-detail span {
        white-space: nowrap;
    }

    .pgd-empty-state {
        margin: 12px 0 30px;
        padding: 45px 20px;
        color: #667085;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        text-align: center;
    }

    .pgd-empty-state i {
        margin-bottom: 10px;
        color: #3c8dbc;
        font-size: 40px;
    }

    .pgd-empty-state h3 {
        margin: 0;
        font-size: 17px;
    }

    @media (max-width: 1199px) {
        .pgd-tank-column { min-height: 320px; }
        .pgd-gauge-wrap { min-height: 168px; }
        .pgd-gauge-svg { max-width: 183px; }
    }

    @media (max-width: 767px) {
        .pgd-tank-column {
            min-height: 0;
            margin-bottom: 34px;
        }
        .pgd-tank-divider { width: 62%; }
        .pgd-gauge-wrap { min-height: 165px; }
        .pgd-gauge-svg { max-width: 180px; }
    }

    @media (max-width: 420px) {
        .pgd-gauge-wrap { min-height: 150px; }
        .pgd-gauge-svg { max-width: 162px; }
        .pgd-tank-name { font-size: 19px; }
        .pgd-tank-detail { font-size: 15px; }
    }
</style>
@endsection

@section('content')
<section class="content-header">
    <h1>{{ __('home.welcome_message', ['name' => session('user.first_name')]) }}</h1>
</section>

{{--
    Petro General / Petro Dashboard
    The tank status gauge is rendered directly by Blade as SVG. There is no
    JavaScript, Google Charts, CDN, Petro Module or other feature-module asset
    dependency, so the gauge remains visible even when external assets are not
    available.
--}}
<section class="content no-print pgd-page">
    @if(!empty($dashboard['message']))
        <div class="pgd-message" style="font-size: {{ $dashboard['message']['font_size'] }}px; color: {{ $dashboard['message']['color'] }};">
            {!! $dashboard['message']['text'] !!}
        </div>
    @endif

    @if($dashboard['tanks']->isEmpty())
        <div class="pgd-empty-state">
            <i class="fa fa-tint" aria-hidden="true"></i>
            <h3>@lang('petrogeneral::lang.no_fuel_tanks')</h3>
        </div>
    @else
        <div class="row pgd-tank-grid">
            @foreach($dashboard['tanks'] as $tank)
                @php
                    $gaugeValue = max(0, min(100, (float) $tank->fill_percentage));
                    $gaugeDisplay = (int) round($gaugeValue);
                    $needleAngle = 135 + ($gaugeValue * 2.7);
                    $gaugeId = 'pgd-gauge-' . (int) $tank->id . '-' . $loop->index;
                    $cx = 160;
                    $cy = 132;
                @endphp

                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 text-center pgd-tank-column">
                    <div class="pgd-tank-divider" aria-hidden="true"></div>

                    <div class="pgd-gauge-wrap">
                        <svg
                            class="pgd-gauge-svg"
                            width="320"
                            height="285"
                            viewBox="0 0 320 285"
                            role="img"
                            aria-label="{{ $tank->fuel_tank_number }} tank level {{ $gaugeDisplay }} percent"
                        >
                            <defs>
                                <linearGradient id="{{ $gaugeId }}-bezel" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#f8f8f8" />
                                    <stop offset="24%" stop-color="#cfcfcf" />
                                    <stop offset="50%" stop-color="#eeeeee" />
                                    <stop offset="78%" stop-color="#bdbdbd" />
                                    <stop offset="100%" stop-color="#ececec" />
                                </linearGradient>
                                <radialGradient id="{{ $gaugeId }}-face" cx="48%" cy="42%" r="67%">
                                    <stop offset="0%" stop-color="#ffffff" />
                                    <stop offset="78%" stop-color="#fafafa" />
                                    <stop offset="100%" stop-color="#eeeeee" />
                                </radialGradient>
                                <radialGradient id="{{ $gaugeId }}-hub" cx="38%" cy="30%" r="70%">
                                    <stop offset="0%" stop-color="#62a1f4" />
                                    <stop offset="100%" stop-color="#3f7fd9" />
                                </radialGradient>
                                <filter id="{{ $gaugeId }}-shadow" x="-20%" y="-20%" width="140%" height="140%">
                                    <feDropShadow dx="0" dy="1.4" stdDeviation="1.5" flood-color="#000000" flood-opacity="0.20" />
                                </filter>
                            </defs>

                            {{-- Outer bezel and dial face --}}
                            <circle cx="160" cy="132" r="124" fill="url(#{{ $gaugeId }}-bezel)" stroke="#5a5a5a" stroke-width="1.6" />
                            <circle cx="160" cy="132" r="112" fill="#ededed" stroke="#c5c5c5" stroke-width="2" />
                            <circle cx="160" cy="132" r="103" fill="url(#{{ $gaugeId }}-face)" stroke="#ffffff" stroke-width="1.5" />

                            {{-- Status bands: Low 0-35%, Medium 35-70%, Good 70-100%. --}}
                            <path d="M 95.653 196.347 A 91 91 0 0 1 100.900 62.803" fill="none" stroke="#e83b0b" stroke-width="24" stroke-linecap="butt" />
                            <path d="M 100.900 62.803 A 91 91 0 0 1 233.621 78.512" fill="none" stroke="#ff9900" stroke-width="24" stroke-linecap="butt" />

                            {{-- Dial ticks are server rendered, so no browser JavaScript is required. --}}
                            @for($tick = 0; $tick <= 20; $tick++)
                                @php
                                    $tickValue = $tick * 5;
                                    $tickAngle = 135 + ($tickValue * 2.7);
                                    $tickRadians = deg2rad($tickAngle);
                                    $isMajor = ($tickValue % 25) === 0;
                                    $startRadius = $isMajor ? 78 : 83;
                                    $endRadius = 99;
                                    $x1 = $cx + ($startRadius * cos($tickRadians));
                                    $y1 = $cy + ($startRadius * sin($tickRadians));
                                    $x2 = $cx + ($endRadius * cos($tickRadians));
                                    $y2 = $cy + ($endRadius * sin($tickRadians));
                                @endphp
                                <line
                                    x1="{{ number_format($x1, 2, '.', '') }}"
                                    y1="{{ number_format($y1, 2, '.', '') }}"
                                    x2="{{ number_format($x2, 2, '.', '') }}"
                                    y2="{{ number_format($y2, 2, '.', '') }}"
                                    stroke="{{ $isMajor ? '#2f2f2f' : '#707070' }}"
                                    stroke-width="{{ $isMajor ? '3' : '1.4' }}"
                                />
                            @endfor

                            {{-- Scale labels --}}
                            <text x="104.65" y="182.35" fill="#252525" font-size="15" font-family="Arial, sans-serif" text-anchor="middle">0</text>
                            <text x="205.35" y="182.35" fill="#252525" font-size="15" font-family="Arial, sans-serif" text-anchor="middle">100</text>

                            {{-- Dynamic needle. Base needle points right and rotates around the hub. --}}
                            <g transform="rotate({{ number_format($needleAngle, 3, '.', '') }} 160 132)">
                                <polygon
                                    points="142,127.8 252,132 142,136.2"
                                    fill="#e76545"
                                    stroke="#b84129"
                                    stroke-width="1.6"
                                    stroke-linejoin="round"
                                />
                            </g>
                            <circle cx="160" cy="132" r="16.5" fill="url(#{{ $gaugeId }}-hub)" stroke="#777777" stroke-width="1.5" />

                            {{-- Percentage value --}}
                            <text x="160" y="226" fill="#111111" font-size="27" font-family="Arial, sans-serif" font-weight="400" text-anchor="middle">{{ $gaugeDisplay }}</text>
                        </svg>
                    </div>

                    <h4 class="pgd-tank-name">{{ $tank->fuel_tank_number }}</h4>
                    <p class="pgd-tank-detail">
                        @lang('petrogeneral::lang.current_balance'):
                        <span>{{ number_format($tank->current_balance, 2, '.', ',') }}</span>
                    </p>
                    <p class="pgd-tank-detail">
                        @lang('petrogeneral::lang.storage_volume'):
                        <span>{{ number_format($tank->storage_volume, 2, '.', ',') }}</span>
                    </p>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endsection
