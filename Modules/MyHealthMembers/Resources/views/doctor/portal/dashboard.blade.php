@extends('layouts.app')

@section('title', __('My Health - Doctor Portal'))

@section('content')
<section class="content-header">
    <h1>{{ __('My Health') }} <small>{{ __('Doctor Portal') }}</small></h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row">
        @php
            $cards = [
                ['label' => 'Today Consultations', 'value' => $summary['today_consultations'] ?? 0, 'icon' => 'fa fa-stethoscope'],
                ['label' => 'Waiting Patients', 'value' => $summary['waiting_patients'] ?? 0, 'icon' => 'fa fa-clock-o'],
                ['label' => 'Open Consultations', 'value' => $summary['open_consultations'] ?? 0, 'icon' => 'fa fa-folder-open'],
                ['label' => 'Follow-ups', 'value' => $summary['follow_ups'] ?? 0, 'icon' => 'fa fa-calendar-check-o'],
                ['label' => 'Prescriptions Today', 'value' => $summary['prescriptions_today'] ?? 0, 'icon' => 'fa fa-medkit'],
                ['label' => 'Lab Requests Today', 'value' => $summary['lab_requests_today'] ?? 0, 'icon' => 'fa fa-flask'],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="col-md-2 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua"><i class="{{ $card['icon'] }}"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ __($card['label']) }}</span>
                        <span class="info-box-number">{{ number_format($card['value']) }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Find Patient') }}</h3>
                </div>
                <div class="box-body">
                    <form method="GET" action="{{ route('myhealth.doctor.portal.dashboard') }}">
                        <div class="input-group">
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('Search by Code, Name, Mobile, NIC') }}">
                            <span class="input-group-btn"><button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button></span>
                        </div>
                    </form>
                    <hr>
                    <div class="list-group">
                        @forelse($members as $member)
                            <a class="list-group-item" href="{{ route('myhealth.doctor.portal.consultation.create', $member->id) }}">
                                <strong>{{ $member->myhealth_code }}</strong> - {{ $member->name }}<br>
                                <small>{{ $member->mobile }} {{ $member->nic_no ? ' | ' . $member->nic_no : '' }}</small>
                            </a>
                        @empty
                            <p class="text-muted">{{ __('Search member to start a consultation.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Today Patient Queue') }}</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Time') }}</th>
                                <th>{{ __('Consultation No') }}</th>
                                <th>{{ __('Patient') }}</th>
                                <th>{{ __('Doctor') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($queue as $consultation)
                                <tr>
                                    <td>{{ $consultation->consultation_time }}</td>
                                    <td>{{ $consultation->consultation_no }}</td>
                                    <td>{{ optional($consultation->member)->name }}</td>
                                    <td>{{ optional($consultation->doctor)->name }}</td>
                                    <td><span class="label label-info">{{ ucfirst(str_replace('_', ' ', $consultation->status)) }}</span></td>
                                    <td>
                                        @if(optional($consultation->member)->id)
                                            <a class="btn btn-xs btn-primary" href="{{ route('myhealth.doctor.portal.consultation.create', $consultation->member_id) }}">{{ __('Open') }}</a>
                                        @endif
                                        @if($consultation->status !== 'completed')
                                            <form method="POST" action="{{ route('myhealth.doctor.portal.consultation.complete', $consultation->id) }}" style="display:inline;">
                                                @csrf
                                                <button class="btn btn-xs btn-success" type="submit">{{ __('Complete') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('No consultations found for today.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="box-footer">{{ $queue->links() }}</div>
            </div>
        </div>
    </div>
</section>
@endsection
