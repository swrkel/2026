<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => public_path('uploads'),
        ],

        'local_server' => [
            'driver' => 'local',
            'root' => public_path('uploads'),
            'url' => '/uploads',
        ],

        'article' => [
            'driver' => 'local',
            'root' => base_path('helpguide/uploads/articles/images'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_KEY'),
            'secret' => env('AWS_SECRET'),
            'region' => env('AWS_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],
        /*
         | Master banner images are intentionally stored OUTSIDE the Laravel
         | deployment tree. Their database rows live centrally and every tenant
         | uses the master asset URL below. No public/storage symlink is needed.
         |
         | Default example:
         |   code:    /home/account/public_html
         |   banners: /home/account/shared/banner_uploads/master-domain/
         |
         | Therefore replacing public_html does not remove uploaded banners.
        */
        'banner_uploads' => [
            'driver' => 'local',
            // IMPORTANT: keep master banners OUTSIDE the Laravel code tree.
            // A normal code replacement may replace public/, storage/, app/,
            // Modules/, etc. This path survives those deployments.
            'root' => env(
                'BANNER_STORAGE_ROOT',
                dirname(base_path()) . '/shared/banner_uploads/' . preg_replace(
                    '/[^A-Za-z0-9._-]/',
                    '_',
                    (string) (parse_url(env('APP_URL', ''), PHP_URL_HOST) ?: env('CENTRAL_DOMAIN', 'default'))
                )
            ),
            // All tenant/business pages should fetch the file from the master
            // system rather than looking for a tenant-local copy.
            'url' => rtrim(env('BANNER_MASTER_URL', env('APP_URL', '')), '/') . '/master-banner-assets',
            'visibility' => 'public',
        ],

         'public_uploads' => [
        'driver' => 'local',
        'root' => public_path('uploads'),
        'url' => '/uploads',
        'visibility' => 'public',
    ],

        'dropbox' => [
            'driver' => 'dropbox',
            'authorizationToken' => env('DROPBOX_ACCESS_TOKEN')
        ],

        'google' => [
            'driver' => 'google',
            'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
            'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
            'folder_id' => env('GOOGLE_FOLDER_NAME'),
        ],
        
    ],

];
