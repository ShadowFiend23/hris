<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Source Directories
    |--------------------------------------------------------------------------
    | PHP directories to encrypt when building a client deployment package.
    | vendor/, public/, resources/, and storage/ are intentionally excluded:
    |   - vendor/    third-party packages (not our code)
    |   - public/    already-compiled Vite assets
    |   - resources/ Vue/CSS source (compiled into public/build/ by Vite)
    |   - storage/   runtime files
    */
    'source' => [
        'app',
    ],

    /*
    |--------------------------------------------------------------------------
    | Destination Directory
    |--------------------------------------------------------------------------
    | The output folder for the encrypted build. This folder is what you
    | ship to the client (merged with vendor/, public/, storage/).
    | Added to .gitignore so encrypted builds are never committed.
    */
    'destination' => 'dist',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key Length
    |--------------------------------------------------------------------------
    | Length of the per-file random encryption key. 16 is a good balance
    | between security and performance.
    */
    'key_length' => 16,

];
