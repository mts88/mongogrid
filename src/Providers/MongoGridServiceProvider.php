<?php

namespace Mts88\MongoGrid\Providers;

use \Illuminate\Support\ServiceProvider;
use \Mts88\MongoGrid\Services\MongoGrid;

class MongoGridServiceProvider extends ServiceProvider
{
    protected $defer = true;

    const CONFIG_URI = '/../../config/gridfs.php';

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
			__DIR__. self::CONFIG_URI => config_path('gridfs.php'),
		]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__. self::CONFIG_URI, 'gridfs'
        );

        $this->app->singleton(MongoGrid::class, function ($app) {
            return new MongoGrid();
        });
    }

        /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [MongoGrid::class];
    }
}
