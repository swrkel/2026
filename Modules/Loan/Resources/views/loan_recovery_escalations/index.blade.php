@extends('layouts.app')

@section('title', 'Enterprise Recovery Escalation Dashboard')

@section('content')

<style>

    .erp-page-title {

        font-size: 30px;

        font-weight: 700;

        margin-bottom: 25px;

        color: #2c3e50;
    }

    .erp-top-grid {

        display: grid;

        grid-template-columns:
            repeat(auto-fit, minmax(240px, 1fr));

        gap: 20px;

        margin-bottom: 30px;
    }

    .erp-metric-card {

        background: #fff;

        border-radius: 14px;

        padding: 24px;

        box-shadow:
            0 2px 10px rgba(0,0,0,0.08);
    }

    .erp-metric-title {

        font-size: 14px;

        color: #7f8c8d;

        margin-bottom: 10px;

        font-weight: 600;
    }

    .erp-metric-value {

        font-size: 34px;

        font-weight: 700;
    }

    .metric-danger {
        color: #e74c3c;
    }

    .metric-warning {
        color: #f39c12;
    }

    .metric-primary {
        color: #2980b9;
    }

    .metric-success {
        color: #27ae60;
    }

    .erp-card {

        background: #fff;

        border-radius: 14px;

        padding: 24px;

        box-shadow:
            0 2px 10px rgba(0,0,0,0.08);

        margin-bottom: 30px;
    }

    .erp-section-title {

        font-size: 24px;

        font-weight: 700;

        margin-bottom: 20px;
    }

    .erp-banner {

        background: #243447;

        color: #fff;

        border-radius: 12px;

        padding: 18px 22px;

        margin-bottom: 25px;

        display: flex;

        justify-content: space-between;

        align-items: center;

        flex-wrap: wrap;
    }

    .erp-banner h3 {

        margin: 0;

        font-size: 20px;
    }

    .erp-badge {

        display: inline-block;

        padding: 7px 14px;

        border-radius: 30px;

        font-size: 11px;

        font-weight: 700;

        color: #fff;
    }

    .badge-legal {
        background: #e74c3c;
    }

    .badge-critical {
        background: #e67e22;
    }

    .badge-warning {
        background: #f39c12;
    }

    .badge-normal {
        background: #3498db;
    }

    .badge-branch {
        background: #16a085;
    }

    .badge-ho {
        background: #8e44ad;
    }

    .badge-open {
        background: #27ae60;
    }

    .badge-closed {
        background: #7f8c8d;
    }

    .erp-alert {

        padding: 14px 18px;

        border-radius: 10px;

        margin-bottom: 20px;

        font-weight: 600;
    }

    .erp-alert-success {

        background: #d4edda;

        color: #155724;
    }

    .erp-alert-error {

        background: #f8d7da;

        color: #721c24;
    }

    .erp-table-responsive {

        overflow-x: auto;
    }

    table {

        width: 100%;

        border-collapse: collapse;

        min-width: 1600px;
    }

    table thead {

        background: #243447;

        color: #fff;
    }

    table th,
    table td {

        padding: 14px;

        border: 1px solid #ecf0f1;

        text-align: left;

        vertical-align: top;

        font-size: 14px;
    }

    table tbody tr:nth-child(even) {

        background: #fafafa;
    }

    .erp-summary-grid {

        display: grid;

        grid-template-columns:
            repeat(auto-fit, minmax(300px, 1fr));

        gap: 20px;
    }

    .erp-summary-box {

        background: #f8fafc;

        border-radius: 12px;

        border: 1px solid #ecf0f1;

        padding: 18px;
    }

    .erp-summary-title {

        font-size: 18px;

        font-weight: 700;

        margin-bottom: 15px;
    }

    .erp-summary-stat {

        margin-bottom: 8px;

        font-size: 14px;
    }

    .erp-sla-danger {

        color: #e74c3c;

        font-weight: 700;
    }

    .erp-sla-warning {

        color: #f39c12;

        font-weight: 700;
    }

    .erp-form-grid {

        display: grid;

        grid-template-columns:
            repeat(auto-fit, minmax(300px, 1fr));

        gap: 20px;
    }

    input,
    select,
    textarea {

        width: 100%;

        border: 1px solid #dcdde1;

        border-radius: 8px;

        padding: 10px;

        font-size: 14px;

        margin-top: 5px;
    }

    textarea {

        min-height: 100px;

        resize: vertical;
    }

    .erp-btn {

        background: #243447;

        color: #fff;

        border: none;

        padding: 12px 20px;

        border-radius: 8px;

        font-weight: 700;

        cursor: pointer;
    }

    .erp-btn:hover {

        opacity: 0.9;
    }

    @media(max-width: 768px) {

        table {
            min-width: 1300px;
        }

        .erp-page-title {
            font-size: 24px;
        }

        .erp-section-title {
            font-size: 20px;
        }
    }

