<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi;

use Illuminate\Support\Facades\Notification;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthClient;
use TrustMedical\LaravelChatworkApi\Http\ChatworkPendingRequestFactory;
use TrustMedical\LaravelChatworkApi\Http\ResponseMapper;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkChannel;

final class ChatworkServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('chatwork')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ChatworkPendingRequestFactory::class);
        $this->app->singleton(ResponseMapper::class);
        $this->app->singleton(OAuthClient::class);

        $this->app->singleton('chatwork', function ($app) {
            return new ChatworkManager($app);
        });

        $this->app->alias('chatwork', ChatworkManager::class);
    }

    public function packageBooted(): void
    {
        Notification::resolved(function ($manager) {
            $manager->extend('chatwork', fn ($app) => $app->make(ChatworkChannel::class));
        });
    }
}
