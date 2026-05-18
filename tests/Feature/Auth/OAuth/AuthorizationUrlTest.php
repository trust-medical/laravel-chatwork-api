<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheStateStore;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthClient;

beforeEach(function () {
    Config::set('cache.default', 'array');
    Config::set('chatwork.oauth.client_id', 'client-abc');
    Config::set('chatwork.oauth.redirect_uri', 'https://app.example.com/callback');
    Config::set('chatwork.oauth.authorization_url', 'https://www.chatwork.com/packages/oauth2/login.php');
});

it('必須クエリパラメータを含む認可URLを組み立てる', function () {
    $client = new OAuthClient(
        new CacheStateStore(Cache::store()),
        config('chatwork.oauth'),
    );

    $url = $client->buildAuthorizationUrl();

    expect($url)->toStartWith('https://www.chatwork.com/packages/oauth2/login.php?');

    $query = [];
    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect($query)->toHaveKeys(['client_id', 'redirect_uri', 'response_type', 'state']);
    expect($query['client_id'])->toBe('client-abc');
    expect($query['redirect_uri'])->toBe('https://app.example.com/callback');
    expect($query['response_type'])->toBe('code');
});

it('スコープが指定された場合はscopeを含む', function () {
    $client = new OAuthClient(
        new CacheStateStore(Cache::store()),
        config('chatwork.oauth'),
    );

    $url = $client->buildAuthorizationUrl(scopes: ['rooms.all:read_write', 'users.profile.me:read']);

    $query = [];
    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect($query)->toHaveKey('scope');
    expect($query['scope'])->toBe('rooms.all:read_write users.profile.me:read');
});

it('期待するペイロードとともにstateをStateStoreへ保存する', function () {
    $store = new CacheStateStore(Cache::store());
    $client = new OAuthClient($store, config('chatwork.oauth'));

    $url = $client->buildAuthorizationUrl(context: ['return_to' => 'dashboard']);

    $query = [];
    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    $payload = $store->pull($query['state']);

    expect($payload)->not->toBeNull();
    expect($payload['context'])->toBe(['return_to' => 'dashboard']);
});

it('48文字の16進数で暗号学的にランダムなstateを生成する', function () {
    $client = new OAuthClient(
        new CacheStateStore(Cache::store()),
        config('chatwork.oauth'),
    );

    $url1 = $client->buildAuthorizationUrl();
    $url2 = $client->buildAuthorizationUrl();

    $query1 = [];
    $query2 = [];
    parse_str(parse_url($url1, PHP_URL_QUERY), $query1);
    parse_str(parse_url($url2, PHP_URL_QUERY), $query2);

    expect($query1['state'])->toMatch('/^[0-9a-f]{48}$/');
    expect($query2['state'])->toMatch('/^[0-9a-f]{48}$/');
    expect($query1['state'])->not->toBe($query2['state']);
});
