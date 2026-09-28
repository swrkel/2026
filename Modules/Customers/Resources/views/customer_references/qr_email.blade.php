<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reference->documentTitle() }} QR</title>
</head>
{{--
    Task 8046 - the email body for the QR Email action.

    Table-based layout with inline styles, because email clients strip <style>
    blocks and have no reliable flex/grid support.

    The QR is embedded as a base64 data URI when it was rendered server-side.
    Several clients (notably Gmail on the web) refuse to display data URIs, so
    the reference details are repeated as text below the image. The recipient
    can always read what the code says even when the image never appears.
--}}
<body style="margin:0; padding:0; background:#f4f4f6; font-family: Arial, Helvetica, sans-serif; color:#222;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f6; padding:24px 0;">
    <tr>
        <td align="center">

            <table role="presentation" width="460" cellpadding="0" cellspacing="0"
                   style="background:#ffffff; border:1px solid #e0e0e4; border-radius:6px; padding:24px;">

                <tr>
                    <td align="center" style="font-size:16px; font-weight:bold; letter-spacing:0.05em; text-transform:uppercase; padding-bottom:18px;">
                        {{ $reference->documentTitle() }}
                    </td>
                </tr>

                {{--
                    The renderer returns ready-to-use markup - an <img> with a
                    base64 PNG for the milon driver, inline SVG for the others.
                    It is emitted as-is rather than re-encoded, because wrapping
                    an <img> tag in a data URI would produce a broken image.
                --}}
                @if(! empty($qr_svg))
                    <tr>
                        <td align="center" style="padding-bottom:18px;">
                            {!! $qr_svg !!}
                        </td>
                    </tr>
                @endif

                <tr>
                    <td>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                               style="border-collapse:collapse; font-size:13px;">
                            <tr>
                                <th align="left" style="border:1px solid #e0e0e4; background:#f7f7f7; padding:7px 9px; width:42%;">Customer Name</th>
                                <td style="border:1px solid #e0e0e4; padding:7px 9px;">{{ $customer_name }}</td>
                            </tr>
                            <tr>
                                <th align="left" style="border:1px solid #e0e0e4; background:#f7f7f7; padding:7px 9px;">{{ $reference->referenceLabel() }}</th>
                                <td style="border:1px solid #e0e0e4; padding:7px 9px;">{{ $reference->reference_no }}</td>
                            </tr>
                            @if($reference->is_vehicle)
                                <tr>
                                    <th align="left" style="border:1px solid #e0e0e4; background:#f7f7f7; padding:7px 9px;">Fuel Type</th>
                                    <td style="border:1px solid #e0e0e4; padding:7px 9px;">{{ $fuel_type_label }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th align="left" style="border:1px solid #e0e0e4; background:#f7f7f7; padding:7px 9px;">Date &amp; Time</th>
                                <td style="border:1px solid #e0e0e4; padding:7px 9px;">{{ optional($reference->reference_datetime)->format('d/m/Y H:i') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                @unless(! empty($qr_svg))
                    <tr>
                        <td style="padding-top:16px; font-size:12px; color:#666;">
                            The QR image could not be attached to this message. The reference details above are
                            the same information the code carries.
                        </td>
                    </tr>
                @endunless

            </table>

        </td>
    </tr>
</table>

</body>
</html>
