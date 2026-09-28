<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $ticket->ticket_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        .qty { width: 45px; font-weight: bold; }
    </style>
</head>
<body onload="window.print()">
    <h3 class="center">KITCHEN ORDER TICKET</h3>
    <div class="center"><strong>{{ $ticket->ticket_no }}</strong></div>
    <div class="line"></div>
    <div>Order: #{{ $ticket->order_id }}</div>
    <div>Time: {{ optional($ticket->created_at)->format('d/m/Y H:i') }}</div>
    <div class="line"></div>
    <table>
        @foreach($ticket->lines as $line)
            <tr>
                <td class="qty">{{ number_format((float) $line->quantity, 3) }}</td>
                <td>
                    <strong>{{ $line->item_name }}</strong><br>
                    @if($line->modifiers_text)<small>{{ $line->modifiers_text }}</small><br>@endif
                    @if($line->special_instruction)<em>{{ $line->special_instruction }}</em>@endif
                </td>
            </tr>
        @endforeach
    </table>
    <div class="line"></div>
</body>
</html>
