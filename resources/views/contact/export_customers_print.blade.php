<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer List</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 8px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:nth-child(even) { background: #fafafa; }
        h2 { margin-bottom: 8px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    {{-- Modified by Engr. Alex -- task 7889 --}}
    <h2>Customer List</h2>
    @if($format === 'print')
        <p class="no-print">
            <button onclick="window.print()">Print</button>
        </p>
    @endif
    <table>
        <thead>
            <tr>
                @foreach($headers as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    @if($format === 'print')
        <script>window.onload = function(){ window.print(); }</script>
    @endif
</body>
</html>
