<?php

namespace App\Services\Sync;

use App\Contracts\Sync\DeltaProvider;
use App\Contracts\Sync\SyncResourceHandler;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves sync handlers and delta providers per resource name from
 * config/sync.php. Apps built on the starter register their resources there.
 */
class SyncRegistry
{
    public function __construct(private readonly Container $container) {}

    /** @return list<string> */
    public function resources(): array
    {
        return array_keys(config('sync.resources', []));
    }

    public function handlerFor(string $resource): ?SyncResourceHandler
    {
        $class = config("sync.resources.{$resource}.handler");

        return $class ? $this->container->make($class) : null;
    }

    public function deltaFor(string $resource): ?DeltaProvider
    {
        $class = config("sync.resources.{$resource}.delta");

        return $class ? $this->container->make($class) : null;
    }

    /** @return list<string> resources that expose delta reads */
    public function deltaResources(): array
    {
        return array_keys(array_filter(
            config('sync.resources', []),
            fn (array $entry) => isset($entry['delta'])
        ));
    }
}
