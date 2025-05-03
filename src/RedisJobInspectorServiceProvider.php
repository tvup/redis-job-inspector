<?php
namespace RedisJobInspector;

use Illuminate\Support\ServiceProvider;
use RedisJobInspector\Facades\RedisQueue;

class RedisJobInspectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RedisQueue::class, fn () => new RedisQueue());
    }
}
