<?php

namespace App\Mixins\Integrations\Freshdesk;

use App\Services\FreshdeskService;
use Illuminate\Support\ServiceProvider;

class FreshdeskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Config::class);
        $this->app->singleton(RateLimitGuard::class);
        $this->app->singleton(RetryPolicy::class);
        $this->app->singleton(ResponseParser::class);
        $this->app->singleton(SignatureVerifier::class);

        $this->app->singleton(Client::class, function ($app) {
            return new Client(
                config: $app->make(Config::class),
                guard:  $app->make(RateLimitGuard::class),
                retry:  $app->make(RetryPolicy::class),
                parser: $app->make(ResponseParser::class),
            );
        });

        $this->app->singleton(FreshdeskService::class);
    }
}
