@php($title = $title ?? 'Beauty Saloons')
<section class="content-header"><h1>{{ $title }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<div class="box box-info"><div class="box-header with-border"><h3 class="box-title">Resource Utilization Report</h3></div>
<div class="box-body">
<div class="row">
 <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-building"></i></span><div class="info-box-content"><span class="info-box-text">Total Branches</span><span class="info-box-number">{{ $summary['total_branches'] ?? 0 }}</span></div></div></div>
 <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">Active Branches</span><span class="info-box-number">{{ $summary['active_branches'] ?? 0 }}</span></div></div></div>
</div>
<p>{{ $summary['message'] ?? '' }}</p>
</div></div>
</section>

