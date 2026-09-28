<?php
return [
    'name' => 'HelpGuide',
    'central_connection' => env('HELPGUIDE_CENTRAL_CONNECTION', config('tenancy.database.central_connection', 'mysql')),
    'tenant_connection' => 'helpguide_tenant',
    'translation' => [
        'provider' => env('HELPGUIDE_TRANSLATION_PROVIDER', 'mymemory'),
        'google_api_key' => env('HELPGUIDE_GOOGLE_TRANSLATE_KEY'),
        'libretranslate_url' => env('HELPGUIDE_LIBRETRANSLATE_URL'),
        'libretranslate_api_key' => env('HELPGUIDE_LIBRETRANSLATE_KEY'),
        'timeout' => (int) env('HELPGUIDE_TRANSLATION_TIMEOUT', 20),
        'scheduler_batch_size' => (int) env('HELPGUIDE_TRANSLATION_BATCH_SIZE', 1),
        'max_attempts' => (int) env('HELPGUIDE_TRANSLATION_MAX_ATTEMPTS', 4),
        'stale_lock_minutes' => (int) env('HELPGUIDE_TRANSLATION_STALE_LOCK_MINUTES', 20),
        'backoff_minutes' => [1, 5, 15, 60],
        'text_chunk_chars' => (int) env('HELPGUIDE_TRANSLATION_TEXT_CHUNK_CHARS', 430),
        'html_chunk_chars' => (int) env('HELPGUIDE_TRANSLATION_HTML_CHUNK_CHARS', 3500),
    ],
];
