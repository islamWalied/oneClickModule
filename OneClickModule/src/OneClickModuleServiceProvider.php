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
        }
    }
}