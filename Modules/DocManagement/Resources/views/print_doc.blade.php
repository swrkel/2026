<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Document #{{ $doc->doc_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
        th { background: #f5f5f5; width: 30%; }
        h2 { text-align: center; }
        .print-btn { margin-bottom: 20px; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>
    <div class="print-btn">
        <button onclick="window.print()">Print</button>
        <a href="{{ url('DocManagement/documet') }}">Back</a>
    </div>

    <h2>Document Management</h2>
    <p style="text-align:center;">Document #{{ $doc->doc_no }}</p>

    <table>
        <tr><th>Doc No</th><td>{{ $doc->doc_no }}</td></tr>
        <tr><th>Originator</th><td>{{ $doc->originator }}</td></tr>
        <tr><th>Document Type</th><td>{{ $doc->document_type }}</td></tr>
        <tr><th>Purpose</th><td>{{ $doc->purpose }}</td></tr>
        <tr><th>Referred To</th><td>{{ $doc->referred_to }}</td></tr>
        <tr><th>Status</th><td>{{ $doc->status }}</td></tr>
        <tr><th>Note</th><td>{{ $doc->note }}</td></tr>
        <tr><th>Date</th><td>{{ $doc->created_at }}</td></tr>
    </table>

    @if(!empty($doc->attachment_items))
    <div style="margin-top:20px;">
        <h4>Attached Documents</h4>
        @foreach($doc->attachment_items as $attachment)
            <div style="margin-bottom: 12px;">
                <div>{{ $attachment['name'] }}</div>
            </div>
        @endforeach
    </div>
    @endif

    <script>window.onload = function() { window.print(); }</script>
</body>
</html>
