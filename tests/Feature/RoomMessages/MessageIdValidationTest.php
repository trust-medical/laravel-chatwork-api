<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);

    // 漏れた場合に stray request ではなく明示的に失敗させるための受け皿。
    Http::fake(['https://api.chatwork.com/*' => Http::response([], 200)]);
});

it('find は不正な message_id を送信前に拒否する', function (string $messageId) {
    expect(fn () => Chatwork::rooms()->messages()->find(123, $messageId))
        ->toThrow(ChatworkValidationException::class);

    Http::assertNothingSent();
})->with(['abc', '0', '012', '', '5x', '-5', '5,6']);

it('update は不正な message_id を送信前に拒否する', function (string $messageId) {
    expect(fn () => Chatwork::rooms()->messages()->update(123, $messageId, 'body'))
        ->toThrow(ChatworkValidationException::class);

    Http::assertNothingSent();
})->with(['abc', '0', '012', '', '5x']);

it('deleteMessage は不正な message_id を送信前に拒否する', function (string $messageId) {
    expect(fn () => Chatwork::rooms()->messages()->deleteMessage(123, $messageId))
        ->toThrow(ChatworkValidationException::class);

    Http::assertNothingSent();
})->with(['abc', '0', '012', '', '5x']);

it('有効な数値文字列 message_id は通過する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/9999' => Http::response(
            fixtureJson('messages/get-message-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->messages()->find(123, '9999');

    Http::assertSent(fn ($r) => $r->url() === 'https://api.chatwork.com/v2/rooms/123/messages/9999');
});
