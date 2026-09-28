{{--
    S382: Legacy Chequer dashboard view bridge.
    Some old route/controller paths still resolve to this legacy view. Keep it as a thin bridge to the
    new ERP-standard dashboard so users never see the old cheque dashboard/table layout again.
--}}
@include('chequer::dashboard.index', ['stats' => $stats ?? []])
