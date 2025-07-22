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
            ]);
            $this->commands([
                \IslamWalied\OneClickModule\Commands\PublishHelpers::class,
            ]);
            $this->commands([
                \IslamWalied\OneClickModule\Commands\PublishLogging::class,
            ]);
            $this->commands([
                \IslamWalied\OneClickModule\Commands\PublishMiddlewares::class,
            ]);

        }
    }
}