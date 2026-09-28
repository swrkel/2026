@php
    $footersettings = DB::table('system')->where('key', 'app_footer')->select('value')->first();
@endphp

<!-- Main Footer -->
<footer class="main-footer no-print" style="display:flex;justify-content:flex-start;align-items:center;width:100%;padding:14px 10px;min-height:58px;">
    <small style="font-size:calc(24px - 2pt) !important;line-height:1.45;">
        {{ $footersettings->value ?? "" }}
    </small>
</footer>
