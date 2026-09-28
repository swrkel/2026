<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pumper Dashboard-New</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; background: #f4f7fb; font-family: Arial, Helvetica, sans-serif; color: #243447; }
        .card { width: min(620px, 100%); background: #fff; border: 1px solid #dbe4ef; border-radius: 14px; box-shadow: 0 14px 35px rgba(29, 48, 75, .10); padding: 32px; }
        h1 { margin: 0 0 12px; font-size: 25px; }
        p { margin: 8px 0; line-height: 1.6; }
        .code { margin: 18px 0; padding: 12px 15px; border-radius: 8px; background: #f2f6fb; font-weight: 700; word-break: break-word; }
        .button { display: inline-block; margin-top: 18px; padding: 11px 18px; border-radius: 8px; background: #1f6fb2; color: #fff; text-decoration: none; font-weight: 700; }
        .help { color: #66788a; font-size: 13px; }
    </style>
</head>
<body>
    <main class="card">
        <h1>Business could not be loaded</h1>
        <p>Pumper Dashboard-New is running, but the selected business reference was not found in the active database.</p>
        <div class="code">{{ $companyNumber }}</div>
        <p class="help">Return to the main login page and select the business again. The module accepts company number, company reference, registration number, and numeric business ID.</p>
        <a class="button" href="{{ url('/login') }}">Return to Main Login</a>
    </main>
</body>
</html>
