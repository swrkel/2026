@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::messages.leads'))
@section('leadsnew_content')
<section class="content-header leads-new-header">
    <h1><i class="fa fa-lightbulb-o"></i> {{ __('leadsnew::messages.leads') }}</h1>
    <div class="breadcrumb-note">{{ __('leadsnew::messages.standalone_module') }}</div>
</section>
<section class="content leads-new-page">

    @if(!empty($missingTables ?? []))
        <div class="ln-setup-alert">
            <div class="ln-setup-icon"><i class="fa fa-database"></i></div>
            <div>
                <strong>{{ __('leadsnew::messages.database_setup_pending_title') }}</strong><br>
                <span>{{ __('leadsnew::messages.database_setup_pending_clean') }}</span>
            </div>
        </div>
    @endif
    @include('leadsnew::partials.standard_toolbar', [
        'createUrl' => url('/leads-new/leads/create'),
        'createLabel' => __('leadsnew::messages.add_lead'),
        'showDateRange' => true,
    ])

    <div class="ln-panel">
        <div class="ln-panel-header">
            <h3 class="ln-panel-title"><i class="fa fa-list"></i> {{ __('leadsnew::messages.lead_list') }}</h3>
            <span class="text-muted">{{ $leads->total() ?? 0 }} {{ __('leadsnew::messages.no_records_found') == 'No records found.' ? 'records' : '' }}</span>
        </div>
        <div class="ln-table table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>{{ __('leadsnew::messages.lead_no') }}</th>
                        <th>{{ __('leadsnew::messages.name') }}</th>
                        <th>{{ __('leadsnew::messages.mobile') }}</th>
                        <th>{{ __('leadsnew::messages.email') }}</th>
                        <th>{{ __('leadsnew::messages.status') }}</th>
                        <th>{{ __('leadsnew::messages.priority') }}</th>
                        <th class="text-right">{{ __('leadsnew::messages.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                        @php($statusClass = strtolower(str_replace(' ', '-', $lead->status ?: 'new')))
                        <tr>
                            <td><strong>{{ $lead->lead_no }}</strong></td>
                            <td>{{ $lead->name }}</td>
                            <td>{{ $lead->mobile ?: '-' }}</td>
                            <td>{{ $lead->email ?: '-' }}</td>
                            <td><span class="ln-badge status-{{ $statusClass }}">{{ $lead->status ?: 'New' }}</span></td>
                            <td>{{ $lead->priority ?: '-' }}</td>
                            <td class="text-right">
                                <div class="btn-group ln-action-dropdown">
                                    <button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">
                                        {{ __('leadsnew::messages.action') }} <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li><a href="{{ url('/leads-new/leads/' . $lead->id) }}"><i class="fa fa-eye"></i> {{ __('leadsnew::messages.view') }}</a></li>
                                        <li><a href="{{ url('/leads-new/leads/' . $lead->id . '/edit') }}"><i class="fa fa-pencil"></i> {{ __('leadsnew::messages.edit') }}</a></li>
                                        <li>
                                            <form method="post" action="{{ url('/leads-new/leads/' . $lead->id . '/duplicate') }}">@csrf
                                                <button type="submit" class="btn btn-link btn-block text-left"><i class="fa fa-copy"></i> {{ __('leadsnew::messages.duplicate') }}</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="post" action="{{ url('/leads-new/leads/' . $lead->id) }}" onsubmit="return confirm('{{ __('leadsnew::messages.confirm_delete') }}')">@csrf @method('DELETE')
                                                <button type="submit" class="btn btn-link btn-block text-left text-danger"><i class="fa fa-trash"></i> {{ __('leadsnew::messages.delete') }}</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="ln-empty"><i class="fa fa-inbox"></i>{{ __('leadsnew::messages.no_leads_yet') }}</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="ln-panel-body">{{ $leads->appends(request()->query())->links() }}</div>
    </div>
</section>
@endsection
@section('css')
<link rel="stylesheet" href="{{ asset('Modules/LeadsNew/Resources/assets/css/leads_new.css') }}">
@endsection
