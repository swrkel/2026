@extends('distributionnew::layouts.app')
@section('content')
<div class="pos-module-card disnew-scanner-page">
  <div class="pos-page-header"><h4>Barcode / QR Labels</h4><p>Create and manage barcode/QR labels for distribution stock.</p></div>
  <div class="row g-3">
    <div class="col-md-3"><div class="pos-stat-card"><span>Scans Today</span><strong>0</strong></div></div>
    <div class="col-md-3"><div class="pos-stat-card"><span>Accepted</span><strong>0</strong></div></div>
    <div class="col-md-3"><div class="pos-stat-card"><span>Exceptions</span><strong>0</strong></div></div>
    <div class="col-md-3"><div class="pos-stat-card"><span>Pending Sync</span><strong>0</strong></div></div>
  </div>
  <div class="card mt-3"><div class="card-body">
    <div class="form-group"><label>Scan Barcode / QR</label><input class="form-control disnew-scan-input" placeholder="Scan or type barcode and press Enter"></div>
    <div class="table-responsive mt-3"><table class="table table-bordered table-striped disnew-scanner-table"><thead><tr><th>Time</th><th>Barcode</th><th>Product</th><th>Qty</th><th>Status</th></tr></thead><tbody></tbody></table></div>
  </div></div>
</div>
@endsection
