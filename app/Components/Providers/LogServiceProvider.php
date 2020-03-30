<?php


namespace App\Components\Providers;


use Exception;
use Illuminate\Support\ServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('log', function () {
            try {
                $logger = new Logger('app');
                $path = $this->app->get('path.storage');
                $file = $path . sprintf('/logs/app-%s.log', date('Y-m-d'));
                // todo:: ad async file driver or others
//                $logger->pushHandler(new \App\Handlers\AsyncLogStreamHandler($file, Logger::DEBUG));
                $logger->pushHandler(new StreamHandler($file, LOG_DEBUG));
            } catch (Exception $exception) {
                throw new Exception("Can not load logger handler");
            }
            return $logger;
        });
    }
}
