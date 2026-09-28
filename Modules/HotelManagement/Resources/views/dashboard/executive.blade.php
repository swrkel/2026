@extends('hotelmanagement::layouts.app')

@section('title', $title ?? 'Hotel Dashboard')

@section('css')
    @parent
    <style>
        .hm-owner-dashboard {
            color: #0f172a;
            background: #f5f8fc;
            padding: 0 22px 28px;
            min-height: calc(100vh - 145px);
        }

        .hm-owner-dashboard a,
        .hm-owner-dashboard a:hover,
        .hm-owner-dashboard a:focus {
            text-decoration: none;
        }

        .hm-dashboard-hero {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 24px 28px;
            margin: 0 0 22px;
            border: 1px solid #dbe7f3;
            border-radius: 18px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 55%, #eef6ff 100%);
            box-shadow: 0 14px 35px rgba(15, 23, 42, .08);
        }

        .hm-dashboard-hero::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(180deg, #2563eb, #06b6d4);
        }

        .hm-dashboard-eyebrow {
            margin-bottom: 6px;
            color: #2563eb;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .hm-dashboard-hero h1 {
            margin: 0;
            color: #102033;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .hm-dashboard-hero p {
            max-width: 720px;
            margin: 8px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.55;
        }

        .hm-dashboard-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .hm-dashboard-actions .btn {
            min-height: 42px;
            padding: 10px 16px;
            border-radius: 10px;
            border-width: 1px;
            font-weight: 700;
            box-shadow: 0 6px 16px rgba(2, 6, 23, .08);
        }

        .hm-dashboard-actions .btn-default {
            border-color: #dbe7f3;
            background: #ffffff;
            color: #0f172a;
        }

        .hm-dashboard-actions .btn-primary {
            border-color: #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .hm-dashboard-actions .btn-success {
            border-color: #16a34a;
            background: #16a34a;
            color: #ffffff;
        }

        .hm-dashboard-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .hm-dashboard-kpi-link {
            display: block;
            height: 100%;
            color: inherit;
        }

        .hm-dashboard-kpi {
            position: relative;
            overflow: hidden;
            min-height: 168px;
            height: 100%;
            padding: 20px 20px 16px;
            border: 1px solid #dbe7f3;
            border-radius: 18px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            box-shadow: 0 14px 30px rgba(15, 23, 42, .08);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .hm-dashboard-kpi:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 42px rgba(15, 23, 42, .12);
        }

        .hm-dashboard-kpi::before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 4px;
            background: #2563eb;
        }

        .hm-dashboard-kpi-top {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .hm-dashboard-kpi-icon {
            flex: 0 0 56px;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            color: #ffffff;
            font-size: 21px;
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            box-shadow: 0 10px 20px rgba(37, 99, 235, .22);
        }

        .hm-dashboard-kpi-label {
            color: #334155;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.25;
        }

        .hm-dashboard-kpi-value {
            margin-top: 16px;
            color: #0f172a;
            font-size: 30px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -.02em;
        }

        .hm-dashboard-kpi-hint {
            min-height: 18px;
            margin-top: 10px;
            color: #64748b;
            font-size: 12px;
        }

        .hm-dashboard-kpi-open {
            float: right;
            color: #2563eb;
            font-size: 11px;
            font-weight: 900;
        }

        .hm-dashboard-kpi-spark {
            position: relative;
            overflow: hidden;
            height: 22px;
            margin-top: 12px;
            border-radius: 12px;
            background: linear-gradient(90deg, rgba(37, 99, 235, .08), rgba(37, 99, 235, .20), rgba(37, 99, 235, .08));
        }

        .hm-dashboard-kpi-spark::after {
            content: "";
            position: absolute;
            left: 10%;
            right: 10%;
            top: 11px;
            border-top: 2px solid rgba(37, 99, 235, .75);
            transform: skewY(-7deg);
        }

        .hm-dashboard-kpi.success::before { background: #16a34a; }
        .hm-dashboard-kpi.success .hm-dashboard-kpi-icon { background: linear-gradient(135deg, #16a34a, #86efac); box-shadow: 0 10px 20px rgba(22, 163, 74, .22); }
        .hm-dashboard-kpi.success .hm-dashboard-kpi-value { color: #16a34a; }
        .hm-dashboard-kpi.success .hm-dashboard-kpi-spark { background: linear-gradient(90deg, rgba(22, 163, 74, .08), rgba(22, 163, 74, .20), rgba(22, 163, 74, .08)); }
        .hm-dashboard-kpi.success .hm-dashboard-kpi-spark::after { border-color: rgba(22, 163, 74, .75); }

        .hm-dashboard-kpi.warning::before { background: #f59e0b; }
        .hm-dashboard-kpi.warning .hm-dashboard-kpi-icon { background: linear-gradient(135deg, #f59e0b, #fde68a); box-shadow: 0 10px 20px rgba(245, 158, 11, .22); }
        .hm-dashboard-kpi.warning .hm-dashboard-kpi-value { color: #f97316; }
        .hm-dashboard-kpi.warning .hm-dashboard-kpi-spark { background: linear-gradient(90deg, rgba(245, 158, 11, .08), rgba(245, 158, 11, .22), rgba(245, 158, 11, .08)); }
        .hm-dashboard-kpi.warning .hm-dashboard-kpi-spark::after { border-color: rgba(245, 158, 11, .78); }

        .hm-dashboard-kpi.purple::before { background: #7c3aed; }
        .hm-dashboard-kpi.purple .hm-dashboard-kpi-icon { background: linear-gradient(135deg, #7c3aed, #c084fc); box-shadow: 0 10px 20px rgba(124, 58, 237, .22); }
        .hm-dashboard-kpi.purple .hm-dashboard-kpi-value { color: #7c3aed; }
        .hm-dashboard-kpi.purple .hm-dashboard-kpi-spark { background: linear-gradient(90deg, rgba(124, 58, 237, .08), rgba(124, 58, 237, .20), rgba(124, 58, 237, .08)); }
        .hm-dashboard-kpi.purple .hm-dashboard-kpi-spark::after { border-color: rgba(124, 58, 237, .75); }

        .hm-dashboard-kpi.danger::before { background: #ef4444; }
        .hm-dashboard-kpi.danger .hm-dashboard-kpi-icon { background: linear-gradient(135deg, #ef4444, #fca5a5); box-shadow: 0 10px 20px rgba(239, 68, 68, .22); }
        .hm-dashboard-kpi.danger .hm-dashboard-kpi-value { color: #dc2626; }
        .hm-dashboard-kpi.danger .hm-dashboard-kpi-spark { background: linear-gradient(90deg, rgba(239, 68, 68, .08), rgba(239, 68, 68, .20), rgba(239, 68, 68, .08)); }
        .hm-dashboard-kpi.danger .hm-dashboard-kpi-spark::after { border-color: rgba(239, 68, 68, .75); }

        .hm-dashboard-kpi.info::before { background: #0891b2; }
        .hm-dashboard-kpi.info .hm-dashboard-kpi-icon { background: linear-gradient(135deg, #0891b2, #67e8f9); box-shadow: 0 10px 20px rgba(8, 145, 178, .22); }
        .hm-dashboard-kpi.info .hm-dashboard-kpi-value { color: #0891b2; }
        .hm-dashboard-kpi.info .hm-dashboard-kpi-spark { background: linear-gradient(90deg, rgba(8, 145, 178, .08), rgba(8, 145, 178, .20), rgba(8, 145, 178, .08)); }
        .hm-dashboard-kpi.info .hm-dashboard-kpi-spark::after { border-color: rgba(8, 145, 178, .75); }

        @media (max-width: 1199px) {
            .hm-dashboard-kpi-grid {
                grid-template-columns: repeat(2, minmax(220px, 1fr));
            }
        }

        @media (max-width: 767px) {
            .hm-owner-dashboard {
                padding: 0 10px 20px;
            }

            .hm-dashboard-hero {
                display: block;
                padding: 20px;
            }

            .hm-dashboard-hero h1 {
                font-size: 24px;
            }

            .hm-dashboard-actions {
                justify-content: flex-start;
                margin-top: 16px;
            }

            .hm-dashboard-actions .btn {
                flex: 1 1 auto;
            }

            .hm-dashboard-kpi-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }
    </style>
@endsection

@section('hotel_content')
@php
    $cardMeta = [
        'rooms' => [
            'label' => 'Rooms',
            'icon' => 'fa-bed',
            'tone' => '',
            'hint' => 'Total hotel rooms',
            'route' => 'hotel-management.rooms.index',
            'money' => false,
        ],
        'reservations_today' => [
            'label' => 'Reservations Today',
            'icon' => 'fa-calendar-check-o',
            'tone' => 'success',
            'hint' => 'Reservations created today',
            'route' => 'hotel-management.reservations.index',
            'money' => false,
        ],
        'checkins_today' => [
            'label' => 'Check-ins Today',
            'icon' => 'fa-sign-in',
            'tone' => 'warning',
            'hint' => 'Guest arrivals today',
            'route' => 'hotel-management.front-office.index',
            'money' => false,
        ],
        'checkouts_today' => [
            'label' => 'Checkouts Today',
            'icon' => 'fa-sign-out',
            'tone' => 'purple',
            'hint' => 'Guest departures today',
            'route' => 'hotel-management.front-office.index',
            'money' => false,
        ],
        'revenue_today' => [
            'label' => 'Revenue Today',
            'icon' => 'fa-money',
            'tone' => 'info',
            'hint' => 'Guest payments received today',
            'route' => 'hotel-management.reports.revenue',
            'money' => true,
        ],
        'open_housekeeping' => [
            'label' => 'Open Housekeeping',
            'icon' => 'fa-magic',
            'tone' => 'danger',
            'hint' => 'Pending housekeeping tasks',
            'route' => 'hotel-management.housekeeping.index',
            'money' => false,
        ],
    ];
@endphp

<section class="content hm-owner-dashboard">
    <div class="hm-dashboard-hero">
        <div>
            <div class="hm-dashboard-eyebrow">Module</div>
            <h1>{{ $title ?? 'Hotel Dashboard' }}</h1>
            <p>Welcome to Hotel Management. Here is what is happening in your hotel today.</p>
        </div>

        <div class="hm-dashboard-actions">
            @if(Route::has('hotel-management.dashboards.owner'))
                <a href="{{ route('hotel-management.dashboards.owner') }}" class="btn btn-default">
                    <i class="fa fa-dashboard"></i> Dashboard
                </a>
            @endif

            @if(Route::has('hotel-management.reservations.index'))
                <a href="{{ route('hotel-management.reservations.index') }}" class="btn btn-primary">
                    <i class="fa fa-calendar-plus-o"></i> Reservations
                </a>
            @endif

            @if(Route::has('hotel-management.reports.index'))
                <a href="{{ route('hotel-management.reports.index') }}" class="btn btn-success">
                    <i class="fa fa-bar-chart"></i> Reports
                </a>
            @endif
        </div>
    </div>

    <div class="hm-dashboard-kpi-grid">
        @foreach($kpis ?? [] as $key => $value)
            @php
                $meta = $cardMeta[$key] ?? [
                    'label' => ucwords(str_replace('_', ' ', $key)),
                    'icon' => 'fa-line-chart',
                    'tone' => '',
                    'hint' => 'Current hotel activity',
                    'route' => 'hotel-management.dashboards.owner',
                    'money' => false,
                ];

                $cardUrl = !empty($meta['route']) && Route::has($meta['route'])
                    ? route($meta['route'])
                    : '#';

                if (is_numeric($value)) {
                    $formattedValue = !empty($meta['money'])
                        ? number_format((float) $value, 4)
                        : number_format((float) $value, 0);
                } else {
                    $formattedValue = $value;
                }
            @endphp

            <a href="{{ $cardUrl }}" class="hm-dashboard-kpi-link">
                <div class="hm-dashboard-kpi {{ $meta['tone'] }}">
                    <div class="hm-dashboard-kpi-top">
                        <div class="hm-dashboard-kpi-icon">
                            <i class="fa {{ $meta['icon'] }}"></i>
                        </div>
                        <div class="hm-dashboard-kpi-label">{{ $meta['label'] }}</div>
                    </div>

                    <div class="hm-dashboard-kpi-value">{{ $formattedValue }}</div>

                    <div class="hm-dashboard-kpi-hint">
                        {{ $meta['hint'] }}
                        @if($cardUrl !== '#')
                            <span class="hm-dashboard-kpi-open">
                                Open <i class="fa fa-angle-right"></i>
                            </span>
                        @endif
                    </div>

                    <div class="hm-dashboard-kpi-spark"></div>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endsection
