{{--
    S-662: Petro General dashboard tank gauges, rebuilt to the requested format.

    WHAT CHANGED
        The half-circle "E to F" fuel gauge in a bordered card is replaced by the
        round speedometer dial in the second reference image: a grey bezel, a
        warm coloured scale, tick marks, a blue hub, a red needle, the value
        printed large inside the dial, and the tank name with its figures
        underneath on a plain background - three to a row.

    STILL INLINE SVG
        No chart library and no image files, so it prints and needs nothing
        published. Everything is scoped to .pg-dash.

    GEOMETRY
        A 270 degree dial. Angles are measured clockwise from the positive x
        axis in SVG screen coordinates, where y increases downwards:

            theta(v) = 135 + 2.7v        degrees, for v in 0..100
            x = cx + r cos(theta)        y = cy + r sin(theta)

        v = 0   -> 135 deg, lower left
        v = 50  -> 270 deg, top
        v = 100 ->  45 deg, lower right

        Every band spans less than 180 degrees, so the SVG large-arc-flag stays
        0. The needle art points straight up, so it is rotated by
        theta(v) - 270 about the hub.

    THE NEEDLE IS ROTATED BY THE SVG ATTRIBUTE ONLY - no CSS transform and no
    transition on it. Declaring either promotes the attribute transform to a CSS
    transform and applies transform-origin on top of the centre already given
    inside rotate(), which offsets the pivot twice and swings the needle outside
    the viewBox. That is what made it vanish once before; the sweep on load is
    done with SVG's own animateTransform, which cannot collide with CSS.
--}}
<style>
    .pg-dash .pg-tank-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 26px 18px;
        margin-bottom: 22px;
    }

    /* Two up, then one up, as the screen narrows. */
    @media (max-width: 1100px) {
        .pg-dash .pg-tank-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 700px) {
        .pg-dash .pg-tank-grid { grid-template-columns: minmax(0, 1fr); }
    }

    /*
     * The reference has no card: the dials sit directly on the page. The border,
     * shadow and hover lift are therefore gone rather than restyled.
     */
    .pg-dash .pg-tank {
        background: transparent;
        text-align: center;
        padding: 4px 6px 10px;
    }

    .pg-dash .pg-gauge {
        width: 100%;
        /* 24 Sep 2026: increase dashboard gauge diameter by 10% (210px -> 231px). */
        max-width: 231px;
        height: auto;
        display: block;
        margin: 0 auto 6px;
    }

    .pg-dash .pg-tank-no {
        font-size: 15px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .pg-dash .pg-tank-product {
        font-size: 12px;
        color: #94a3b8;
        margin-bottom: 6px;
    }

    /* Label and figure on one line, as in the reference. */
    .pg-dash .pg-figure {
        font-size: 12.5px;
        color: #475569;
        line-height: 1.7;
    }

    .pg-dash .pg-figure strong {
        color: #1f2937;
        font-weight: 600;
    }

    .pg-dash .pg-section-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 14px;
    }

    .pg-dash .pg-empty { color: #94a3b8; font-size: 13px; padding: 10px 0; }
</style>

<h4 class="pg-section-title">@lang('petrogeneral::lang.available_vs_capacity')</h4>

@if(empty($tanks) || count($tanks) === 0)
    <div class="pg-empty">@lang('petrogeneral::lang.no_records_found')</div>
@else
    @php
        /*
         * The warm scale of the reference dial: deep red at empty, easing to a
         * pale amber at full. Six bands keep the steps smooth without needing a
         * gradient, which not every PDF renderer honours.
         */
        $pgBands = [
            ['from' => 0,   'to' => 17,  'colour' => '#c0392b'],
            ['from' => 17,  'to' => 34,  'colour' => '#e14b16'],
            ['from' => 34,  'to' => 50,  'colour' => '#f07000'],
            ['from' => 50,  'to' => 67,  'colour' => '#f79009'],
            ['from' => 67,  'to' => 84,  'colour' => '#fbb040'],
            ['from' => 84,  'to' => 100, 'colour' => '#fdd08a'],
        ];

        // Clockwise from the positive x axis, y downwards. 135deg = empty.
        $pgAngle = function ($value) {
            return 135 + (2.7 * $value);
        };

        $pgPoint = function ($value, $radius) use ($pgAngle) {
            $rad = deg2rad($pgAngle($value));

            return [
                120 + ($radius * cos($rad)),
                120 + ($radius * sin($rad)),
            ];
        };
    @endphp

    <div class="pg-tank-grid">
        @foreach($tanks as $tank)
            @php
                $capacity  = (float) ($tank->storage_volume ?? 0);
                $available = (float) ($tank->current_balance ?? 0);

                /*
                 * A capacity of zero would divide by zero. A balance outside the
                 * range happens after a dip correction, so the NEEDLE is clamped
                 * to the dial while the printed figures still show what is really
                 * stored - an over-full or negative tank stays visible instead of
                 * being quietly hidden.
                 */
                $pct = $capacity > 0 ? ($available / $capacity) * 100 : 0;
                $needlePct = max(0, min(100, $pct));
                $displayPct = (int) round($needlePct);

                $needleAngle = $pgAngle($needlePct) - 270;
            @endphp
            <div class="pg-tank">
                <svg class="pg-gauge" viewBox="0 0 240 240" role="img"
                     aria-label="{{ $tank->fuel_tank_number ?? '' }}, {{ $displayPct }}% available">

                    {{-- bezel: the grey ring the dial sits in --}}
                    <circle cx="120" cy="120" r="104" fill="#ffffff"
                            stroke="#d9dde3" stroke-width="10"></circle>
                    <circle cx="120" cy="120" r="96" fill="#ffffff"
                            stroke="#f1f3f6" stroke-width="4"></circle>

                    {{-- the coloured scale --}}
                    @foreach($pgBands as $band)
                        @php
                            [$sx, $sy] = $pgPoint($band['from'], 78);
                            [$ex, $ey] = $pgPoint($band['to'], 78);
                        @endphp
                        <path d="M {{ round($sx, 2) }} {{ round($sy, 2) }}
                                 A 78 78 0 0 1 {{ round($ex, 2) }} {{ round($ey, 2) }}"
                              fill="none" stroke="{{ $band['colour'] }}" stroke-width="20"
                              stroke-linecap="butt"></path>
                    @endforeach

                    {{-- tick marks every 10, longer at each 25 --}}
                    @for($t = 0; $t <= 100; $t += 5)
                        @php
                            $isMajor = ($t % 25 === 0);
                            [$ix, $iy] = $pgPoint($t, $isMajor ? 60 : 64);
                            [$ox, $oy] = $pgPoint($t, 68);
                        @endphp
                        <line x1="{{ round($ix, 2) }}" y1="{{ round($iy, 2) }}"
                              x2="{{ round($ox, 2) }}" y2="{{ round($oy, 2) }}"
                              stroke="#334155" stroke-width="{{ $isMajor ? 2.4 : 1.2 }}"
                              opacity="{{ $isMajor ? 0.85 : 0.45 }}"></line>
                    @endfor

                    {{-- the full-scale label, as in the reference --}}
                    @php [$hx, $hy] = $pgPoint(100, 46); @endphp
                    <text x="{{ round($hx, 2) }}" y="{{ round($hy, 2) }}"
                          font-size="11" font-weight="600" fill="#475569"
                          text-anchor="middle">100</text>

                    {{-- Available quantity as a percentage of tank storage capacity.
                         Keep the display aligned with the 0-100 gauge range.
                         Font reduced by 1.5 from 26 to 24.5 as requested. --}}
                    <text x="120" y="176" font-size="24.5" font-weight="800"
                          fill="#111827" text-anchor="middle">{{ $displayPct }}%</text>

                    {{-- needle: SVG attribute rotation only, see the note above --}}
                    <g transform="rotate({{ round($needleAngle, 2) }} 120 120)">
                        <polygon points="116.5,120 123.5,120 120,44" fill="#d0342c"></polygon>
                        <polygon points="118,120 122,120 120,140" fill="#d0342c" opacity="0.75"></polygon>
                        <animateTransform attributeName="transform"
                                          type="rotate"
                                          from="{{ round($pgAngle(0) - 270, 2) }} 120 120"
                                          to="{{ round($needleAngle, 2) }} 120 120"
                                          dur="0.7s"
                                          fill="freeze"></animateTransform>
                    </g>

                    {{-- hub --}}
                    <circle cx="120" cy="120" r="11" fill="#2f6fed"></circle>
                    <circle cx="120" cy="120" r="4.5" fill="#5b8ff2"></circle>
                </svg>

                <div class="pg-tank-no">{{ $tank->fuel_tank_number ?? '' }}</div>

                @if(!empty($tank->product_name) || !empty($tank->fuel_type))
                    <div class="pg-tank-product">{{ $tank->product_name ?? $tank->fuel_type }}</div>
                @endif

                <div class="pg-figure">
                    @lang('petrogeneral::lang.available'):
                    <strong>{{ number_format($available, 2) }}</strong>
                </div>
                <div class="pg-figure">
                    @lang('petrogeneral::lang.storage_volume'):
                    <strong>{{ number_format($capacity, 2) }}</strong>
                </div>
            </div>
        @endforeach
    </div>
@endif
