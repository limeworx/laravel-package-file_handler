<?php

namespace Limeworx\FileHandler;

use Illuminate\support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

class FileHandlerServiceProvider extends ServiceProvider{

    public function boot(){
        $this->loadRoutesFrom(__DIR__.'/routes/api.php'); 
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->publishes([
            __DIR__.'config/image.php'=>config_path('image.php')
        ]);
    }

    public function register(){
        $this->registerEloquentFactoriesFrom(__DIR__.'/database/factories');
    }

    /**
     * Register factories.
     *
     * @param  string  $path
     * @return void
     */
    protected function registerEloquentFactoriesFrom($path)
    {
        $this->app->make(EloquentFactory::class)->load($path);
    }
}