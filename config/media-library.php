<?php

return [
    'disk' => env('MEDIA_DISK', 'public'),
    'path_generator' => Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator::class,
    'url_generator'  => Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator::class,
    'version'        => 1,
    'queue'          => ['name' => 'media-library', 'connection' => null],
    'custom_headers' => [],
    'custom_url_with_size_in_response' => true,
    's3' => [
        'disk' => 's3',
    ],
];
