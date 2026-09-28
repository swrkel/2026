@php
    $favicon_default_url = asset('img/setting/icon-1730547010.png');
    $favicon_default_file = public_path('img/setting/icon-1730547010.png');
    $favicon_url = '';
    $favicon_version = null;

    $favicon_attach_version = function ($url, $version) {
        if (empty($url) || empty($version)) {
            return $url;
        }

        $separator = strpos($url, '?') === false ? '?' : '&';
        return $url . $separator . 'v=' . $version;
    };

    $favicon_from_path = function ($path) use (&$favicon_version) {
        if (empty($path)) {
            return '';
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $normalized = ltrim($path, '/');
        $candidates = [
            $normalized,
            str_replace('public/', '', $normalized),
        ];

        foreach ($candidates as $candidate) {
            if (empty($candidate)) {
                continue;
            }

            $absolute = public_path($candidate);
            if (file_exists($absolute)) {
                $favicon_version = @filemtime($absolute) ?: time();
                return asset($candidate);
            }
        }

        $storageCandidates = [];
        if (strpos($normalized, 'public/') === 0) {
            $storageCandidates[] = storage_path('app/' . $normalized);
            $storageCandidates[] = storage_path('app/' . substr($normalized, strlen('public/')));
        } else {
            $storageCandidates[] = storage_path('app/public/' . $normalized);
            $storageCandidates[] = storage_path('app/' . $normalized);
        }

        foreach ($storageCandidates as $candidate) {
            if (!empty($candidate) && file_exists($candidate)) {
                $favicon_version = @filemtime($candidate) ?: time();
                try {
                    return \Storage::url($path);
                } catch (\Throwable $e) {
                    return '';
                }
            }
        }

        return '';
    };

    $favicon_business = null;
    $favicon_business_id = session()->get('business.id') ?: session()->get('user.business_id');
    $favicon_fast_superadmin = isset($__is_superadmin_business_index) && $__is_superadmin_business_index;

    if (!$favicon_fast_superadmin && !empty($favicon_business_id)) {
        $favicon_business = \App\Business::find($favicon_business_id);
    }

    // The business list does not need tenant/site-specific favicon discovery.
    // Use the default icon and avoid three database queries on this fast path.
    $favicon_site_settings = $favicon_fast_superadmin
        ? null
        : DB::table('site_settings')->where('id', 1)->select('*')->first();
    $favicon_settings = $favicon_fast_superadmin
        ? null
        : DB::table('settings')->where('id', 1)->select('*')->first();

    if (!empty($favicon_business) && !empty($favicon_business->favicon_path)) {
        $favicon_url = $favicon_from_path($favicon_business->favicon_path);
    }

    if (empty($favicon_url) && !empty($favicon_site_settings) && !empty($favicon_site_settings->uploadFileFicon)) {
        $favicon_url = $favicon_from_path($favicon_site_settings->uploadFileFicon);
    }

    if (empty($favicon_url) && !empty($favicon_settings) && !empty($favicon_settings->favicon)) {
        $favicon_url = $favicon_from_path($favicon_settings->favicon);
    }

    if (empty($favicon_url)) {
        $favicon_url = $favicon_default_url;
        if (file_exists($favicon_default_file)) {
            $favicon_version = @filemtime($favicon_default_file) ?: time();
        }
    }

    $favicon_url = $favicon_attach_version($favicon_url, $favicon_version);
@endphp

@if (!empty($favicon_url))
    <link rel="shortcut icon" type="image/png" href="{{ $favicon_url }}" />
    <link rel="icon" type="image/png" href="{{ $favicon_url }}" />
    <link rel="apple-touch-icon" href="{{ $favicon_url }}" />
@endif
