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
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkConfigurationException;
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

        $this->app->bind(StateStore::class, fn (Application $app): StateStore => $this->resolveConfigured($app, 'chatwork.oauth.state_store', StateStore::class)
            ?? new CacheStateStore($app->make('cache')->store()));

        $this->app->bind(TokenRepository::class, fn (Application $app): TokenRepository => $this->resolveConfigured($app, 'chatwork.oauth.token_repository', TokenRepository::class)
            ?? new CacheTokenRepository(
                $app->make('cache')->store(),
                $app->make('encrypter'),
            ));

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
     *
     * 既知の制約: `php artisan route:cache` 有効時は本メソッドのクロージャが
     * 実行されないため、`route_throttle` の形式検証も行われない。
     *
     * @throws ChatworkConfigurationException `route_throttle` が "max,minutes" でも limiter 名でもない場合。
     */
    public function registerOAuthRoutes(): void
    {
        $config = $this->app->make('config');
        $prefix = $config->get('chatwork.oauth.route_prefix');
        $resolvedPrefix = is_string($prefix) && $prefix !== '' ? $prefix : 'chatwork/oauth';

        $middleware = ['web'];
        $throttle = $config->get('chatwork.oauth.route_throttle');
        if ($throttle !== null && $throttle !== '') {
            if (! is_string($throttle)
                || (! preg_match('/^\d+,\d+$/', $throttle)
                    && ! preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $throttle))) {
                throw new ChatworkConfigurationException(sprintf(
                    'chatwork.oauth.route_throttle は "max,minutes"（例 "10,1"）または limiter 名である必要があります。got: %s',
                    is_scalar($throttle) ? (string) $throttle : get_debug_type($throttle),
                ));
            }

            $middleware[] = 'throttle:' . $throttle;
        }

        Route::prefix($resolvedPrefix)
            ->middleware($middleware)
            ->group(function (): void {
                // GET ルートのため `web` グループ配下でも Laravel の CSRF 検証対象外。
                // 本 callback の CSRF 防御は単回使用 state（OAuthCallbackController の
                // StateStore::pull() 検証）が担う。
                Route::get('callback', OAuthCallbackController::class)
                    ->name('chatwork.oauth.callback');
            });

        // `->name()` は RouteCollection::add() の後に適用されるため addLookups() が
        // このルートを nameList に登録できない。Laravel 自身の
        // RouteServiceProvider::loadRoutes() 末尾と同様、明示再構築が必須
        // （削除すると Route::has()/getByName()/route() が解決できなくなる）。
        Route::getRoutes()->refreshNameLookups();
    }

    /**
     * config 値で差し替え可能なサービス（state_store / token_repository）を解決する。
     *
     * 未設定（null / 空文字）なら null を返し、呼び出し側が既定実装へフォールバックする。
     * クラス名が指定されているのに存在しない／契約 interface を実装しないときは、
     * 黙ってフォールバックせず {@see ChatworkConfigurationException} で fail-loud にする。
     *
     * @template T of object
     *
     * @param  class-string<T>  $contract
     * @return T|null
     *
     * @throws ChatworkConfigurationException 指定クラスが不存在、または契約 interface 未実装の場合。
     */
    private function resolveConfigured(Application $app, string $configKey, string $contract): ?object
    {
        $configured = $app->make('config')->get($configKey);
        if (! is_string($configured) || $configured === '') {
            return null;
        }

        if (! class_exists($configured)) {
            throw new ChatworkConfigurationException(
                sprintf('%s に指定されたクラス %s が存在しません。', $configKey, $configured),
            );
        }

        $instance = $app->make($configured);
        if (! $instance instanceof $contract) {
            throw new ChatworkConfigurationException(sprintf(
                '%s は %s を実装する必要がありますが %s が解決されました。',
                $configKey,
                $contract,
                get_debug_type($instance),
            ));
        }

        return $instance;
    }
}