</style>

<div class="container-fluid">

    <div class="erp-page-title">
        Enterprise Recovery Escalation Dashboard
    </div>

    <div class="erp-banner">

        <h3>
            Multi-Branch Escalation Governance Engine
        </h3>

        <div>

            @if($isHeadOfficeUser)

                <span class="erp-badge badge-ho">
                    HEAD OFFICE ACCESS
                </span>

            @else

                <span class="erp-badge badge-branch">
                    BRANCH RESTRICTED ACCESS
                </span>

            @endif

        </div>

    </div>

    @if(session('success'))

        <div class="erp-alert erp-alert-success">
            {{ session('success') }}
        </div>

    @endif

    @if(session('error'))

        <div class="erp-alert erp-alert-error">
            {{ session('error') }}
        </div>

    @endif

    @if($errors->any())

        <div class="erp-alert erp-alert-error">

            <ul style="margin:0;padding-left:20px;">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif

    <div class="erp-top-grid">

        <div class="erp-metric-card">

            <div class="erp-metric-title">
                Total Escalations
            </div>

            <div class="erp-metric-value metric-primary">
                {{ $total_escalations }}
            </div>

        </div>

        <div class="erp-metric-card">

            <div class="erp-metric-title">
                Open Escalations
            </div>

            <div class="erp-metric-value metric-success">
                {{ $open_escalations }}
            </div>

        </div>

        <div class="erp-metric-card">

            <div class="erp-metric-title">
                Critical Escalations
            </div>

            <div class="erp-metric-value metric-danger">
                {{ $critical_escalations }}
            </div>

        </div>

        <div class="erp-metric-card">

            <div class="erp-metric-title">
                Legal Escalations
            </div>

            <div class="erp-metric-value metric-warning">
                {{ $legal_escalations }}
            </div>

        </div>

    </div>

    <div class="erp-card">

        <div class="erp-section-title">
            Branch Escalation Summary
        </div>

        <div class="erp-summary-grid">

            @foreach($branchEscalationSummary as $summary)

                <div class="erp-summary-box">

                    <div class="erp-summary-title">

                        {{ $summary->branch_name ?? 'Unknown Branch' }}

                    </div>

                    <div class="erp-summary-stat">

                        Total Escalations:
                        <strong>
                            {{ $summary->total_escalations }}
                        </strong>

                    </div>

                    <div class="erp-summary-stat">

                        Critical Cases:
                        <strong class="erp-sla-danger">
                            {{ $summary->critical_cases }}
                        </strong>

                    </div>

                    <div class="erp-summary-stat">

                        Legal Cases:
                        <strong class="erp-sla-warning">
                            {{ $summary->legal_cases }}
                        </strong>

                    </div>

                </div>

            @endforeach

        </div>

    </div>

    <div class="erp-card">

        <div class="erp-section-title">
            Create Recovery Escalation
        </div>

        <form method="POST"
              action="{{ route('loan.recovery.escalation.store') }}">

            @csrf

            <div class="erp-form-grid">

                <div>

                    <label>
                        Loan ID
                    </label>

                    <input type="number"
                           name="loan_id"
                           required>

                </div>

                <div>

                    <label>
                        Escalation Type
                    </label>

                    <select name="escalation_type"
                            required>

                        <option value="">
                            Select Type
                        </option>

                        <option value="collections">
                            Collections
                        </option>

                        <option value="legal">
                            Legal
                        </option>

                        <option value="field_visit">
                            Field Visit
                        </option>

                    </select>

                </div>

                <div>

                    <label>
                        Priority Level
                    </label>

                    <select name="priority_level"
                            required>

                        <option value="">
                            Select Priority
                        </option>

                        <option value="normal">
                            Normal
                        </option>

                        <option value="high">
                            High
                        </option>

                        <option value="critical">
                            Critical
                        </option>

                    </select>

                </div>

                <div>

                    <label>
                        Assigned Officer
                    </label>

                    <input type="number"
                           name="assigned_to">

                </div>

            </div>

            <div style="margin-top:20px;">

                <label>
                    Escalation Notes
                </label>

                <textarea name="notes"
                          required></textarea>

            </div>

            <div style="margin-top:20px;">

                <label style="margin-right:20px;">

                    <input type="checkbox"
                           name="legal_action_required"
                           value="1">

                    Legal Action Required

                </label>

                <label>

                    <input type="checkbox"
                           name="field_visit_required"
                           value="1">

                    Field Visit Required

                </label>

            </div>

            <div style="margin-top:25px;">

                <button type="submit"
                        class="erp-btn">

                    Create Escalation

                </button>

            </div>

        </form>

    </div>

    <div class="erp-card">

        <div class="erp-section-title">
            Enterprise Escalation Queue
        </div>

        <div class="erp-table-responsive">

            <table>

                <thead>

                <tr>

                    <th>ID</th>

                    <th>Branch</th>

                    <th>Customer</th>

                    <th>Application No</th>

                    <th>DPD</th>

                    <th>Escalation Type</th>

                    <th>Priority</th>

                    <th>Status</th>

                    <th>SLA Due</th>

                    <th>Governance</th>

                </tr>

                </thead>

                <tbody>

                @forelse($escalations as $escalation)

                    <tr>

                        <td>
                            {{ $escalation->id }}
                        </td>

                        <td>

                            <span class="erp-badge badge-branch">

                                {{ $escalation->branch_name ?? 'N/A' }}

                            </span>

                        </td>

                        <td>

                            <strong>
                                {{ $escalation->customer_name ?? 'N/A' }}
                            </strong>

                            <br>

                            {{ $escalation->customer_mobile ?? '-' }}

                        </td>

                        <td>
                            {{ $escalation->application_no }}
                        </td>

                        <td>

                            @if($escalation->dpd >= 90)

                                <span class="erp-badge badge-legal">

                                    {{ $escalation->dpd }} Days

                                </span>

                            @elseif($escalation->dpd >= 60)

                                <span class="erp-badge badge-critical">

                                    {{ $escalation->dpd }} Days

                                </span>

                            @elseif($escalation->dpd >= 30)

                                <span class="erp-badge badge-warning">

                                    {{ $escalation->dpd }} Days

                                </span>

                            @else

                                <span class="erp-badge badge-normal">

                                    {{ $escalation->dpd }} Days

                                </span>

                            @endif

                        </td>

                        <td>

                            {{ ucfirst($escalation->escalation_type) }}

                        </td>

                        <td>

                            @if($escalation->priority_level == 'critical')

                                <span class="erp-badge badge-legal">
                                    CRITICAL
                                </span>

                            @elseif($escalation->priority_level == 'high')

                                <span class="erp-badge badge-warning">
                                    HIGH
                                </span>

                            @else

                                <span class="erp-badge badge-normal">
                                    NORMAL
                                </span>

                            @endif

                        </td>

                        <td>

                            @if($escalation->status == 'open')

                                <span class="erp-badge badge-open">
                                    OPEN
                                </span>

                            @else

                                <span class="erp-badge badge-closed">
                                    CLOSED
                                </span>

                            @endif

                        </td>

                        <td>

                            @if(
                                $escalation->sla_due_date
                                &&
                                now()->gt($escalation->sla_due_date)
                            )

                                <span class="erp-sla-danger">

                                    SLA BREACHED

                                </span>

                            @else

                                {{ $escalation->sla_due_date }}

                            @endif

                        </td>

                        <td>

                            @if($escalation->legal_action_required)

                                <div class="erp-sla-danger">
                                    Legal Action Required
                                </div>

                            @endif

                            @if($escalation->field_visit_required)

                                <div class="erp-sla-warning">
                                    Field Visit Required
                                </div>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="10">

                            No escalations found.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div style="margin-top:20px;">

            {{ $escalations->links() }}

        </div>

    </div>

</div>

@endsection