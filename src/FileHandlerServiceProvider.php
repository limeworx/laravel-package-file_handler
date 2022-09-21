<?php

namespace Limeworx\FileHandler;

use Illuminate\support\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;

class FileHandlerServiceProvider extends ServiceProvider{

    public function boot(){
        $this->loadRoutesFrom(__DIR__.'/Routes/api.php'); 
        $this->loadMigrationsFrom(__DIR__.'/database/migrations'); 

        $this->publishes([
            __DIR__.'config/image.php'=>config_path('image.php')
        ]);
    }

}
