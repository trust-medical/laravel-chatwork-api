<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use TrustMedical\LaravelChatworkApi\Data\Responses\MyAccountData;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage;

it('config の response.mode が singleton manager の既定モードに反映される', function () {
    config()->set('chatwork.response.mode', 'result');
    app()->forgetInstance('chatwork');

    expect(app('chatwork')->getMode())->toBe(ResponseMode::Result);
});

it('config の response.mode=array が API 呼び出しの既定戻り値に伝播する', function () {
    config()->set('chatwork.response.mode', 'array');
    config()->set('chatwork.connections.default', ['auth' => 'api_token', 'token' => 't']);
    app()->forgetInstance('chatwork');

    Http::fake([
        'https://api.chatwork.com/v2/me' => Http::response(fixtureJson('me/get-me-200.json'), 200),
    ]);

    $result = app('chatwork')->me()->get();

    expect($result)->toBeArray()
        ->and($result)->not->toBeInstanceOf(MyAccountData::class);
});

it('無効な config の response.mode は解決時に ChatworkValidationException をスローする', function () {
    config()->set('chatwork.response.mode', 'totally-invalid');
    app()->forgetInstance('chatwork');

    $caught = null;
    try {
        app('chatwork');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class)
        ->and($caught?->violations())->toHaveKey('chatwork.response.mode');
});

it('通知送信は config の既定モードに漏れず asResult 固定で 4xx を例外化する', function () {
    config()->set('chatwork.response.mode', 'array');
    config()->set('chatwork.connections.default', ['auth' => 'api_token', 'token' => 't']);
    app()->forgetInstance('chatwork');

    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response(['errors' => ['nope']], 400),
    ]);

    $caught = null;
    try {
        Notification::route('chatwork', 111)->notify(chatworkNotification(new ChatworkMessage('Hi')));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkRequestException::class)
        ->and($caught?->status())->toBe(400);
});
