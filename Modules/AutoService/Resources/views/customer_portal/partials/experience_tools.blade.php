@if($selectedJob)
<div class="as-customer-card">
    <div class="as-card-header"><span>Customer Experience Tools</span><span class="label label-primary">Live / Downloads / Callback</span></div>
    <div class="as-card-body">
        <div class="row">
            <div class="col-md-3"><a class="btn btn-default btn-block" target="_blank" href="{{ route('autoservice.customer_experience.job_card', ['job' => $selectedJob->id, 'q' => $keyword]) }}"><i class="fa fa-print"></i> Download Job Card</a></div>
            <div class="col-md-3"><a class="btn btn-default btn-block" target="_blank" href="{{ route('autoservice.customer_experience.inspection_report', ['job' => $selectedJob->id, 'q' => $keyword]) }}"><i class="fa fa-check-square-o"></i> Inspection Report</a></div>
            <div class="col-md-3"><a class="btn btn-default btn-block" target="_blank" href="{{ route('autoservice.customer_experience.warranty_certificate', ['job' => $selectedJob->id, 'q' => $keyword]) }}"><i class="fa fa-certificate"></i> Warranty Certificate</a></div>
            <div class="col-md-3"><button type="button" class="btn btn-info btn-block" id="as-live-refresh"><i class="fa fa-refresh"></i> Refresh Live Status</button></div>
        </div>
        <div id="as-live-status-box" class="alert alert-info" style="margin-top:12px;display:none"></div>
        <hr>
        <form method="post" action="{{ route('autoservice.customer_experience.callback') }}" class="well well-sm">
            @csrf
            <input type="hidden" name="q" value="{{ $keyword }}">
            <input type="hidden" name="job_id" value="{{ $selectedJob->id }}">
            <input type="hidden" name="vehicle_id" value="{{ $selectedVehicle->id }}">
            <div class="row">
                <div class="col-md-3"><label>Your Name</label><input name="customer_name" class="form-control" required></div>
                <div class="col-md-2"><label>Mobile</label><input name="customer_mobile" class="form-control" required></div>
                <div class="col-md-2"><label>Preferred Time</label><input name="preferred_time" class="form-control" placeholder="Today evening"></div>
                <div class="col-md-2"><label>Reason</label><select name="reason" class="form-control"><option value="service_update">Service Update</option><option value="estimate_question">Estimate Question</option><option value="parts_question">Parts Question</option><option value="delivery_time">Delivery Time</option><option value="other">Other</option></select></div>
                <div class="col-md-3"><label>Message</label><input name="message" class="form-control" placeholder="Optional note"></div>
            </div>
            <div class="row" style="margin-top:10px"><div class="col-md-2"><button class="btn btn-success btn-block"><i class="fa fa-phone"></i> Request Callback</button></div></div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('as-live-refresh');
    if(!btn) return;
    btn.addEventListener('click', function(){
        var box = document.getElementById('as-live-status-box');
        box.style.display='block'; box.innerHTML='Checking live status...';
        fetch("{{ route('autoservice.customer_experience.live_progress', ['job_id' => $selectedJob->id, 'q' => $keyword]) }}")
            .then(function(r){return r.json();})
            .then(function(data){ box.innerHTML = '<strong>'+data.job_no+'</strong> - '+data.status_label+' ('+data.progress_percent+'%)<br>Bill Total: '+Number(data.bill_total||0).toFixed(2)+' | Balance: '+Number(data.balance_amount||0).toFixed(2)+'<br>'+ (data.customer_visible_note || ''); })
            .catch(function(){ box.className='alert alert-danger'; box.innerHTML='Unable to refresh live service status.'; });
    });
});
</script>
@endif
