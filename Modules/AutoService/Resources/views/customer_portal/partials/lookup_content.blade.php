<style>
    .as-customer-card{border:1px solid #e6edf5;border-radius:10px;background:#fff;margin-bottom:18px;box-shadow:0 2px 10px rgba(31,45,61,.04)}
    .as-customer-card .as-card-header{padding:14px 16px;border-bottom:1px solid #edf2f7;font-weight:700;color:#23364d;display:flex;justify-content:space-between;align-items:center}
    .as-customer-card .as-card-body{padding:16px}
    .as-kpi{background:#f8fafc;border:1px solid #e6edf5;border-radius:10px;padding:12px;margin-bottom:10px}.as-kpi small{display:block;color:#718096}.as-kpi strong{font-size:18px;color:#1f2d3d}
    .auto-service-progress{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0 18px}.as-step{padding:8px 12px;border-radius:18px;background:#edf2f7;color:#4a5568;font-size:12px}.as-step.done{background:#dff4e6;color:#1f7a3f}.as-step.active{background:#d9edf7;color:#1f5f8b;font-weight:700}.as-step.cancelled{background:#fde2e2;color:#9b2c2c}
    .as-table-wrap{overflow-x:auto}.as-money{text-align:right;white-space:nowrap}.as-muted{color:#718096}.as-current-bill-total{font-size:22px;font-weight:800;color:#1f2d3d}
</style>

<div class="as-customer-card">
    <div class="as-card-header">
        <span><i class="fa fa-car"></i> Auto Service Customer Portal</span>
        @if(!empty($publicMode))<span class="label label-primary">Customer View</span>@endif
    </div>
    <div class="as-card-body">
        <form method="get" action="{{ url()->current() }}">
            <div class="row">
                <div class="col-md-6">
                    <label>Job No / Vehicle No / Mobile No / Customer Name</label>
                    <input name="q" class="form-control" value="{{ $keyword ?? request('q') }}" placeholder="Enter job number, vehicle number, or registered mobile number">
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary form-control"><i class="fa fa-search"></i> View Status</button>
                </div>
            </div>
        </form>

        @if(session('status'))<hr><div class="alert alert-success">{{ session('status') }}</div>@endif
        @if($errors->any())<hr><div class="alert alert-danger"><ul style="margin-bottom:0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @if(!empty($keyword) && !$selectedJob && !$selectedVehicle)
            <hr><div class="alert alert-warning">No vehicle/job found for the entered details.</div>
        @endif
    </div>
</div>

@if($selectedVehicle)
    <div class="row">
        <div class="col-md-3"><div class="as-kpi"><small>Vehicle</small><strong>{{ $selectedVehicle->registration_no }}</strong><div class="as-muted">{{ trim(($selectedVehicle->make ?? '').' '.($selectedVehicle->model ?? '')) }}</div></div></div>
        <div class="col-md-3"><div class="as-kpi"><small>Current Status</small><strong>{{ $selectedJob ? ($statusSteps[$selectedJob->status] ?? ucwords(str_replace('_',' ', $selectedJob->status))) : 'No active job' }}</strong><div class="as-muted">{{ $selectedJob->job_no ?? 'History only' }}</div></div></div>
        <div class="col-md-3"><div class="as-kpi"><small>Estimated Delivery</small><strong>{{ $selectedJob->estimated_delivery_at ?? $selectedJob->estimated_completion_at ?? '-' }}</strong><div class="as-muted">Current service timeline</div></div></div>
        <div class="col-md-3"><div class="as-kpi"><small>Current Balance</small><strong>{{ number_format((float)($currentBill['balance_amount'] ?? $selectedJob->balance_amount ?? 0), 2) }}</strong><div class="as-muted">Current bill balance</div></div></div>
    </div>

    <div class="as-customer-card">
        <div class="as-card-header"><span>Vehicle & Current Service Status</span></div>
        <div class="as-card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered table-condensed">
                        <tr><th style="width:180px">Vehicle No</th><td>{{ $selectedVehicle->registration_no }}</td></tr>
                        <tr><th>Make / Model</th><td>{{ $selectedVehicle->make }} {{ $selectedVehicle->model }}</td></tr>
                        <tr><th>VIN</th><td>{{ $selectedVehicle->vin }}</td></tr>
                        <tr><th>Chassis No</th><td>{{ $selectedVehicle->chassis_no }}</td></tr>
                        <tr><th>Engine No</th><td>{{ $selectedVehicle->engine_no }}</td></tr>
                        <tr><th>Current Odometer</th><td>{{ number_format((float)$selectedVehicle->current_odometer, 0) }}</td></tr>
                        <tr><th>Next Service</th><td>{{ $selectedVehicle->next_service_date }} {{ $selectedVehicle->next_service_odometer ? ' / '.number_format((float)$selectedVehicle->next_service_odometer,0).' km' : '' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    @if($selectedJob)
                        <table class="table table-bordered table-condensed">
                            <tr><th style="width:180px">Current Job</th><td><strong>{{ $selectedJob->job_no }}</strong></td></tr>
                            <tr><th>Job Date</th><td>{{ $selectedJob->job_date }}</td></tr>
                            <tr><th>Status</th><td><span class="label label-info">{{ $statusSteps[$selectedJob->status] ?? ucwords(str_replace('_',' ', $selectedJob->status)) }}</span></td></tr>
                            <tr><th>Workflow Stage</th><td>{{ ucwords(str_replace('_',' ', $selectedJob->workflow_stage ?? $selectedJob->status)) }}</td></tr>
                            <tr><th>Customer Note</th><td>{{ $selectedJob->customer_visible_note ?? '-' }}</td></tr>
                            <tr><th>Complaint</th><td>{{ $selectedJob->customer_complaint ?? '-' }}</td></tr>
                        </table>
                    @else
                        <div class="alert alert-info">No currently active job was found for this vehicle. Please check the history section below.</div>
                    @endif
                </div>
            </div>

            @if($selectedJob)
                @php $passed = true; @endphp
                <h4>Current Service Progress</h4>
                <div class="auto-service-progress">
                    @foreach($statusSteps as $key => $label)
                        @php
                            $active = $selectedJob->status === $key;
                            $done = $passed && !$active && $selectedJob->status !== 'cancelled';
                            if ($active) { $passed = false; }
                        @endphp
                        <span class="as-step {{ $active ? 'active' : ($done ? 'done' : '') }}">{{ $label }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>


    @include('autoservice::customer_portal.partials.experience_tools')

    @if(!empty($portalSettings['allow_customer_appointment_request']))
        <div class="as-customer-card">
            <div class="as-card-header"><span>Request Next Appointment</span><span class="label label-success">Customer Self Service</span></div>
            <div class="as-card-body">
                <form method="post" action="{{ route('autoservice.customer_portal.appointment_request.store') }}" class="well well-sm">
                    @csrf
                    <input type="hidden" name="vehicle_id" value="{{ $selectedVehicle->id }}">
                    <input type="hidden" name="registration_no" value="{{ $selectedVehicle->registration_no }}">
                    <div class="row">
                        <div class="col-md-3"><label>Name</label><input name="customer_name" class="form-control" value="{{ old('customer_name') }}" required></div>
                        <div class="col-md-2"><label>Mobile</label><input name="customer_mobile" class="form-control" value="{{ old('customer_mobile') }}" required></div>
                        <div class="col-md-3"><label>Email</label><input type="email" name="customer_email" class="form-control" value="{{ old('customer_email') }}"></div>
                        <div class="col-md-2"><label>Preferred Date</label><input type="date" name="preferred_date" class="form-control" value="{{ old('preferred_date') }}" required></div>
                        <div class="col-md-2"><label>Preferred Time</label><input type="time" name="preferred_time" class="form-control" value="{{ old('preferred_time') }}"></div>
                    </div>
                    <div class="row" style="margin-top:10px">
                        <div class="col-md-3"><label>Service Type</label><input name="service_type" class="form-control" placeholder="Periodic service / repair / inspection" value="{{ old('service_type') }}"></div>
                        <div class="col-md-7"><label>Customer Note</label><textarea name="customer_note" class="form-control" rows="2" placeholder="Explain the issue or service requirement">{{ old('customer_note') }}</textarea></div>
                        <div class="col-md-2"><label>&nbsp;</label><button class="btn btn-success form-control"><i class="fa fa-calendar-plus-o"></i> Request</button></div>
                    </div>
                </form>
                @if(isset($portalAppointments) && $portalAppointments->count())
                    <h4>Recent Appointment Requests</h4>
                    <div class="as-table-wrap"><table class="table table-bordered table-condensed"><thead><tr><th>Appointment No</th><th>Date/Time</th><th>Service Type</th><th>Status</th><th>Note</th></tr></thead><tbody>@foreach($portalAppointments as $appointment)<tr><td>{{ $appointment->appointment_no }}</td><td>{{ $appointment->appointment_at }}</td><td>{{ $appointment->service_type }}</td><td>{{ ucwords(str_replace('_',' ', $appointment->status)) }}</td><td>{{ $appointment->customer_note }}</td></tr>@endforeach</tbody></table></div>
                @endif
            </div>
        </div>
    @endif

    <div class="as-customer-card">
        <div class="as-card-header">
            <span>Current Bill</span>
            <span>
                @if(!empty($currentBill))<span class="label label-default">{{ $currentBill['title'] }}</span>@endif
                <a class="btn btn-xs btn-default" target="_blank" href="{{ route('autoservice.customer_portal.service_summary.print', request()->query()) }}"><i class="fa fa-print"></i> Print Summary</a>
                <a class="btn btn-xs btn-info" href="{{ route('autoservice.customer_portal.payment_history', ['q' => $keyword]) }}"><i class="fa fa-credit-card"></i> Payment History</a>
            </span>
        </div>
        <div class="as-card-body">
            @if(!$canViewCurrentInvoice && $selectedJob)
                <div class="alert alert-info">The business has not enabled current invoice viewing. The portal shows current job estimated bill lines and previous invoices where available.</div>
            @endif
            @if(!empty($currentBill))
                <div class="row">
                    <div class="col-md-3"><div class="as-kpi"><small>Reference</small><strong>{{ $currentBill['number'] }}</strong><div class="as-muted">{{ $currentBill['date'] }}</div></div></div>
                    <div class="col-md-3"><div class="as-kpi"><small>Total Bill</small><strong>{{ number_format((float)$currentBill['total_amount'], 2) }}</strong><div class="as-muted">Before payments</div></div></div>
                    <div class="col-md-3"><div class="as-kpi"><small>Paid</small><strong>{{ number_format((float)$currentBill['paid_amount'], 2) }}</strong><div class="as-muted">Recorded payments</div></div></div>
                    <div class="col-md-3"><div class="as-kpi"><small>Balance</small><span class="as-current-bill-total">{{ number_format((float)$currentBill['balance_amount'], 2) }}</span><div class="as-muted">To be settled</div></div></div>
                </div>
                <div class="as-table-wrap">
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>Type</th><th>Description</th><th class="as-money">Qty</th><th class="as-money">Unit Price</th><th class="as-money">Discount</th><th class="as-money">Tax</th><th class="as-money">Total</th></tr></thead>
                        <tbody>
                        @forelse($currentBill['lines'] as $line)
                            <tr>
                                <td>{{ ucwords(str_replace('_',' ', $line->line_type)) }}</td>
                                <td>{{ $line->description }}</td>
                                <td class="as-money">{{ number_format((float)$line->quantity, 2) }}</td>
                                <td class="as-money">{{ number_format((float)$line->unit_price, 2) }}</td>
                                <td class="as-money">{{ number_format((float)$line->discount_amount, 2) }}</td>
                                <td class="as-money">{{ number_format((float)$line->tax_amount, 2) }}</td>
                                <td class="as-money">{{ number_format((float)$line->line_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center">No current bill line details found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-warning">No current bill found for this lookup.</div>
            @endif
        </div>
    </div>

    <div class="as-customer-card">
        <div class="as-card-header"><span>Parts & Accessories Used</span><span>{{ $partsHistory->count() }} record(s) <a class="btn btn-xs btn-success" href="{{ route('autoservice.customer_portal.parts_history.export', request()->query()) }}"><i class="fa fa-download"></i> Export CSV</a></span></div>
        <div class="as-card-body">
            <form method="get" action="{{ url()->current() }}" class="well well-sm">
                <input type="hidden" name="q" value="{{ $keyword }}">
                <div class="row">
                    <div class="col-md-3"><label>Search Part / Accessory</label><input class="form-control" name="part_q" value="{{ $partFilters['part_q'] ?? '' }}" placeholder="Part name, accessory, job no"></div>
                    <div class="col-md-2"><label>Job No</label><input class="form-control" name="job_no" value="{{ $partFilters['job_no'] ?? '' }}"></div>
                    <div class="col-md-2"><label>From Date</label><input type="date" class="form-control" name="date_from" value="{{ $partFilters['date_from'] ?? '' }}"></div>
                    <div class="col-md-2"><label>To Date</label><input type="date" class="form-control" name="date_to" value="{{ $partFilters['date_to'] ?? '' }}"></div>
                    <div class="col-md-3"><label>&nbsp;</label><div class="btn-group btn-block"><button class="btn btn-primary" style="width:70%"><i class="fa fa-filter"></i> Filter Parts</button><a class="btn btn-default" style="width:30%" href="{{ url()->current() }}?q={{ urlencode($keyword) }}">Reset</a></div></div>
                </div>
            </form>
            <div class="row">
                <div class="col-md-3"><div class="as-kpi"><small>Total Qty</small><strong>{{ number_format((float)$partsSummary['qty'], 2) }}</strong></div></div>
                <div class="col-md-3"><div class="as-kpi"><small>Gross Value</small><strong>{{ number_format((float)$partsSummary['subtotal'], 2) }}</strong></div></div>
                <div class="col-md-3"><div class="as-kpi"><small>Discount</small><strong>{{ number_format((float)$partsSummary['discount'], 2) }}</strong></div></div>
                <div class="col-md-3"><div class="as-kpi"><small>Net Total</small><strong>{{ number_format((float)$partsSummary['total'], 2) }}</strong></div></div>
            </div>
            <div class="as-table-wrap">
                <table class="table table-bordered table-striped table-condensed">
                    <thead>
                        <tr>
                            <th>Date</th><th>Job No</th><th>Reference</th><th>Type</th><th>Part / Accessory</th><th class="as-money">Qty</th><th class="as-money">Unit Price</th><th class="as-money">Discount</th><th class="as-money">Tax</th><th class="as-money">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($partsHistory as $part)
                            <tr>
                                <td>{{ $part->used_date }}</td>
                                <td>{{ $part->job_no }}</td>
                                <td>{{ $part->reference_no }}</td>
                                <td>{{ ucwords(str_replace('_',' ', $part->line_type)) }}</td>
                                <td>{{ $part->description }}</td>
                                <td class="as-money">{{ number_format((float)$part->quantity, 2) }}</td>
                                <td class="as-money">{{ number_format((float)$part->unit_price, 2) }}</td>
                                <td class="as-money">{{ number_format((float)$part->discount_amount, 2) }}</td>
                                <td class="as-money">{{ number_format((float)$part->tax_amount, 2) }}</td>
                                <td class="as-money">{{ number_format((float)$part->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center">No parts/accessories found for the selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="as-customer-card">
        <div class="as-card-header"><span>Past Service History</span></div>
        <div class="as-card-body as-table-wrap">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Job No</th><th>Date</th><th>Vehicle</th><th>Status</th><th>Odometer</th><th class="as-money">Total</th><th class="as-money">Paid</th><th class="as-money">Balance</th><th>Next Service</th></tr></thead>
                <tbody>
                    @forelse($serviceHistory as $job)
                        <tr>
                            <td><strong>{{ $job->job_no }}</strong></td>
                            <td>{{ $job->job_date }}</td>
                            <td>{{ optional($customerVehicles->firstWhere('id', $job->vehicle_id))->registration_no ?? $selectedVehicle->registration_no }}</td>
                            <td>{{ $statusSteps[$job->status] ?? ucwords(str_replace('_',' ', $job->status)) }}</td>
                            <td>{{ number_format((float)$job->odometer, 0) }}</td>
                            <td class="as-money">{{ number_format((float)$job->total_amount, 2) }}</td>
                            <td class="as-money">{{ number_format((float)$job->paid_amount, 2) }}</td>
                            <td class="as-money">{{ number_format((float)$job->balance_amount, 2) }}</td>
                            <td>{{ $job->next_service_date }} {{ $job->next_service_odometer ? ' / '.number_format((float)$job->next_service_odometer,0).' km' : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">No service history found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="as-customer-card">
        <div class="as-card-header"><span>Invoices & Payment History</span></div>
        <div class="as-card-body as-table-wrap">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Invoice No</th><th>Date</th><th>Job No</th><th>Status</th><th class="as-money">Total</th><th class="as-money">Paid</th><th class="as-money">Balance</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td><strong>{{ $invoice->invoice_no }}</strong></td>
                            <td>{{ $invoice->invoice_date }}</td>
                            <td>{{ optional($invoice->job)->job_no }}</td>
                            <td>{{ ucwords(str_replace('_',' ', $invoice->status)) }}</td>
                            <td class="as-money">{{ number_format((float)$invoice->total_amount, 2) }}</td>
                            <td class="as-money">{{ number_format((float)$invoice->paid_amount, 2) }}</td>
                            <td class="as-money">{{ number_format((float)$invoice->balance_amount, 2) }}</td>
                            <td><a class="btn btn-xs btn-primary" href="{{ route('autoservice.customer_portal.invoices.show', ['invoice' => $invoice->id, 'q' => $keyword]) }}">View Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No invoice found for this customer/vehicle.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(isset($approvals) && $approvals->count())
        <div class="as-customer-card"><div class="as-card-header"><span>Customer Approval Requests</span><span class="label label-warning">Action Required</span></div><div class="as-card-body as-table-wrap">
            <table class="table table-bordered"><thead><tr><th>No</th><th>Request</th><th class="as-money">Amount</th><th>Status</th><th>Requested</th><th>Customer Response</th></tr></thead><tbody>@foreach($approvals as $approval)<tr><td>{{ $approval->approval_no }}</td><td>{{ $approval->title }}<br><small>{{ $approval->description }}</small></td><td class="as-money">{{ number_format((float)$approval->amount, 2) }}</td><td>{{ ucfirst($approval->status) }}</td><td>{{ $approval->requested_at }}</td><td>@if($approval->status === 'pending')<form method="post" action="{{ route('autoservice.customer_portal.approvals.respond', ['approval' => $approval->id, 'q' => $keyword]) }}" style="min-width:260px">@csrf<input type="hidden" name="q" value="{{ $keyword }}"><textarea name="customer_note" class="form-control input-sm" rows="1" placeholder="Optional note"></textarea><div class="btn-group btn-group-xs" style="margin-top:5px"><button name="response" value="approved" class="btn btn-success"><i class="fa fa-check"></i> Approve</button><button name="response" value="declined" class="btn btn-danger"><i class="fa fa-times"></i> Decline</button></div></form>@else<span class="as-muted">Responded {{ $approval->responded_at }}</span><br><small>{{ $approval->customer_note }}</small>@endif</td></tr>@endforeach</tbody></table>
        </div></div>
    @endif

    @if(isset($documents) && $documents->count())
        <div class="as-customer-card"><div class="as-card-header"><span>Photos / Documents Shared With Customer</span></div><div class="as-card-body as-table-wrap">
            <table class="table table-bordered"><thead><tr><th>Type</th><th>Title</th><th>File</th><th>Notes</th></tr></thead><tbody>@foreach($documents as $doc)<tr><td>{{ ucwords(str_replace('_',' ', $doc->document_type)) }}</td><td>{{ $doc->title }}</td><td>@if($doc->file_path)<a target="_blank" href="{{ route('autoservice.customer_portal.documents.show', ['document' => $doc->id, 'q' => $keyword]) }}">View</a>@endif</td><td>{{ $doc->notes }}</td></tr>@endforeach</tbody></table>
        </div></div>
    @endif


    @if(!empty($portalSettings['allow_customer_document_upload']))
        <div class="as-customer-card">
            <div class="as-card-header"><span>Upload Service Photos / Documents</span><span class="label label-info">Customer Upload</span></div>
            <div class="as-card-body">
                <form method="post" action="{{ route('autoservice.customer_portal.documents.store') }}" enctype="multipart/form-data" class="well well-sm">
                    @csrf
                    <input type="hidden" name="q" value="{{ $keyword }}">
                    <input type="hidden" name="vehicle_id" value="{{ $selectedVehicle->id }}">
                    <input type="hidden" name="job_id" value="{{ $selectedJob->id ?? '' }}">
                    <div class="row">
                        <div class="col-md-2"><label>Document Type</label><select name="document_type" class="form-control" required><option value="vehicle_photo">Vehicle Photo</option><option value="damage_photo">Damage Photo</option><option value="payment_slip">Payment Slip</option><option value="registration_book">Registration Book</option><option value="insurance">Insurance</option><option value="other">Other</option></select></div>
                        <div class="col-md-3"><label>Title</label><input name="title" class="form-control" placeholder="Short title"></div>
                        <div class="col-md-2"><label>Your Name</label><input name="customer_name" class="form-control"></div>
                        <div class="col-md-2"><label>Mobile</label><input name="customer_mobile" class="form-control"></div>
                        <div class="col-md-3"><label>File</label><input type="file" name="document_file" class="form-control" required><small class="as-muted">JPG, PNG, WEBP, PDF, DOC/DOCX. Max 10MB.</small></div>
                    </div>
                    <div class="row" style="margin-top:10px">
                        <div class="col-md-10"><label>Note</label><textarea name="notes" class="form-control" rows="2" placeholder="Add any details for the service advisor"></textarea></div>
                        <div class="col-md-2"><label>&nbsp;</label><button class="btn btn-primary form-control"><i class="fa fa-upload"></i> Upload</button></div>
                    </div>
                </form>
            </div>
        </div>
    @endif



    @if(isset($portalAlerts) && $portalAlerts->count())
        <div class="as-customer-card">
            <div class="as-card-header"><span>Customer Portal Alerts</span><span class="label label-primary">Live Updates</span></div>
            <div class="as-card-body as-table-wrap">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Date/Time</th><th>Priority</th><th>Status</th><th>Alert</th><th>Message</th><th>Action</th></tr></thead>
                    <tbody>
                    @foreach($portalAlerts as $alert)
                        <tr>
                            <td>{{ $alert->created_at }}</td>
                            <td><span class="label {{ ($alert->priority ?? 'normal') === 'high' ? 'label-danger' : 'label-info' }}">{{ ucfirst($alert->priority ?? 'normal') }}</span></td>
                            <td>{{ ucwords(str_replace('_',' ', $alert->status ?? 'new')) }}</td>
                            <td><strong>{{ $alert->title }}</strong><br><small>{{ ucwords(str_replace('_',' ', $alert->event_type ?? 'update')) }}</small></td>
                            <td>{{ $alert->message }}</td>
                            <td>
                                @if(empty($alert->acknowledged_at))
                                    <form method="post" action="{{ route('autoservice.customer_portal.alerts.acknowledge', ['alert' => $alert->id, 'q' => $keyword]) }}">
                                        @csrf
                                        <input type="hidden" name="q" value="{{ $keyword }}">
                                        <button class="btn btn-xs btn-success"><i class="fa fa-check"></i> Mark Read</button>
                                    </form>
                                @else
                                    <span class="as-muted">Read {{ $alert->acknowledged_at }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if(isset($portalCommunicationLog) && $portalCommunicationLog->count())
        <div class="as-customer-card">
            <div class="as-card-header"><span>Communication Log</span><span class="label label-default">SMS / Email / WhatsApp / In-App</span></div>
            <div class="as-card-body as-table-wrap">
                <table class="table table-bordered table-condensed">
                    <thead><tr><th>Date/Time</th><th>Channel</th><th>Event</th><th>Recipient</th><th>Status</th><th>Message</th></tr></thead>
                    <tbody>
                    @foreach($portalCommunicationLog as $log)
                        <tr>
                            <td>{{ $log->created_at }}</td>
                            <td>{{ strtoupper($log->channel ?? '-') }}</td>
                            <td>{{ ucwords(str_replace('_',' ', $log->event ?? '-')) }}</td>
                            <td>{{ $log->recipient ?? '-' }}</td>
                            <td>{{ ucwords(str_replace('_',' ', $log->status ?? '-')) }}</td>
                            <td>{{ $log->message ?? '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($timeline->count())
        <div class="as-customer-card"><div class="as-card-header"><span>Service Timeline</span></div><div class="as-card-body as-table-wrap">
            <table class="table table-bordered"><thead><tr><th>Date/Time</th><th>Event</th><th>Description</th></tr></thead><tbody>@foreach($timeline as $item)<tr><td>{{ $item->event_at }}</td><td>{{ $item->title }}</td><td>{{ $item->description }}</td></tr>@endforeach</tbody></table>
        </div></div>
    @endif

    @if(!empty($portalSettings['allow_customer_feedback']))
        <div class="as-customer-card"><div class="as-card-header"><span>Customer Feedback</span></div><div class="as-card-body">
            @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
            <form method="post" action="{{ route('autoservice.customer_portal.feedback.store') }}" class="well">
                @csrf
                <input type="hidden" name="vehicle_id" value="{{ $selectedVehicle->id }}">
                <input type="hidden" name="job_id" value="{{ $selectedJob->id ?? '' }}">
                <div class="row">
                    <div class="col-md-2"><label>Service Rating</label><select name="service_rating" class="form-control"><option value="">Please Select</option>@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }}</option>@endfor</select></div>
                    <div class="col-md-2"><label>Mechanic Rating</label><select name="mechanic_rating" class="form-control"><option value="">Please Select</option>@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }}</option>@endfor</select></div>
                    <div class="col-md-2"><label>Workshop Rating</label><select name="workshop_rating" class="form-control"><option value="">Please Select</option>@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }}</option>@endfor</select></div>
                    <div class="col-md-3"><label>Your Name</label><input name="customer_name" class="form-control"></div>
                    <div class="col-md-3"><label>Mobile</label><input name="customer_mobile" class="form-control"></div>
                </div>
                <div class="row" style="margin-top:10px"><div class="col-md-10"><label>Comments</label><textarea name="comments" class="form-control" rows="2"></textarea></div><div class="col-md-2"><label>&nbsp;</label><button class="btn btn-success form-control">Submit Feedback</button></div></div>
            </form>
        </div></div>
    @endif

    @if($reminders->count())
        <div class="as-customer-card"><div class="as-card-header"><span>Service Reminders</span></div><div class="as-card-body as-table-wrap">
            <table class="table table-bordered"><thead><tr><th>Due Date</th><th>Send On</th><th>Channel</th><th>Status</th></tr></thead><tbody>@foreach($reminders as $r)<tr><td>{{ $r->due_date }}</td><td>{{ $r->send_on }}</td><td>{{ strtoupper($r->channel) }}</td><td>{{ ucfirst($r->status) }}</td></tr>@endforeach</tbody></table>
        </div></div>
    @endif
@endif
