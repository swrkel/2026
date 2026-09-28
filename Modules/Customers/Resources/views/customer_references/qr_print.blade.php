<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reference->documentTitle() }} QR - {{ $reference->reference_no }}</title>

    {{--
        Task 8046 - the printable QR page.

        Standalone rather than extending layouts.app: it opens in its own tab
        and is also fed to the PDF writer, and the application chrome (sidebar,
        top bar, theme scripts) has no business in either. Styles are inline for
        the same reason - a PDF writer fetching external stylesheets over the
        network is slow at best and blank at worst.
    --}}
    <style>
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #222;
            margin: 0;
            padding: 24px;
            background: #fff;
        }
        .cus-ref-card {
            max-width: 420px;
            margin: 0 auto;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
        }
        .cus-ref-card h1 {
            font-size: 16px;
            margin: 0 0 16px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .cus-ref-qr { margin: 0 auto 18px; }
        .cus-ref-qr svg,
        .cus-ref-qr canvas,
        .cus-ref-qr img {
            width: 240px;
            height: 240px;
        }
        table.cus-ref-details {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }
        table.cus-ref-details th,
        table.cus-ref-details td {
            border: 1px solid #e0e0e0;
            padding: 7px 9px;
            vertical-align: top;
        }
        table.cus-ref-details th {
            width: 42%;
            background: #f7f7f7;
            font-weight: 600;
        }
        .cus-ref-notice {
            max-width: 420px;
            margin: 0 auto 16px;
            padding: 10px 12px;
            border: 1px solid #f0c36d;
            background: #fdf7e3;
            border-radius: 4px;
            font-size: 12px;
        }
        .cus-ref-fallback-text {
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            text-align: left;
            background: #f7f7f9;
            border: 1px solid #e1e1e8;
            border-radius: 4px;
            padding: 10px;
        }
        @media print {
            /* Anything that is not the card is screen-only furniture. */
            .cus-ref-no-print { display: none !important; }
            body { padding: 0; }
            .cus-ref-card { border: none; }
        }
    </style>
</head>
<body>

@if(! empty($pdfFallbackNotice))
    <div class="cus-ref-notice cus-ref-no-print">
        A PDF could not be generated on the server for this document.
        Use your browser&rsquo;s print dialog and choose <strong>Save as PDF</strong>.
    </div>
@endif

<div class="cus-ref-card">
    <h1>{{ $reference->documentTitle() }}</h1>

    <div class="cus-ref-qr" id="cus_ref_print_qr" data-qr-payload="{{ $qr_payload }}">
        @if(! empty($qr_svg))
            {!! $qr_svg !!}
        @else
            {{-- Filled in by the browser fallback below. --}}
        @endif
    </div>

    @if(empty($qr_svg))
        {{--
            Shown until the browser fallback replaces it, and left in place if
            that fallback cannot load. The details are still readable and the
            printout is still usable, which matters more than a blank square.
        --}}
        <div class="cus-ref-fallback-text" id="cus_ref_print_fallback">{{ $qr_payload }}</div>
    @endif

    <table class="cus-ref-details">
        <tbody>
            <tr>
                <th>Customer Name</th>
                <td>{{ $customer_name }}</td>
            </tr>
            <tr>
                <th>{{ $reference->referenceLabel() }}</th>
                <td>{{ $reference->reference_no }}</td>
            </tr>
            @if($reference->is_vehicle)
                <tr>
                    <th>Fuel Type</th>
                    <td>{{ $fuel_type_label }}</td>
                </tr>
            @endif
            <tr>
                <th>Date &amp; Time</th>
                <td>{{ optional($reference->reference_datetime)->format('d/m/Y H:i') }}</td>
            </tr>
        </tbody>
    </table>
</div>

@if(empty($qr_svg))
    {{--
        Browser-side rendering, used only when the application has no
        server-side QR library. Loaded from a CDN because the module cannot add
        a package to the host application's dependencies. On an air-gapped
        install this will not load and the plain-text payload above stays
        visible - which is why that text is rendered first rather than as an
        error state.

        Installing simplesoftwareio/simple-qrcode removes this path entirely.
    --}}
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        (function () {
            var holder = document.getElementById('cus_ref_print_qr');
            var fallback = document.getElementById('cus_ref_print_fallback');
            if (!holder || typeof window.QRCode === 'undefined') {
                return;
            }

            new window.QRCode(holder, {
                text: holder.getAttribute('data-qr-payload') || '',
                width: 240,
                height: 240,
                correctLevel: window.QRCode.CorrectLevel.M
            });

            if (fallback) {
                fallback.style.display = 'none';
            }
        })();
    </script>
@endif

@if(! empty($autoPrint))
    <script>
        window.addEventListener('load', function () {
            // Delayed so a browser-rendered QR has painted before the print
            // dialog captures the page.
            window.setTimeout(function () { window.print(); }, 350);
        });
    </script>
@endif

</body>
</html>
