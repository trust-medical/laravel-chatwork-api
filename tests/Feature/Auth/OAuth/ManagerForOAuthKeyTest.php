<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenRepository;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenSet;
use TrustMedical\LaravelChatworkApi\ChatworkManager;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkConfigurationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    Config::set('cache.default', 'array');
    Config::set('chatwork.base_uri', 'https://api.chatwork.com/v2');
    Config::set('chatwork.default', 'default');
    Config::set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'placeholder-token',
    ]);
    Config::set('chatwork.oauth.client_id', 'client-abc');
    Config::set('chatwork.oauth.client_secret', 'super-secret');
    Config::set('chatwork.oauth.token_url', 'https://oauth.chatwork.com/token');
    Config::set('chatwork.oauth.refresh_leeway_seconds', 60);
    Carbon::setTestNow('2026-05-15 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('期限内トークンで Authorization: Bearer ヘッダーを送信し x-chatworktoken は付けない', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/create-message-201.json'),
            201,
        ),
    ]);

    app(TokenRepository::class)->save('user-42', new TokenSet(
        accessToken: 'fresh-access-42',
        refreshToken: 'refresh-42',
        expiresAt: Carbon::now()->addHour()->toDateTimeImmutable(),
    ));

    Chatwork::forOAuthKey('user-42')->rooms()->messages()->create(123, 'Hello');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer fresh-access-42')
        && ! $r->hasHeader('x-chatworktoken')
    );
});

it('該当 key のトークンが無いと ChatworkAuthenticationException を投げ HTTP を送信しない', function () {
    Http::fake();

    expect(fn () => Chatwork::forOAuthKey('unknown-user'))
        ->toThrow(ChatworkAuthenticationException::class);

    Http::assertNothingSent();
});

it('期限切れトークンを refresh して TokenRepository へ永続化したうえでリクエストする', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/create-message-201.json'),
            201,
        ),
    ]);

    $repository = app(TokenRepository::class);
    $repository->save('user-77', new TokenSet(
        accessToken: 'expired-77',
        refreshToken: 'rt-77',
        expiresAt: Carbon::now()->subSecond()->toDateTimeImmutable(),
    ));

    Chatwork::forOAuthKey('user-77')->rooms()->messages()->create(123, 'Hi');

    $saved = $repository->find('user-77');
    expect($saved?->accessToken)->toBe('sample-access-token');

    Http::assertSent(fn (Request $r) => $r->url() === 'https://oauth.chatwork.com/token'
        && ($r['grant_type'] ?? null) === 'refresh_token'
    );
    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/123/messages'
        && $r->hasHeader('Authorization', 'Bearer sample-access-token')
    );
});

it('空 key で ChatworkConfigurationException を投げ HTTP を送信しない', function () {
    Http::fake();

    expect(fn () => Chatwork::forOAuthKey(''))
        ->toThrow(ChatworkConfigurationException::class);

    Http::assertNothingSent();
});

it('$base 省略時は config(chatwork.default) のエントリから baseUri と timeout を継承する', function () {
    Config::set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'placeholder',
        'base_uri' => 'https://default-uri.example/v2',
        'timeout' => 11,
    ]);

    app(TokenRepository::class)->save('user-5', new TokenSet(
        accessToken: 'a5',
        refreshToken: 'r5',
        expiresAt: Carbon::now()->addHour()->toDateTimeImmutable(),
    ));

    $connection = Chatwork::forOAuthKey('user-5')->getEffectiveConnection();

    expect($connection->baseUri)->toBe('https://default-uri.example/v2');
    expect($connection->timeout)->toBe(11);
});

it('$base 明示時は当該 connection の baseUri と timeout を継承し TokenRepository キーは $key を使う', function () {
    Config::set('chatwork.connections.alt-base', [
        'auth' => 'api_token',
        'token' => 'placeholder',
        'base_uri' => 'https://alt-base.example/v2',
        'timeout' => 22,
    ]);

    $repository = app(TokenRepository::class);
    $repository->save('user-6', new TokenSet(
        accessToken: 'a6',
        refreshToken: 'r6',
        expiresAt: Carbon::now()->addHour()->toDateTimeImmutable(),
    ));

    $connection = Chatwork::forOAuthKey('user-6', 'alt-base')->getEffectiveConnection();

    expect($connection->baseUri)->toBe('https://alt-base.example/v2');
    expect($connection->timeout)->toBe(22);
    expect($repository->find('alt-base'))->toBeNull();
    expect($repository->find('user-6'))->not->toBeNull();
});

it('存在しない $base を指定すると ChatworkAuthenticationException を投げる', function () {
    expect(fn () => Chatwork::forOAuthKey('user-7', 'missing-base'))
        ->toThrow(ChatworkAuthenticationException::class);
});

it('forOAuthKey() は元の manager を変更しない (immutable clone)', function () {
    app(TokenRepository::class)->save('user-8', new TokenSet(
        accessToken: 'a8',
        refreshToken: 'r8',
        expiresAt: Carbon::now()->addHour()->toDateTimeImmutable(),
    ));

    $original = app(ChatworkManager::class);
    $reflection = new ReflectionClass($original);
    $connectionProperty = $reflection->getProperty('connection');

    expect($connectionProperty->getValue($original))->toBeNull();

    $derived = $original->forOAuthKey('user-8');

    expect($derived)->not->toBe($original);
    expect($connectionProperty->getValue($original))->toBeNull();
});

it('mode chain が asResult()->forOAuthKey() / forOAuthKey()->asResult() のどちら順でも Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            ['errors' => ['oops']],
            400,
        ),
    ]);

    app(TokenRepository::class)->save('user-9', new TokenSet(
        accessToken: 'a9',
        refreshToken: 'r9',
        expiresAt: Carbon::now()->addHour()->toDateTimeImmutable(),
    ));

    $first = Chatwork::asResult()->forOAuthKey('user-9')->rooms()->messages()->create(123, 'X');
    $second = Chatwork::forOAuthKey('user-9')->asResult()->rooms()->messages()->create(123, 'X');

    expect($first)->toBeInstanceOf(Result::class);
    expect($first->failed())->toBeTrue();
    expect($second)->toBeInstanceOf(Result::class);
    expect($second->failed())->toBeTrue();
});

it('返ってきた Connection の name は "oauth:{$key}" 形式になる', function () {
    app(TokenRepository::class)->save('u42', new TokenSet(
        accessToken: 'a',
        refreshToken: 'r',
        expiresAt: Carbon::now()->addHour()->toDateTimeImmutable(),
    ));

    $connection = Chatwork::forOAuthKey('u42')->getEffectiveConnection();

    expect($connection->name)->toBe('oauth:u42');
});
