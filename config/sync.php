<?php

use App\Services\Sync\Deltas\DeviceDeltaProvider;
use App\Services\Sync\Handlers\DeviceSyncHandler;

return [

    // Maximum operations accepted per POST /api/v1/sync/operations batch.
    'batch_max' => 100,

    // Maximum changed records returned per resource per delta request.
    'delta_limit' => 500,

    /*
     * Synchronisable resources. Apps built on the starter register theirs
     * here: 'resource-name' => ['handler' => ..., 'delta' => ...].
     * The devices entry is the reference implementation.
     */
    'resources' => [
        DeviceSyncHandler::RESOURCE => [
            'handler' => DeviceSyncHandler::class,
            'delta' => DeviceDeltaProvider::class,
        ],
    ],

];
