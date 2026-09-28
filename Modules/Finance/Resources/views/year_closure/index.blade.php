@extends('layouts.app')

@section('title', 'Close Financial Year')

@section('content')

@push('css')
<style>
.fyc-card{background:#fff;border:1px solid #e3e8f0;border-radius:12px;
    box-shadow:0 2px 8px rgba(23,32,51,.05);padding:18px;margin-bottom:16px}
.fyc-card h3{font-size:15px;font-weight:700;margin:0 0 14px;color:#172033}
.fyc-state{padding:14px 16px;border-radius:10px;margin-bottom:16px;font-size:14px}
.fyc-state.closed{background:#fef3c7;border:1px solid #fde68a;color:#92400e}
.fyc-state.open{background:#eaf7ee;border:1px solid #c7ead0;color:#216c36}
.fyc-field{margin-bottom:13px}
.fyc-field label{display:block;font-weight:700;font-size:12px;color:#4b5870;margin-bottom:6px}
.fyc-field input,.fyc-field textarea{width:100%;border:1px solid #d9e0ea;border-radius:8px;
    padding:9px 11px;font-size:13px}
.fyc-table{width:100%;border-collapse:collapse;font-size:13px}
.fyc-table th{padding:9px 10px;border-bottom:2px solid #e5eaf1;text-align:left;
    font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#68758b}
.fyc-table td{padding:10px;border-bottom:1px solid #eef2f7;vertical-align:top}
.fyc-badge{display:inline-block;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:600}
.fyc-badge.active{background:#fef3c7;color:#92400e}
.fyc-badge.reopened{background:#f1f3f7;color:#6b7688}
.fyc-note{font-size:12px;color:#6f7b90;line-height:1.5}
.fyc-warn{background:#fdeeee;border:1px solid #f5c6c6;color:#982d2d;
    padding:12px 14px;border-radius:8px;font-size:13px;margin-bottom:14px}
</style>
@endpush

<section class="content-header"><h1>Close Financial Year</h1></section>

<section class="content">

    @if(session('status'))
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> {{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> {{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> {{ $errors->first() }}</div>
    @endif

    {{-- Where things stand, stated before anything can be changed. --}}
    @if($closedUpto)
        <div class="fyc-state closed">
            <strong>Closed to {{ $closedUpto->format('d/m/Y') }}.</strong>
            No transaction dated on or before that day can be entered or amended,
            in any module.
            @if($earliest)
                The earliest date now allowed is <strong>{{ $earliest->format('d/m/Y') }}</strong>.
            @endif
        </div>
    @else
        <div class="fyc-state open">
            <strong>No financial year is closed.</strong>
            Transactions can be dated back to the system opening date.
            @if($earliest)
                The earliest allowed date is <strong>{{ $earliest->format('d/m/Y') }}</strong>.
            @endif
        </div>
    @endif

    <div class="row">
        <div class="col-md-5">
            <div class="fyc-card">
                <h3>Close a financial year</h3>

                <div class="fyc-warn">
                    <i class="fa fa-exclamation-triangle"></i>
                    This stops <strong>every user in this business</strong> entering or
                    amending anything dated on or before the date you choose — purchases,
                    sales, expenses, journals, settlements. Only a Super Admin can reopen it.
                </div>

                <form method="post" action="{{ route('finance.year-closure.close') }}">
                    @csrf

                    <div class="fyc-field">
                        <label>Close everything up to and including</label>
                        <input type="date" name="closed_upto" id="fyc-date" required
                            max="{{ now()->toDateString() }}">
                    </div>

                    <div class="fyc-field">
                        <label>Confirm the date</label>
                        {{-- Typing it twice is deliberate friction. A mis-click should
                             not be enough to freeze a period for everyone. --}}
                        <input type="date" name="confirm_date" required
                            max="{{ now()->toDateString() }}">
                        <div class="fyc-note">Type the same date again to confirm.</div>
                    </div>

                    <div class="fyc-field">
                        <label>Label <span class="fyc-note">(optional)</span></label>
                        <input type="text" name="label" maxlength="100" placeholder="FY 2025/26">
                    </div>

                    <div class="fyc-field">
                        <label>Note <span class="fyc-note">(optional)</span></label>
                        <textarea name="note" rows="2"></textarea>
                    </div>

                    <button type="submit" class="btn btn-danger"
                        onclick="return confirm('Close the financial year?\n\nNo one will be able to enter or amend anything on or before that date.')">
                        <i class="fa fa-lock"></i> Close Financial Year
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-7">
            <div class="fyc-card">
                <h3>History</h3>

                <table class="fyc-table">
                    <thead>
                        <tr><th>Closed up to</th><th>Label</th><th>Closed</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                    @forelse($closures as $c)
                        <tr>
                            <td><strong>{{ \Carbon\Carbon::parse($c->closed_upto)->format('d/m/Y') }}</strong></td>
                            <td>{{ $c->label ?: '—' }}
                                @if($c->note)<div class="fyc-note">{{ $c->note }}</div>@endif</td>
                            <td class="fyc-note">{{ \Carbon\Carbon::parse($c->closed_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($c->reopened_at)
                                    <span class="fyc-badge reopened">Reopened</span>
                                    <div class="fyc-note">
                                        {{ \Carbon\Carbon::parse($c->reopened_at)->format('d/m/Y H:i') }}
                                        @if($c->reopen_reason)<br>{{ $c->reopen_reason }}@endif
                                    </div>
                                @else
                                    <span class="fyc-badge active">In force</span>
                                @endif
                            </td>
                            <td>
                                @unless($c->reopened_at)
                                    <form method="post" action="{{ route('finance.year-closure.reopen', $c->id) }}"
                                          onsubmit="var r = prompt('Why is this year being reopened?'); if (!r) { return false; } this.reopen_reason.value = r; return true;">
                                        @csrf
                                        <input type="hidden" name="reopen_reason">
                                        <button type="submit" class="btn btn-default btn-xs">
                                            <i class="fa fa-unlock"></i> Reopen</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center;padding:30px;color:#69758b">
                            No financial year has been closed yet.
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>

                <p class="fyc-note" style="margin-top:12px">
                    A reopened closure stays on record rather than being deleted — the fact
                    that a year was closed and later reopened is exactly what an auditor asks
                    about.
                </p>
            </div>
        </div>
    </div>

</section>

@endsection
