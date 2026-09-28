@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::lang.reports'))
@section('leadsnew_subtitle', 'Open operational and management reports for lead activity, pipeline progress, conversions and follow-ups.')

@section('leadsnew_content')
    @php
        $reports = [
            [
                'title' => 'Lead Register',
                'category' => 'Lead Management',
                'description' => 'Complete lead register with contact and status information.',
                'icon' => 'fa-list-alt',
                'tone' => '',
                'url' => url('/leads-new/reports/lead-register'),
            ],
            [
                'title' => 'Conversion Report',
                'category' => 'Lead Analytics',
                'description' => 'Review lead conversion results grouped by status.',
                'icon' => 'fa-exchange',
                'tone' => 'success',
                'url' => url('/leads-new/reports/conversion'),
            ],
            [
                'title' => 'Pipeline Report',
                'category' => 'Opportunity Pipeline',
                'description' => 'Monitor opportunity stages, values and probability.',
                'icon' => 'fa-sitemap',
                'tone' => 'purple',
                'url' => url('/leads-new/reports/pipeline'),
            ],
            [
                'title' => 'Follow-up Report',
                'category' => 'Activity Tracking',
                'description' => 'Track scheduled and completed lead follow-up activity.',
                'icon' => 'fa-clock-o',
                'tone' => 'warning',
                'url' => url('/leads-new/reports/followups'),
            ],
            [
                'title' => 'Executive Report',
                'category' => 'Management Summary',
                'description' => 'Management summary of lead and opportunity performance.',
                'icon' => 'fa-dashboard',
                'tone' => 'cyan',
                'url' => url('/leads-new/reports/executive'),
            ],
            [
                'title' => 'Lead Source Report',
                'category' => 'Source Analysis',
                'description' => 'Understand which sources generate the strongest lead flow.',
                'icon' => 'fa-bullhorn',
                'tone' => 'danger',
                'url' => url('/leads-new/reports/source'),
            ],
            [
                'title' => 'Lead Ageing',
                'category' => 'Lead Monitoring',
                'description' => 'Review how long leads remain open before progression.',
                'icon' => 'fa-hourglass-half',
                'tone' => 'purple',
                'url' => url('/leads-new/reports/ageing'),
            ],
            [
                'title' => 'Team Performance',
                'category' => 'Team Analytics',
                'description' => 'Compare assigned users and lead management outcomes.',
                'icon' => 'fa-users',
                'tone' => 'success',
                'url' => url('/leads-new/reports/team-performance'),
            ],
        ];
    @endphp

    <div class="ch-card ln-reports-centre">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-bar-chart text-primary"></i> Leads-New Reports</h3>
                <div class="ch-card-subtitle">Select a report to review, export or print lead information.</div>
            </div>
        </div>

        <div class="ch-card-body">
            {{-- ch-kpi-grid is the same proven grid used by the POS-style Leads-New dashboard.
                 ln-report-dashboard-grid refines it to three report boxes per desktop row. --}}
            <div class="ch-kpi-grid ln-report-dashboard-grid">
                @foreach($reports as $report)
                    <a href="{{ $report['url'] }}" class="ch-kpi-link ln-report-kpi-link">
                        <div class="ch-kpi {{ $report['tone'] }} ln-report-kpi">
                            <div class="ch-kpi-top">
                                <div class="ch-icon"><i class="fa {{ $report['icon'] }}"></i></div>
                                <div class="label-text">{{ $report['category'] }}</div>
                            </div>

                            <div class="value">{{ $report['title'] }}</div>

                            <div class="hint">
                                <span class="ln-report-description">{{ $report['description'] }}</span>
                                <span class="ch-drill">Open Report <i class="fa fa-angle-right"></i></span>
                            </div>

                            <div class="spark"></div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endsection
