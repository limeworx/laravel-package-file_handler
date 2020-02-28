<?php

namespace Limeworx\FileHandler;

use Illuminate\support\ServiceProvider;

class FileHandlerServiceProvider extends ServiceProvider{

    public function boot(){
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations.php');
    }

    public function register(){

    }
}