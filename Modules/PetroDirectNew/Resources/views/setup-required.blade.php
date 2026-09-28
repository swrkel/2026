<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Petro Direct-New setup required</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f2f6fb;color:#172b4d;padding:32px}.card{max-width:1100px;margin:auto;background:#fff;border:1px solid #d9e2ef;border-radius:18px;padding:32px;box-shadow:0 12px 36px rgba(23,43,77,.08)}code{background:#edf3fb;padding:4px 8px;border-radius:6px}.missing{line-height:1.8;word-break:break-word}.note{padding:12px 14px;background:#fff4d6;border-radius:10px;margin-top:18px}.db{padding:12px 14px;background:#eaf4ff;border-radius:10px;margin:14px 0}.db strong{display:inline-block;min-width:150px}
    </style>
</head>
<body>
<div class="card">
    <h1>Petro Direct-New setup required</h1>

    <div class="db">
        <div><strong>Checked database:</strong> <code>{{ $databaseName ?: 'Unknown' }}</code></div>
        <div><strong>Laravel connection:</strong> <code>{{ $connectionName ?: 'Unknown' }}</code></div>
    </div>

    <p>Run this repair SQL in the exact tenant database shown above:</p>
    <p><code>Modules/PetroDirectNew/SQL/07_FORCE_REPAIR_OPERATOR_WORKSPACE.sql</code></p>

    <h3>Missing database objects</h3>
    <div class="missing">{{ implode(', ', $missing) }}</div>

    <div class="note">
        The SQL is idempotent, does not delete operator records, and prints an OK/MISSING result for every required column after execution.
    </div>
</div>
</body>
</html>
