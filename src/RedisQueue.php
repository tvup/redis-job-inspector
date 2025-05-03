<?php

namespace RedisJobInspector;

class RedisQueue
{
    public function jobs(): RedisJobQueryBuilder
    {
        return new RedisJobQueryBuilder();
    }
}
