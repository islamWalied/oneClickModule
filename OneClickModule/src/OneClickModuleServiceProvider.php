<?php

namespace IslamWalied\OneClickModule;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class OneClickModuleServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \IslamWalied\OneClickModule\Commands\PublishTraits::class,
                \IslamWalied\OneClickModule\Commands\PublishHelpers::class,
                \IslamWalied\OneClickModule\Commands\PublishLogging::class,
                \IslamWalied\OneClickModule\Commands\PublishMiddlewares::class,
                \IslamWalied\OneClickModule\Commands\generateModule::class,
                \IslamWalied\OneClickModule\Commands\DeleteModuleEntity::class,
            ]);

        }
    }
}