<?php

namespace Titeca\SqlAnywhere;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Add database driver
        $this->app->resolving('db', function ($db) {
            $db->extend('sqlanywhere', function($config, $name) {

                $config = Arr::prepend($config, $name, 'name');
                $connector = new Database\Connector;

                return new Database\Connection(
                    $connector->connect($config),
                    @$config['database'] ?: '', @$config['prefix'] ?: '', $config
                );
            });
        });
    }
}
