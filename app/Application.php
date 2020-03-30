<?php


namespace App;


use App\Components\Providers\ConfigServiceProvider;
use App\Components\Providers\LogServiceProvider;
use App\Components\Providers\MongodbServiceProvider;
use App\Components\Providers\RedisServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;

class Application extends Container
{

    protected $basePath;

    protected $appPath;

    protected $configPath;

    protected $storagePath;

    protected $env;

    protected $namespace;

    protected $serviceProviders = [];

    protected $loadedProvider = [];

    protected $booted;

    protected $loadedProviders = [];

    /**
     * Application constructor.
     * @param $basePath
     */
    public function __construct($basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
    }

    /**
     * @param mixed $basePath
     * @return Application
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    protected function bindPathsInContainer()
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.storage', $this->storagePath());
    }

    protected function path($path = '')
    {
        $appPath = $this->appPath ?: $this->basePath . DIRECTORY_SEPARATOR . 'app';

        return $appPath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    protected function basePath($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    protected function configPath($path = '')
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'config' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    protected function storagePath()
    {
        return $this->storagePath ?: $this->basePath . DIRECTORY_SEPARATOR . 'storage';
    }

    protected function registerBaseServiceProviders()
    {
        $this->register(new ConfigServiceProvider($this));
        $this->register(new LogServiceProvider($this));
        $this->register(new RedisServiceProvider($this));
        $this->register(new MongodbServiceProvider($this));
    }

    protected function registerBaseBindings()
    {
        static::setInstance($this);
        $this->instance('app', $this);

        $this->instance(Container::class, $this);
    }

    protected function register($provider, $force = false)
    {
        if (($registered = $this->getProvider($provider)) && !$force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        $provider->register();

        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }

        $this->markAsRegistered($provider);

        if ($this->booted) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    public function getProvider($provider)
    {
        return array_values($this->getProviders($provider))[0] ?? null;
    }

    public function getProviders($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::where($this->serviceProviders, function ($value) use ($name) {
            return $value instanceof $name;
        });
    }

    public function resolveProvider(string $provider)
    {
        return new $provider($this);
    }

    public function markAsRegistered($provider)
    {
        $this->serviceProviders[] = $provider;

        $this->loadedProviders[get_class($provider)] = true;
    }

    public function bootProvider($provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }
    }

}

