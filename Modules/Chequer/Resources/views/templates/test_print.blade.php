<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test Print - {{ $row->template_name }}</title>
    <style>
        @page{size:{{ (float)$row->paper_width }}in {{ (float)$row->paper_height }}in;margin:0}
        html,body{margin:0;padding:0;background:#fff;font-family:Arial,sans-serif}
        .toolbar{position:fixed;top:10px;right:10px;z-index:100;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:8px;box-shadow:0 4px 15px rgba(0,0,0,.15)}
        .toolbar button{border:0;border-radius:6px;padding:8px 12px;background:#2563eb;color:#fff;font-weight:700;cursor:pointer}
        .cheque{position:relative;width:{{ (float)$row->paper_width }}in;height:{{ (float)$row->paper_height }}in;background:#fff @if(!empty($fieldMap['template_image'])) url('{{ asset('storage/'.$fieldMap['template_image']) }}') center/100% 100% no-repeat @endif;overflow:hidden}
        .field{position:absolute;box-sizing:border-box;display:flex;align-items:center;overflow:hidden;color:#000}
        .field img{width:100%;height:100%;object-fit:contain}
        @media print{.toolbar{display:none}.cheque{page-break-after:avoid}}
    </style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Print Test</button></div>
<div class="cheque">
    @foreach(($fieldMap['fields'] ?? []) as $key=>$field)
        @if(!empty($field['enabled']))
            <div class="field" style="top:{{ (int)($field['top'] ?? 0) }}px;left:{{ (int)($field['left'] ?? 0) }}px;width:{{ (int)($field['width'] ?? 120) }}px;height:{{ (int)($field['height'] ?? 28) }}px;font-size:{{ (int)($field['font_size'] ?? 14) }}px;text-align:{{ $field['align'] ?? 'left' }};font-weight:{{ !empty($field['bold']) ? '700':'400' }}">
                @if($key === 'signature' && !empty($fieldMap['signature_image']))
                    <img src="{{ asset('storage/'.$fieldMap['signature_image']) }}" alt="Signature">
                @else
                    <span style="width:100%">{{ $field['sample'] ?? $field['label'] ?? $key }}</span>
                @endif
            </div>
        @endif
    @endforeach
</div>
</body>
</html>
