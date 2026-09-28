<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Petro PD-New setup required</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f3f6fb;margin:0;padding:32px;color:#172033}
        .box{max-width:1180px;margin:auto;background:#fff;border:1px solid #dbe3ee;border-radius:18px;padding:32px;box-shadow:0 12px 36px rgba(23,32,51,.08)}
        h1{margin:0 0 18px;font-size:34px} h3{margin:26px 0 10px;font-size:22px}
        p{font-size:18px;line-height:1.55} code{background:#eef3f8;padding:4px 8px;border-radius:5px;word-break:break-all}
        .notice{background:#fff6d8;border:1px solid #f1d782;border-radius:12px;padding:16px 18px;margin:16px 0}
        .db{background:#eaf4ff;border:1px solid #b8d7fa;border-radius:12px;padding:14px 18px;margin-bottom:18px}
        .missing{line-height:1.65;word-break:break-word}
    </style>
</head>
<body>
<div class="box">
    <h1>Petro PD-New setup required</h1>

    @if(!empty($activeDatabase))
        <div class="db"><strong>Active tenant database:</strong> <code>{{ $activeDatabase }}</code></div>
    @endif

    @if(!empty($missingTables))
        <div class="notice">
            <strong>Petro PD-New tables are missing.</strong><br>
            Run <code>Modules/PetroPDNew/SQL/00_MASTER_INSTALL_PETRO_PD_NEW.sql</code>
            in the active tenant database.
        </div>
        <h3>Missing Petro PD-New tables</h3>
        <p class="missing">{{ implode(', ', $missingTables) }}</p>
    @endif

    @if(!empty($missingSourceTables) || !empty($missingSourceColumns))
        <div class="notice">
            <strong>The missing objects belong to Pumper Dashboard-New, not Petro PD-New.</strong><br>
            Run <code>Modules/PumperDashboardNew/SQL/05_REPAIR_PETRO_PD_NEW_SOURCE_SCHEMA_IF_NOT_EXISTS.sql</code>
            in the active tenant database, followed by
            <code>Modules/PumperDashboardNew/SQL/06_VERIFY_PETRO_PD_NEW_SOURCE_SCHEMA.sql</code>.
        </div>
    @endif

    @if(!empty($missingSourceTables))
        <h3>Missing Pumper Dashboard-New source tables</h3>
        <p class="missing">{{ implode(', ', $missingSourceTables) }}</p>
    @endif

    @if(!empty($missingSourceColumns))
        <h3>Missing Pumper Dashboard-New source columns</h3>
        <p class="missing">{{ implode(', ', $missingSourceColumns) }}</p>
    @endif

    @if(!empty($schemaError))
        <h3>Schema inspection error</h3>
        <p class="missing">{{ $schemaError }}</p>
    @endif
</div>
</body>
</html>
