<?php


namespace App\Components\Providers;


use Illuminate\Support\ServiceProvider;
use MongoDB\Client;

class MongodbServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind(Client::class, function () {
            $config = $this->app->get('config');
            $uri = $config->get('mongo.host');
            $port = $config->get('mongo.port');
            $user = $config->get('mongo.username');
            $password = $config->get('mongo.password');
            return new Client(sprintf('mongodb://%s:%s@%s:%s', $user, $password, $uri, $port));
        });
    }

}
