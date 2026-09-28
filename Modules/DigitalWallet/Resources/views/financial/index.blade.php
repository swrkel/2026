@extends('digitalwallet::layout')

@section('content')
<section class="content-header">
    <h1>Enterprise Financial Engine</h1>
    <p class="text-muted">Recharge, reserve, commit, release and adjust wallet balances using auditable ledger entries.</p>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row">
        <div class="col-md-3"><div class="box box-primary"><div class="box-body"><strong>Open Reservations</strong><h3>{{ number_format($stats['reserved'], 2) }}</h3></div></div></div>
        <div class="col-md-3"><div class="box box-success"><div class="box-body"><strong>Committed Today</strong><h3>{{ number_format($stats['committed'], 2) }}</h3></div></div></div>
        <div class="col-md-3"><div class="box box-warning"><div class="box-body"><strong>Released Today</strong><h3>{{ number_format($stats['released'], 2) }}</h3></div></div></div>
        <div class="col-md-3"><div class="box box-info"><div class="box-body"><strong>Adjustments Today</strong><h3>{{ number_format($stats['adjustments_today'], 2) }}</h3></div></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Quick Actions</h3></div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <h4>Recharge</h4>
                    <form method="POST" action="{{ route('digitalwallet.financial.recharge', ['wallet' => optional($wallets->first())->id ?? 0]) }}" onsubmit="this.action=this.action.replace('/0/', '/' + this.wallet_id.value + '/')">
                        @csrf
                        <select name="wallet_id" class="form-control" required>
                            @foreach($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->wallet_code }} - {{ $wallet->wallet_name }}</option>@endforeach
                        </select><br>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="Amount" required><br>
                        <input type="text" name="method" class="form-control" placeholder="Method"><br>
                        <button class="btn btn-success">Post Recharge</button>
                    </form>
                </div>
                <div class="col-md-4">
                    <h4>Reserve</h4>
                    <form method="POST" action="{{ route('digitalwallet.financial.reserve', ['wallet' => optional($wallets->first())->id ?? 0]) }}" onsubmit="this.action=this.action.replace('/0/', '/' + this.wallet_id.value + '/')">
                        @csrf
                        <select name="wallet_id" class="form-control" required>
                            @foreach($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->wallet_code }} - {{ $wallet->wallet_name }}</option>@endforeach
                        </select><br>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="Amount" required><br>
                        <input type="text" name="source_module" class="form-control" placeholder="Source Module"><br>
                        <button class="btn btn-warning">Reserve Funds</button>
                    </form>
                </div>
                <div class="col-md-4">
                    <h4>Adjustment</h4>
                    <form method="POST" action="{{ route('digitalwallet.financial.adjustment', ['wallet' => optional($wallets->first())->id ?? 0]) }}" onsubmit="this.action=this.action.replace('/0/', '/' + this.wallet_id.value + '/')">
                        @csrf
                        <select name="wallet_id" class="form-control" required>
                            @foreach($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->wallet_code }} - {{ $wallet->wallet_name }}</option>@endforeach
                        </select><br>
                        <select name="adjustment_type" class="form-control"><option value="credit">Credit</option><option value="debit">Debit</option></select><br>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="Amount" required><br>
                        <button class="btn btn-primary">Post Adjustment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Recent Reservations</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>No</th><th>Wallet</th><th>Amount</th><th>Status</th><th>Expiry</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($reservations as $reservation)
                    <tr>
                        <td>{{ $reservation->reservation_no }}</td>
                        <td>{{ optional($reservation->wallet)->wallet_name }}</td>
                        <td>{{ number_format($reservation->amount, 2) }}</td>
                        <td>{{ ucfirst($reservation->status) }}</td>
                        <td>{{ optional($reservation->expires_at)->format('Y-m-d H:i') }}</td>
                        <td>
                            @if($reservation->status === 'reserved')
                                <form method="POST" action="{{ route('digitalwallet.financial.commit', $reservation) }}" style="display:inline">@csrf<button class="btn btn-xs btn-success">Commit</button></form>
                                <form method="POST" action="{{ route('digitalwallet.financial.release', $reservation) }}" style="display:inline">@csrf<button class="btn btn-xs btn-warning">Release</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No reservations found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
