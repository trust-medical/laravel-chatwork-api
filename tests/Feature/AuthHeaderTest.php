<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\ChatworkPendingRequestFactory;

beforeEach(function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response([], 200),
    ]);
});

it('withApiToken 使用時に x-chatworktoken を送信する (P1-T07)', function () {
    $manager = Chatwork::withApiToken('runtime-api-token');

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'runtime-api-token')
        && ! $r->hasHeader('Authorization')
    );
});

it('withBearerToken 使用時に Authorization Bearer を送信する (P1-T08)', function () {
    $manager = Chatwork::withBearerToken('runtime-bearer');

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer runtime-bearer')
        && ! $r->hasHeader('x-chatworktoken')
    );
});

it('両メソッドをチェーンしても認証ヘッダーを同時に送信しない (P1-T09)', function () {
    $manager = Chatwork::withApiToken('first')->withBearerToken('second');

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer second')
        && ! $r->hasHeader('x-chatworktoken')
    );
});

it('オーバーライドが未設定の場合はデフォルト接続の認証にフォールバックする', function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'default-api-token',
    ]);

    $manager = Chatwork::connection();

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'default-api-token'));
});

it('トークンをオーバーライドしても base_uri / timeout は元の接続の値を保持する', function () {
    config()->set('chatwork.connections.tenant', [
        'auth' => 'bearer',
        'token' => 'tenant-bearer',
    ]);

    $manager = Chatwork::connection('tenant')->withApiToken('override-api-token');
    $effective = $manager->getEffectiveConnection();

    expect($effective->name)->toBe('tenant')
        ->and($effective->baseUri)->toBe('https://api.chatwork.com/v2');

    app(ChatworkPendingRequestFactory::class)
        ->create($effective)
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'override-api-token')
        && ! $r->hasHeader('Authorization')
    );
});

it('withBearerToken はデフォルト接続の static credentials が未設定でも動作する (v1.0.2)', function () {
    // OAuth 専用プロジェクトを想定: default connection は api_token ドライバ宣言だけ残し、
    // token は未設定 (null)。withBearerToken は動的トークンを使うため、
    // base 側 credentials の解決は不要のはず。
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => null,
    ]);

    $manager = Chatwork::withBearerToken('OAUTH_ACCESS_TOKEN');

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer OAUTH_ACCESS_TOKEN')
        && ! $r->hasHeader('x-chatworktoken')
    );
});

it('withApiToken はデフォルト接続の static credentials が未設定でも動作する (v1.0.2)', function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => null,
    ]);

    $manager = Chatwork::withApiToken('RUNTIME_API_TOKEN');

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'RUNTIME_API_TOKEN')
        && ! $r->hasHeader('Authorization')
    );
});

it('オーバーライド時に base 接続の base_uri / timeout は credentials を解決せず引き継ぐ (v1.0.2)', function () {
    // base connection の auth ドライバは設定不正 (token 未設定) だが、
    // base_uri / timeout は connection 個別設定として有効。
    // override 時はそれらだけを引き継ぎ、credentials 解決はスキップする。
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => null,
        'base_uri' => 'https://api.chatwork.com/v2',
        'timeout' => 7,
    ]);

    $manager = Chatwork::withBearerToken('OAUTH_ACCESS_TOKEN');
    $effective = $manager->getEffectiveConnection();

    expect($effective->name)->toBe('default')
        ->and($effective->baseUri)->toBe('https://api.chatwork.com/v2')
        ->and($effective->timeout)->toBe(7);
});

it('オーバーライド無しの場合はデフォルト接続の credentials を要求する (regression)', function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => null,
    ]);

    expect(fn () => Chatwork::getEffectiveConnection())
        ->toThrow(
            ChatworkAuthenticationException::class,
        );
});

it('明示的に connection() を呼ぶ場合は引き続き credentials が必須 (regression)', function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => null,
    ]);

    expect(fn () => Chatwork::connection('default'))
        ->toThrow(
            ChatworkAuthenticationException::class,
        );
});
