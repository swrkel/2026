<!doctype html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
<table border="1">
    <thead>
    <tr>
        @foreach($headings as $heading)
            <th>{{ $heading }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row['supplier_no'] }}</td>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['mobile'] }}</td>
            <td>{{ $row['email'] }}</td>
            <td style="text-align:right;">{{ $row['total_due'] }}</td>
            <td>{{ $row['created_at'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
