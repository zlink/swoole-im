<?php


namespace App\Components\Providers;


use Illuminate\Support\ServiceProvider;
use Noodlehaus\Config;
use Noodlehaus\Parser\Php;

class ConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('config', function () {
            return Config::load($this->app->get('path.config'), new Php(), false);
        });
    }

}