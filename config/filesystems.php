<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. A "local" driver, as well as a variety of cloud
    | based drivers are available for your choosing. Just store away!
    |
    | Supported: "local", "s3", "rackspace"
    |
    */

    'default' => 'local',

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

    'cloud' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
            'url'    => '/storage/app',
        ],

        's3' => [
            'driver' => 's3',
            'key'    => 'AKIAYWKVJYZQ5NKH6YA4',
            'secret' => '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',
            'region' => 'us-west-2',
            'bucket' => 'zenc0drbucket',
        ],

        'rackspace' => [
            'driver'    => 'rackspace',
            'username'  => 'your-username',
            'key'       => 'your-key',
            'container' => 'your-container',
            'endpoint'  => 'https://identity.api.rackspacecloud.com/v2.0/',
            'region'    => 'IAD',
        ],

        'azimut_storage' => [
            'driver'   => 'ftp',
            'host'     => 'azimut-storage.8ber.ru',
            'port'     => 21,
            'username' => 'azimut_storage',
            'password' => 'rI1pR6lR4s',
            'root'     => '/',
            'url' => 'https://azimut-storage.8ber.ru',
            'timeout'  => 30,
            'passive' => true,
        ],
    ],

];
