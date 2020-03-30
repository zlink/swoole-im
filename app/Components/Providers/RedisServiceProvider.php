<?php


namespace App\Components\Providers;


use App\Components\Services\RedisPool;
use Illuminate\Support\ServiceProvider;

class RedisServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('redis', function () {
            $config = $this->app->get('config');
            return new RedisPool($config->get('redis'));
        });
    }

}
