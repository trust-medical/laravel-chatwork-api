<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\MessageData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('force が null のとき /rooms/{room_id}/messages をクエリなしで GET する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/list-messages-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->messages()->list(123);

    Http::assertSent(function (Request $r) {
        return $r->method() === 'GET'
            && $r->url() === 'https://api.chatwork.com/v2/rooms/123/messages'
            && ! isset($r->data()['force']);
    });
});

it('force が true のとき force=1 クエリを送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages*' => Http::response(
            fixtureJson('messages/list-messages-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->messages()->list(123, force: true);

    Http::assertSent(fn (Request $r) => ($r->data()['force'] ?? null) === 1);
});

it('asDto モードで MessageData の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/list-messages-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->messages()->list(123);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0])->toBeInstanceOf(MessageData::class);
    expect($result[0]->messageId)->toBe('5');
    expect($result[1]->messageId)->toBe('6');
});

it('asCollection モードで MessageData の Collection を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/list-messages-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->rooms()->messages()->list(123);
    /** @var Collection<int, MessageData> $result */
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(2);
    expect($result->first())->toBeInstanceOf(MessageData::class);
});

it('asArray モードで配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/list-messages-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->rooms()->messages()->list(123);
    /** @var array<int, array<string, mixed>> $result */
    expect($result)->toBeArray();
    expect($result[0]['message_id'])->toBe('5');
});

it('204 No Content のとき空配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/list-messages-204.json'),
            204,
        ),
    ]);

    $result = Chatwork::rooms()->messages()->list(123);

    expect($result)->toBe([]);
});

it('asCollection モードで 204 のとき空の Collection を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/list-messages-204.json'),
            204,
        ),
    ]);

    $result = Chatwork::asCollection()->rooms()->messages()->list(123);
    /** @var Collection<int, MessageData> $result */
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(0);
});

it('401 で ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            ['errors' => ['unauthorized']],
            401,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->messages()->list(123);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkRequestException::class)
        ->and($caught?->status())->toBe(401);
});
