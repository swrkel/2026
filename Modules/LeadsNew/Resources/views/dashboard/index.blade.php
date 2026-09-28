@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::messages.leads_new_dashboard'))
@section('leadsnew_subtitle', __('leadsnew::messages.dashboard_summary'))

@section('leadsnew_content')
    @if(!empty($missingTables ?? []))
        <div class="ln-setup-alert">
            <div class="ln-setup-icon"><i class="fa fa-database"></i></div>
            <div>
                <strong>{{ __('leadsnew::messages.database_setup_pending_title') }}</strong><br>
                <span>{{ __('leadsnew::messages.database_setup_pending_clean') }}</span>
            </div>
        </div>
    @endif

    @php
        $cards = [
            [
                'label' => __('leadsnew::messages.total_leads'),
                'value' => $totalLeads ?? 0,
                'icon' => 'fa-users',
                'hint' => __('leadsnew::messages.all_lead_records'),
                'tone' => '',
                'url' => url('/leads-new/leads'),
            ],
            [
                'label' => __('leadsnew::messages.today'),
                'value' => $todayLeads ?? 0,
                'icon' => 'fa-calendar-check-o',
                'hint' => __('leadsnew::messages.created_today'),
                'tone' => 'cyan',
                'url' => url('/leads-new/leads'),
            ],
            [
                'label' => __('leadsnew::messages.this_month'),
                'value' => $monthlyLeads ?? 0,
                'icon' => 'fa-line-chart',
                'hint' => __('leadsnew::messages.created_this_month'),
                'tone' => 'purple',
                'url' => url('/leads-new/leads'),
            ],
            [
                'label' => __('leadsnew::messages.converted'),
                'value' => $convertedLeads ?? 0,
                'icon' => 'fa-check-circle',
                'hint' => __('leadsnew::messages.successful_conversions'),
                'tone' => 'success',
                'url' => url('/leads-new/leads'),
            ],
            [
                'label' => __('leadsnew::messages.followup_due'),
                'value' => $dueFollowups ?? 0,
                'icon' => 'fa-clock-o',
                'hint' => __('leadsnew::messages.pending_followups'),
                'tone' => 'warning',
                'url' => url('/leads-new/followups'),
            ],
        ];
    @endphp

    <div class="ch-kpi-grid ln-dashboard-kpis">
        @foreach($cards as $card)
            <a href="{{ $card['url'] }}" class="ch-kpi-link">
                <div class="ch-kpi {{ $card['tone'] }}">
                    <div class="ch-kpi-top">
                        <div class="ch-icon"><i class="fa {{ $card['icon'] }}"></i></div>
                        <div class="label-text">{{ $card['label'] }}</div>
                    </div>
                    <div class="value">{{ number_format((float) $card['value'], 0) }}</div>
                    <div class="hint">
                        {{ $card['hint'] }}
                        <span class="ch-drill">{{ __('leadsnew::messages.open') }} <i class="fa fa-angle-right"></i></span>
                    </div>
                    <div class="spark"></div>
                </div>
            </a>
        @endforeach
    </div>

    <div class="ln-dashboard-panels">
        <div class="ch-card">
            <div class="ch-card-header">
                <div>
                    <h3 class="ch-card-title"><i class="fa fa-history text-primary"></i> {{ __('leadsnew::messages.recent_leads') }}</h3>
                    <div class="ch-card-subtitle">{{ __('leadsnew::messages.latest_lead_activity_note') }}</div>
                </div>
                <a href="{{ url('/leads-new/leads') }}" class="btn btn-default btn-sm">
                    {{ __('leadsnew::messages.view_all') }} <i class="fa fa-angle-right"></i>
                </a>
            </div>
            <div class="ch-card-body">
                <div class="ch-toolbar">
                    <div style="position:relative;min-width:260px;max-width:440px;flex:1;">
                        <input type="text" class="form-control ln-instant-search" data-target="#ln-recent-leads tbody tr" placeholder="{{ __('leadsnew::messages.search') }}..." style="padding-right:38px;">
                        <i class="fa fa-search" style="position:absolute;right:14px;top:13px;color:#64748b;"></i>
                    </div>
                    <a href="{{ url('/leads-new/leads/create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> {{ __('leadsnew::messages.add_lead') }}
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered ln-searchable-table" id="ln-recent-leads">
                        <thead>
                            <tr>
                                <th>{{ __('leadsnew::messages.lead_no') }}</th>
                                <th>{{ __('leadsnew::messages.name') }}</th>
                                <th>{{ __('leadsnew::messages.mobile') }}</th>
                                <th>{{ __('leadsnew::messages.status') }}</th>
                                <th>{{ __('leadsnew::messages.created_at') }}</th>
                                <th class="text-right">{{ __('leadsnew::messages.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($recentLeads ?? collect()) as $lead)
                                @php($statusClass = strtolower(str_replace(' ', '-', $lead->status ?: 'new')))
                                <tr>
                                    <td><strong>{{ $lead->lead_no ?: ('#' . $lead->id) }}</strong></td>
                                    <td>{{ $lead->name ?: '-' }}</td>
                                    <td>{{ $lead->mobile ?: '-' }}</td>
                                    <td><span class="ln-badge status-{{ $statusClass }}">{{ $lead->status ?: 'New' }}</span></td>
                                    <td>{{ optional($lead->created_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                    <td class="text-right">
                                        <a href="{{ url('/leads-new/leads/' . $lead->id) }}" class="btn btn-default btn-xs">
                                            <i class="fa fa-eye"></i> {{ __('leadsnew::messages.view') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="ln-empty">
                                            <i class="fa fa-inbox"></i>
                                            {{ __('leadsnew::messages.no_leads_yet') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="ch-card">
            <div class="ch-card-header">
                <div>
                    <h3 class="ch-card-title"><i class="fa fa-bolt text-primary"></i> {{ __('leadsnew::messages.quick_actions') }}</h3>
                    <div class="ch-card-subtitle">{{ __('leadsnew::messages.common_lead_actions') }}</div>
                </div>
            </div>
            <div class="ch-card-body ln-actions-list">
                <a href="{{ url('/leads-new/leads/create') }}" class="ln-action-tile">
                    <span class="left"><span class="tile-icon"><i class="fa fa-user-plus"></i></span>{{ __('leadsnew::messages.add_lead') }}</span>
                    <i class="fa fa-angle-right"></i>
                </a>
                <a href="{{ url('/leads-new/import') }}" class="ln-action-tile success">
                    <span class="left"><span class="tile-icon"><i class="fa fa-upload"></i></span>{{ __('leadsnew::messages.import_data') }}</span>
                    <i class="fa fa-angle-right"></i>
                </a>
                <a href="{{ url('/leads-new/reports') }}" class="ln-action-tile warning">
                    <span class="left"><span class="tile-icon"><i class="fa fa-bar-chart"></i></span>{{ __('leadsnew::messages.reports') }}</span>
                    <i class="fa fa-angle-right"></i>
                </a>
                <a href="{{ url('/leads-new/settings') }}" class="ln-action-tile">
                    <span class="left"><span class="tile-icon"><i class="fa fa-cogs"></i></span>{{ __('leadsnew::messages.settings') }}</span>
                    <i class="fa fa-angle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-sitemap text-primary"></i> {{ __('leadsnew::messages.lead_pipeline') }}</h3>
                <div class="ch-card-subtitle">{{ __('leadsnew::messages.pipeline_summary_note') }}</div>
            </div>
        </div>
        <div class="ch-card-body">
            <div class="ln-pipeline-grid">
                <div class="ln-pipeline-step new">
                    <strong>{{ number_format((float) ($pipeline['new'] ?? 0), 0) }}</strong>
                    <span>{{ __('leadsnew::messages.new_leads') }}</span>
                </div>
                <div class="ln-pipeline-step progress">
                    <strong>{{ number_format((float) ($pipeline['in_progress'] ?? 0), 0) }}</strong>
                    <span>{{ __('leadsnew::messages.in_progress') }}</span>
                </div>
                <div class="ln-pipeline-step converted">
                    <strong>{{ number_format((float) ($pipeline['converted'] ?? 0), 0) }}</strong>
                    <span>{{ __('leadsnew::messages.converted') }}</span>
                </div>
                <div class="ln-pipeline-step lost">
                    <strong>{{ number_format((float) ($pipeline['lost'] ?? 0), 0) }}</strong>
                    <span>{{ __('leadsnew::messages.lost') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-th-large text-warning"></i> {{ __('leadsnew::messages.quick_operations') }}</h3>
                <div class="ch-card-subtitle">{{ __('leadsnew::messages.start_work_here') }}</div>
            </div>
        </div>
        <div class="ch-card-body ln-quick-operations">
            <a href="{{ url('/leads-new/leads') }}" class="ln-quick-operation"><span class="qo-icon"><i class="fa fa-list"></i></span><span><strong>{{ __('leadsnew::messages.all_leads') }}</strong><span>{{ __('leadsnew::messages.manage_leads') }}</span></span></a>
            <a href="{{ url('/leads-new/followups') }}" class="ln-quick-operation"><span class="qo-icon"><i class="fa fa-clock-o"></i></span><span><strong>{{ __('leadsnew::messages.followups') }}</strong><span>{{ __('leadsnew::messages.manage_followups') }}</span></span></a>
            <a href="{{ url('/leads-new/opportunities') }}" class="ln-quick-operation"><span class="qo-icon"><i class="fa fa-line-chart"></i></span><span><strong>{{ __('leadsnew::messages.opportunities') }}</strong><span>{{ __('leadsnew::messages.manage_opportunities') }}</span></span></a>
            <a href="{{ url('/leads-new/calendar') }}" class="ln-quick-operation"><span class="qo-icon"><i class="fa fa-calendar"></i></span><span><strong>{{ __('leadsnew::messages.calendar') }}</strong><span>{{ __('leadsnew::messages.manage_calendar') }}</span></span></a>
            <a href="{{ url('/leads-new/documents') }}" class="ln-quick-operation"><span class="qo-icon"><i class="fa fa-file-text-o"></i></span><span><strong>{{ __('leadsnew::messages.documents') }}</strong><span>{{ __('leadsnew::messages.manage_documents') }}</span></span></a>
            <a href="{{ url('/leads-new/administration-centre') }}" class="ln-quick-operation"><span class="qo-icon"><i class="fa fa-sliders"></i></span><span><strong>{{ __('leadsnew::messages.administration') }}</strong><span>{{ __('leadsnew::messages.manage_configuration') }}</span></span></a>
        </div>
    </div>
@endsection
