@extends('egg::layouts.app')

@section('title', 'Egg Management Reports')

@section('content')
<div class="egg-page egg-reports-home">
    <div class="egg-page-header">
        <div>
            <div class="egg-page-eyebrow">EGG MANAGEMENT</div>
            <h1>Reports</h1>
            <p>Select the report you want to open.</p>
        </div>
    </div>

    <div class="row">
        @foreach($reports as $report)
            <div class="col-lg-4 col-md-6 col-sm-12" style="margin-bottom:18px;">
                <a href="{{ route($report['route']) }}" class="egg-report-home-card" style="display:block;text-decoration:none;background:#fff;border:1px solid #e7edf5;border-radius:16px;padding:22px;min-height:160px;box-shadow:0 10px 24px rgba(15,23,42,.06);color:#1f2937;">
                    <div style="width:48px;height:48px;border-radius:14px;background:#eef4ff;color:#2f6fed;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:14px;">
                        <i class="{{ $report['icon'] }}"></i>
                    </div>
                    <div style="font-size:17px;font-weight:700;margin-bottom:7px;">{{ $report['label'] }}</div>
                    <div style="font-size:13px;line-height:1.55;color:#77839a;">{{ $report['description'] }}</div>
                    <div style="margin-top:14px;font-weight:700;color:#2f6fed;">Open Report <i class="fa fa-arrow-right" style="margin-left:6px;"></i></div>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection
