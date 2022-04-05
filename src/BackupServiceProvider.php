<?php

namespace Leolopez\Backup;

use Leolopez\Backup\Commands\CreateBackupCommand;
use Illuminate\Support\ServiceProvider;
use Leolopez\Backup\Facades\Backup;

class BackupServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/backup.php',
            'backup'
        );

        $this->publishes([
            __DIR__.'/config/backup.php' => config_path('backup.php')
        ]);
    }

    public function register()
    {
        $this->app->singleton('backup:create', function ($app) {
            return new Backup();
        });

        $this->commands([
            CreateBackupCommand::class,
        ]);
    }
}
