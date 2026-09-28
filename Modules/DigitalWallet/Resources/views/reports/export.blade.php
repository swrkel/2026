Transaction No,Wallet,Type,Amount,Currency,Balance Before,Balance After,Status,Date
@foreach($transactions as $transaction)
{{ $transaction->transaction_no }},{{ optional($transaction->wallet)->wallet_code }},{{ $transaction->transaction_type }},{{ $transaction->amount }},{{ $transaction->currency }},{{ $transaction->balance_before }},{{ $transaction->balance_after }},{{ $transaction->status }},{{ $transaction->created_at }}
@endforeach
