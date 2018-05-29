<?php

return [

    /*
    |--------------------------------------------------------
    |
    |   Configurations of connection to MongoDB
    |
    |--------------------------------------------------------
    */
    'db_config' => [
        'host'     => env('DB_HOST', 'localhost'),
        'port'     => env('DB_PORT', 27017),
        'database' => env('DB_DATABASE'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'options'  => [
            'database' => 'admin' // sets the authentication database required by mongo 3
        ]
    ],

    /*
    |--------------------------------------------------------
    |
    |   Config of bucket. Default prefix of GridFS collection. Default is 'fs'
    |
    |--------------------------------------------------------
    */
    'bucket'    =>  [
        'prefix'            =>  'fs',
        'chunkSizeBytes'    =>  261120,
        'readPreference'    =>  'primaryPreferred',
        'readConcern'       =>  'available',
    ],

    /*
    |--------------------------------------------------------
    |
    |   By default store file add metadata:
    |       - uuid
    |       - created_at
    |       - updated_at
    |       - downloads
    |
    |--------------------------------------------------------
    */
    'add_meta'      =>  true,

    /*
    |--------------------------------------------------------
    |
    |   Storage disk. Temporary use for storing file in GridFS
    |
    |--------------------------------------------------------
    */
    'storage'       =>  'local',
];
