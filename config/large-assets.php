<?php

return [

    's3' => [
        'chunk_size' => env('LARGE_ASSETS_S3_CHUNK_SIZE', 100 * (1024 * 1024)),
    ],

    'tus' => [
        'chunk_size' => env('LARGE_ASSETS_TUS_CHUNK_SIZE', 10 * (1024 * 1024)),
    ],

];
