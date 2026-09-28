@php($kpis = $data['kpis'] ?? [])
<div class="row">
    @foreach([
        ['Open Alerts', $kpis['open_alerts'] ?? 0, 'fa-bell', 'bg-aqua'],
        ['Critical Alerts', $kpis['critical_alerts'] ?? 0, 'fa-exclamation-triangle', 'bg-red'],
        ['Allergy Alerts', $kpis['allergy_alerts'] ?? 0, 'fa-medkit', 'bg-yellow'],
        ['Medication Alerts', $kpis['medication_alerts'] ?? 0, 'fa-pills', 'bg-purple'],
        ['Abnormal Labs', $kpis['abnormal_lab_alerts'] ?? 0, 'fa-flask', 'bg-orange'],
        ['Radiology Critical', $kpis['critical_radiology_alerts'] ?? 0, 'fa-file-image-o', 'bg-maroon'],
        ['Follow-ups', $kpis['followup_reminders'] ?? 0, 'fa-calendar-check-o', 'bg-green'],
        ['Preventive Care', $kpis['preventive_reminders'] ?? 0, 'fa-heartbeat', 'bg-teal'],
    ] as $card)
        <div class="col-lg-3 col-xs-6">
            <div class="small-box {{ $card[3] }}">
                <div class="inner"><h3>{{ $card[1] }}</h3><p>{{ $card[0] }}</p></div>
                <div class="icon"><i class="fa {{ $card[2] }}"></i></div>
            </div>
        </div>
    @endforeach
</div>
