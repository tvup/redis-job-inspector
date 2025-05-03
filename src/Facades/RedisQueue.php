<?php

namespace RedisJobInspector\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RedisJobInspector\RedisQueue
 */
class RedisQueue extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RedisJobInspector\RedisQueue::class;
    }
}
