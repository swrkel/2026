<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('suppliers::lang.supplier_records') }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        h2 { margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
<h2>{{ __('suppliers::lang.supplier_records') }}</h2>
<table>
    <thead>
    <tr>
        @foreach($headings as $heading)
            <th>{{ $heading }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            <td>{{ $row['supplier_no'] }}</td>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['mobile'] }}</td>
            <td>{{ $row['email'] }}</td>
            <td style="text-align:right;">{{ $row['total_due'] }}</td>
            <td>{{ $row['created_at'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6" style="text-align:center;">{{ __('suppliers::lang.no_suppliers_found') }}</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
