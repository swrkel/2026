@foreach($pos_transactions as $transaction)
    @php
        $label = !empty($transaction->invoice_no) ? $transaction->invoice_no : (!empty($transaction->ref_no) ? $transaction->ref_no : $transaction->id);
    @endphp
    <option value="{{$transaction->id}}" @if(in_array($transaction->id, $selected_pos_transaction_ids ?? [])) selected @endif>
        {{$label}} - {{@num_format($transaction->final_total)}}
    </option>
@endforeach
