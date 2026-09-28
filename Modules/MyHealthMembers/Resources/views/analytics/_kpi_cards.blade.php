@php($kpis = $analytics['kpis'] ?? [])
<div class="row">
    @foreach([
        ['Total Members', 'total_members', 'fa-users'],
        ['New Members', 'new_members', 'fa-user-plus'],
        ['Consultations', 'consultations', 'fa-stethoscope'],
        ['Lab Requests', 'lab_requests', 'fa-flask'],
        ['Radiology', 'radiology_requests', 'fa-file-image-o'],
        ['Surgeries', 'surgeries', 'fa-hospital-o'],
        ['Vaccinations', 'vaccinations', 'fa-shield'],
        ['Insurance Claims', 'insurance_claims', 'fa-file-text-o'],
        ['Invoices', 'invoices', 'fa-file-text'],
        ['Revenue', 'revenue', 'fa-money'],
    ] as $card)
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa {{ $card[2] }}"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ $card[0] }}</span>
                    <span class="info-box-number">
                        @if($card[1] === 'revenue')
                            {{ number_format((float)($kpis[$card[1]] ?? 0), 2) }}
                        @else
                            {{ number_format((int)($kpis[$card[1]] ?? 0)) }}
                        @endif
                    </span>
                </div>
            </div>
        </div>
    @endforeach
</div>
