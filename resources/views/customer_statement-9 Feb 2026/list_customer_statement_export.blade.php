@php
$columns = [
    'date_printed' => __('contact.date_printed'),
    'date_from' => __('contact.date_from'),
    'date_to' => __('contact.date_to'),
    'customer' => __('contact.customer'),
    'statement_no' => __('contact.statement_no'),
    'statement_amount' => __('contact.statement_amount'),
    'payment_status' => __('contact.payment_status'),
    'added_by' => __('contact.added_by'),
    'description' => __('contact.description'),
];
$columnMap = [
    1 => 'date_printed',
    2 => 'date_from',
    3 => 'date_to',
    4 => 'customer',
    5 => 'statement_no',
    6 => 'statement_amount',
    7 => 'payment_status',
    8 => 'added_by',
    9 => 'description',
];
$selectedCols = [];
if (!empty($visible_cols)) {
    foreach ($visible_cols as $i) {
        if (isset($columnMap[$i])) {
            $selectedCols[] = $columnMap[$i];
        }
    }
} else {
    $selectedCols = array_keys($columns);
}
@endphp

<table>
    <tr>
        <td>
            {{$contact->name}}
        </td>
    </tr>
</table>
<table>
    <thead>
        <tr>
            @foreach($selectedCols as $key)
                <th>{{ $columns[$key] }}</th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        <tr>
            @foreach($selectedCols as $key)
                <td>{!! $statement[$key] ?? '' !!}</td>
            @endforeach
        </tr>
    </tbody>
</table>
