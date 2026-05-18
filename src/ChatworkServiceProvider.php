<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheStateStore;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheTokenRepository;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\Controllers\OAuthCallbackController;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthClient;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\StateStore;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenRepository;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
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

        $this->app->bind(StateStore::class, function (Application $app): StateStore {
            $configured = $app->make('config')->get('chatwork.oauth.state_store');
            if (is_string($configured) && $configured !== '') {
                $instance = $app->make($configured);
                if ($instance instanceof StateStore) {
                    return $instance;
                }
            }

            return new CacheStateStore($app->make('cache')->store());
        });

        $this->app->bind(TokenRepository::class, function (Application $app): TokenRepository {
            $configured = $app->make('config')->get('chatwork.oauth.token_repository');
            if (is_string($configured) && $configured !== '') {
                $instance = $app->make($configured);
                if ($instance instanceof TokenRepository) {
                    return $instance;
                }
            }

            return new CacheTokenRepository(
                $app->make('cache')->store(),
                $app->make('encrypter'),
            );
        });

        $this->app->bind(OAuthClient::class, function (Application $app): OAuthClient {
            $config = $app->make('config')->get('chatwork.oauth');

            return new OAuthClient(
                $app->make(StateStore::class),
                is_array($config) ? $config : [],
            );
        });

        $this->app->singleton('chatwork', function ($app) {
            $configured = $app->make('config')->get('chatwork.response.mode');

            return new ChatworkManager(
                $app,
                ResponseMode::fromConfig(is_string($configured) ? $configured : null),
            );
        });

        $this->app->alias('chatwork', ChatworkManager::class);
    }

    public function packageBooted(): void
    {
        Notification::resolved(function ($manager) {
            $manager->extend('chatwork', fn ($app) => $app->make(ChatworkChannel::class));
        });

        if ($this->app->make('config')->get('chatwork.oauth.routes_enabled') === true) {
            $this->registerOAuthRoutes();
        }
    }

    /**
     * OAuth2 callback ルートを登録する。
     *
     * `chatwork.oauth.routes_enabled=true` のとき `packageBooted()` が自動で呼び出す。
     * 意図的に public: `routes_enabled=false` のままにしつつ、独自の RouteServiceProvider
     * から任意の middleware / ドメイン / プレフィックス配下でこの callback ルートを
     * 手動登録したいアプリケーション向けの公開エントリポイントである。
     */
    public function registerOAuthRoutes(): void
    {
        $prefix = $this->app->make('config')->get('chatwork.oauth.route_prefix');
        $resolvedPrefix = is_string($prefix) && $prefix !== '' ? $prefix : 'chatwork/oauth';

        Route::prefix($resolvedPrefix)
            ->middleware('web')
            ->group(function (): void {
                Route::get('callback', OAuthCallbackController::class)
                    ->name('chatwork.oauth.callback');
            });

        Route::getRoutes()->refreshNameLookups();
    }
}
