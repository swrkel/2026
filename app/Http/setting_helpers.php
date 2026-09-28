<?php
/*
|------------------------------------------------------------------------------
| Settings helpers restored after the HelpGuide 8039 rewrite.
|------------------------------------------------------------------------------
| The rewrite removed the RouteServiceProvider that loaded
| Modules/HelpGuide/Helpers/helpers.php, where setting() lived. It was never in
| core, so its removal took the whole estate down: every page calling setting()
| threw "Call to undefined function".
|
| These are restored here, in core, so no module's lifecycle can remove them
| again. Every function is wrapped in function_exists() so a module reinstating
| its own copy will not collide.
|
| setting() reads helpguide_settings (name / val / type), the table that
| survived the rewrite. setting([$name, $value]) writes.
*/

if (! function_exists('setting')) {
    function setting($key, $default = null)
    {
        static $cache = null;
        $table = 'helpguide_settings';
        if (is_array($key)) {
            $name = $key[0] ?? null;
            $val  = $key[1] ?? null;
            if ($name === null) { return null; }
            try {
                if (! \Illuminate\Support\Facades\Schema::hasTable($table)) { return false; }
                $exists = \Illuminate\Support\Facades\DB::table($table)->where('name', $name)->exists();
                if ($exists) {
                    \Illuminate\Support\Facades\DB::table($table)->where('name', $name)
                        ->update(['val' => $val, 'updated_at' => now()]);
                } else {
                    \Illuminate\Support\Facades\DB::table($table)->insert([
                        'name' => $name, 'val' => $val, 'type' => 'string',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $cache = null;
                return $val;
            } catch (\Throwable $e) { return false; }
        }
        if ($cache === null) {
            try {
                if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                    $cache = [];
                } else {
                    $cache = [];
                    foreach (\Illuminate\Support\Facades\DB::table($table)->get(['name','val','type']) as $row) {
                        $value = $row->val;
                        switch ((string) $row->type) {
                            case 'boolean': case 'bool':
                                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN); break;
                            case 'integer': case 'int':
                                $value = (int) $value; break;
                            case 'float': case 'double':
                                $value = (float) $value; break;
                            case 'array': case 'json':
                                $d = json_decode((string) $value, true);
                                $value = is_array($d) ? $d : $value; break;
                        }
                        $cache[(string) $row->name] = $value;
                    }
                }
            } catch (\Throwable $e) {
                $cache = [];
            }
        }
        if ($key === null) { return $cache; }
        return array_key_exists($key, $cache) && $cache[$key] !== null
            ? $cache[$key] : value($default);
    }
}
if (! function_exists('isAppinstalled')) {
    function isAppinstalled()
    {
        static $installed = null;
        if ($installed === null) {
            $installed = file_exists(storage_path('app/app_installed'));
        }
        return $installed;
    }
}
if (! function_exists('isAppInstalled')) {
    function isAppInstalled() { return isAppinstalled(); }
}
if (! function_exists('getLocaleName')) {
    function getLocaleName($lang)
    {
        $locale = str_replace('_', '-', (string) $lang);
        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayName($locale, app()->getLocale());
            if (! empty($name)) { return $name; }
        }
        return $locale;
    }
}
if (! function_exists('availableLanguages')) {
    function availableLanguages()
    {
        static $languages = null;
        if (is_array($languages)) { return $languages; }
        $langPath = resource_path('lang');
        if (! is_dir($langPath)) { return $languages = []; }
        $names = [];
        foreach (array_diff(scandir($langPath) ?: [], ['.', '..']) as $lang) {
            if ($lang !== 'vendor' && is_dir($langPath . DIRECTORY_SEPARATOR . $lang)) {
                $names[$lang] = ucwords(getLocaleName($lang));
            }
        }
        return $languages = $names;
    }
}
