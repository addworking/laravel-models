<?php

namespace Addworking\LaravelModels\Providers;

use Addworking\LaravelModels\ModelFinder;
use Illuminate\Support\ServiceProvider;

class ModelsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        $this->app->make('laravel-models')->registerFunctions();
    }

    public function register()
    {
        $this->app->singleton('laravel-models', function ($app) {
            return $app->make(ModelFinder::class)
                ->setDirectories($app['config']->get('models.directories'))
                ->setAliases($app['config']->get('models.aliases'));
        });
    }

    public function provides()
    {
        return ['laravel-models'];
    }

    protected function bootForConsole()
    {
        // Publishing config.
        $this->publishes([
            base_path('vendor/addworking/laravel-models/config/models.php') => config_path('models.php'),
        ], 'laravel-models');
    }
}
