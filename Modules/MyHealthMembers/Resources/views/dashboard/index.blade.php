@extends('layouts.app')

@section('title', __('My Health'))

@section('content')
<section class="content-header">
    <h1>{{ __('My Health') }} <small>{{ __('Dashboard') }}</small></h1>
</section>

<section class="content">
    @php
        $cards = [
            ['label' => 'Total Members', 'value' => $summary['members'] ?? 0, 'icon' => 'fa fa-users', 'route' => 'myhealth.members.index'],
            ['label' => 'Active Members', 'value' => $summary['active_members'] ?? 0, 'icon' => 'fa fa-check-circle', 'route' => 'myhealth.members.index'],
            ['label' => 'New Today', 'value' => $summary['new_today'] ?? 0, 'icon' => 'fa fa-calendar-plus-o', 'route' => 'myhealth.members.index'],
            ['label' => 'Doctors', 'value' => $summary['doctors'] ?? 0, 'icon' => 'fa fa-user-md', 'route' => 'myhealth.doctors.index'],
            ['label' => 'Male', 'value' => $summary['male'] ?? 0, 'icon' => 'fa fa-male', 'route' => 'myhealth.members.index'],
            ['label' => 'Female', 'value' => $summary['female'] ?? 0, 'icon' => 'fa fa-female', 'route' => 'myhealth.members.index'],
            ['label' => 'Children', 'value' => $summary['children'] ?? 0, 'icon' => 'fa fa-child', 'route' => 'myhealth.members.index'],
            ['label' => 'Senior Citizens', 'value' => $summary['senior_citizens'] ?? 0, 'icon' => 'fa fa-wheelchair', 'route' => 'myhealth.members.index'],
            ['label' => 'Pending Labs', 'value' => $summary['pending_lab_requests'] ?? 0, 'icon' => 'fa fa-flask', 'route' => 'myhealth.labs.index'],
            ['label' => 'Upcoming Appointments', 'value' => $summary['upcoming_appointments'] ?? 0, 'icon' => 'fa fa-video-camera', 'route' => 'myhealth.telemedicine.appointments.index'],
            ['label' => 'Dispenses', 'value' => $summary['dispenses'] ?? 0, 'icon' => 'fa fa-medkit', 'route' => 'myhealth.pharmacy.dispensing.index'],
            ['label' => 'Invoices', 'value' => $summary['billing_invoices'] ?? 0, 'icon' => 'fa fa-file-text-o', 'route' => 'myhealth.billing.invoices.index'],
        ];
    @endphp

    <div class="row">
        @foreach($cards as $card)
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua"><i class="{{ $card['icon'] }}"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ __($card['label']) }}</span>
                        <span class="info-box-number">{{ number_format($card['value']) }}</span>
                        @if(!empty($card['route']) && \Illuminate\Support\Facades\Route::has($card['route']))
                            <a href="{{ route($card['route']) }}">{{ __('Open') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Recent My Health Members') }}</h3>
                    <div class="box-tools">
                        @if(\Illuminate\Support\Facades\Route::has('myhealth.members.create'))
                            <a href="{{ route('myhealth.members.create') }}" class="btn btn-sm btn-success"><i class="fa fa-plus"></i> {{ __('Add Member') }}</a>
                        @endif
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Mobile') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Registered') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentMembers ?? [] as $member)
                                <tr>
                                    <td><strong>{{ $member->myhealth_code }}</strong></td>
                                    <td>{{ $member->name }}</td>
                                    <td>{{ $member->mobile }}</td>
                                    <td><span class="label label-success">{{ $member->status_label }}</span></td>
                                    <td>{{ optional($member->created_at)->format('Y-m-d') }}</td>
                                    <td>
                                        @if(\Illuminate\Support\Facades\Route::has('myhealth.members.show'))
                                            <a href="{{ route('myhealth.members.show', $member->id) }}" class="btn btn-xs btn-primary">{{ __('View') }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('No members found') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-solid">
                <div class="box-header with-border"><h3 class="box-title">{{ __('Quick Links') }}</h3></div>
                <div class="box-body">
                    @php
                        $links = [
                            ['Members', 'myhealth.members.index'],
                            ['Doctors', 'myhealth.doctors.index'],
                            ['Pharmacy', 'myhealth.pharmacy.dashboard'],
                            ['Laboratory', 'myhealth.labs.index'],
                            ['Insurance', 'myhealth.insurance.dashboard'],
                            ['Telemedicine', 'myhealth.telemedicine.dashboard'],
                            ['Billing & Claims', 'myhealth.billing.dashboard'],
                            ['Reports', 'myhealth.reports.index'],
                            ['Settings', 'myhealth.settings.index'],
                        ];
                    @endphp
                    @foreach($links as [$label, $route])
                        @if(\Illuminate\Support\Facades\Route::has($route))
                            <a class="btn btn-default btn-block text-left" href="{{ route($route) }}"><i class="fa fa-angle-right"></i> {{ __($label) }}</a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
