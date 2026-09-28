<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Card - {{ $member->member_number }}</title>
    @php
        $baseWidthMm = 85.6;
        $baseHeightMm = 53.98;

        $minWidthMm = 40;
        $minHeightMm = 25;

        if ($cardSetting) {
            $targetWidthMm = max($cardSetting->width, $minWidthMm);
            $targetHeightMm = max($cardSetting->length, $minHeightMm);
        } else {
            $targetWidthMm = $baseWidthMm;
            $targetHeightMm = $baseHeightMm;
        }

        $scaleX = $targetWidthMm / $baseWidthMm;
        $scaleY = $targetHeightMm / $baseHeightMm;
        $cardScale = min($scaleX, $scaleY);
    @endphp
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }
            .no-print { display: none !important; }
            .page-wrapper {
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .card-container {
                margin: 0;
                box-shadow: none;
            }
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .page-wrapper {
            width: 100%;
            max-width: 600px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card-container {
            border: 2px solid #000;
            padding: 0;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: {{ $targetWidthMm }}mm;
            height: {{ $targetHeightMm }}mm;
            position: relative;
            margin: 0 auto;
            overflow: hidden;
        }
        .card-inner {
            width: {{ $baseWidthMm }}mm;
            height: {{ $baseHeightMm }}mm;
            transform: scale({{ $cardScale }});
            transform-origin: top left;
            padding: 3mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        .business-name-header {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            padding: 5px 0;
            border-bottom: 1px solid #000;
            margin-bottom: 10px;
            word-wrap: break-word;
        }
        .card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .member-details-section {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            margin-bottom: 10px;
        }
        .member-info {
            font-size: 9px;
            line-height: 1.4;
        }
        .member-info div {
            margin-bottom: 2px;
        }
        .member-info strong {
            display: inline-block;
            min-width: 90px;
        }
        .qr-code-section {
            position: absolute;
            top: 0;
            right: 0;
            text-align: center;
            width: 40%;
            max-width: 30mm;
        }
        .qr-code-section img {
            max-width: 100%;
            max-height: 60px;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .signature-divider {
            margin-top: 2px;
            border-top: 1px solid #ccc;
        }
        .signature-section {
            margin-top: 1px;
            text-align: right;
            width: 100%;
        }
        .signature-section img {
            max-width: 100%;
            max-height: 35px;
            width: auto;
            height: auto;
            display: block;
            margin: 0 0 2px auto;
            object-fit: contain;
        }
        .signature-section div {
            font-size: 10px;
            font-weight: bold;
        }
        .print-btn {
            text-align: center;
            margin-bottom: 20px;
        }
        .print-btn button {
            padding: 10px 20px;
            font-size: 16px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .print-btn button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="print-btn no-print">
        <button onclick="window.print()">Print Card</button>
    </div>

    <div class="page-wrapper">
        <div class="card-container">
            <div class="card-inner">
                <div class="business-name-header">
                    {{ $business->name ?? 'Business Name' }}
                </div>

                <div class="card-content">
                    <div class="member-details-section">
                        <div class="member-info">
                            <div><strong>Member Code:</strong> {{ $member->member_number }}</div>
                            <div><strong>Member Name:</strong> {{ $member->member_name }}</div>
                            <div><strong>Joined Date:</strong> 
                                @if($member->date_joined)
                                    @php
                                        try {
                                            $joinedDate = \Carbon\Carbon::parse($member->date_joined)->format('Y-m-d');
                                        } catch (\Exception $e) {
                                            $joinedDate = $member->date_joined;
                                        }
                                    @endphp
                                    {{ $joinedDate }}
                                @else
                                    -
                                @endif
                            </div>
                            <div><strong>Region:</strong> {{ $membershipSetting->region ?? '-' }}</div>
                            <div><strong>Date Issued:</strong> {{ $member->created_at ? $member->created_at->format('Y-m-d') : '-' }}</div>
                        </div>
                        <div class="signature-divider"></div>
                    </div>

                    <div class="qr-code-section">
                        @if($member->qr_code_path && file_exists(public_path('uploads/' . $member->qr_code_path)))
                            <img src="{{ asset('uploads/' . $member->qr_code_path) }}" alt="QR Code">
                        @endif
                    </div>

                    <div class="signature-section">
                        @if($signature)
                            @php
                                $signaturePath = 'uploads/' . $signature->signature_path;
                                $signatureExists = file_exists(public_path($signaturePath));
                                $ext = $signaturePath ? strtolower(pathinfo($signaturePath, PATHINFO_EXTENSION)) : '';
                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'tif']);
                            @endphp
                            @if($signatureExists && $isImage)
                                <img src="{{ asset($signaturePath) }}" alt="Authorized Signature">
                            @elseif($signatureExists && !$isImage)
                                <span class="text-muted" style="font-size: 10px;"><i class="fa fa-file"></i> Signature on file</span>
                            @endif
                        @endif
                        <div>Authorized Signature</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
