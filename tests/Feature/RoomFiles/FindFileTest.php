<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomFileData;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleAccount;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('デフォルトではクエリなしで GET /rooms/{room_id}/files/{file_id} を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files/99' => Http::response(
            fixtureJson('files/get-room-file-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->files()->find(123, 99);

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123/files/99'
        && $r->data() === []);
});

it('要求時に create_download_url=1 を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files/99*' => Http::response(
            fixtureJson('files/get-room-file-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->files()->find(123, 99, createDownloadUrl: true);

    Http::assertSent(fn (Request $r) => $r['create_download_url'] === 1);
});

it('明示的に無効化した場合は create_download_url=0 を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files/99*' => Http::response(
            fixtureJson('files/get-room-file-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->files()->find(123, 99, createDownloadUrl: false);

    Http::assertSent(fn (Request $r) => $r['create_download_url'] === 0);
});

it('ネストした SimpleAccount とダウンロード URL を含む RoomFileData DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files/99' => Http::response(
            fixtureJson('files/get-room-file-200.json'),
            200,
        ),
    ]);

    $file = Chatwork::rooms()->files()->find(123, 99);

    expect($file)->toBeInstanceOf(RoomFileData::class)
        ->and($file->fileId)->toBe(99)
        ->and($file->account)->toBeInstanceOf(SimpleAccount::class)
        ->and($file->account->accountId)->toBe(123)
        ->and($file->messageId)->toBe('22')
        ->and($file->filename)->toBe('report.pdf')
        ->and($file->filesize)->toBe(2048)
        ->and($file->uploadTime)->toBe(1735707600)
        ->and($file->downloadUrl)->toBe('https://download.chatwork.com/files/99?token=abc');
});

it('400 時に errors() を持つ ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files/99' => Http::response(
            fixtureJson('files/get-room-file-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->files()->find(123, 99);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['room_id is invalid']);
});

it('404 時に ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files/99' => Http::response(
            fixtureJson('files/get-room-file-404.json'),
            404,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->files()->find(123, 99);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(404)
        ->and($caught?->errors())->toBe(['file not found']);
});

it('429 時に rateLimit() を公開する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files/99' => Http::response(
            fixtureJson('files/get-room-file-429.json'),
            429,
            [
                'x-ratelimit-limit' => '200',
                'x-ratelimit-remaining' => '0',
                'x-ratelimit-reset' => '1735718400',
            ],
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->files()->find(123, 99);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(429)
        ->and($caught?->rateLimit())->toBe([
            'limit' => 200,
            'remaining' => 0,
            'reset' => 1735718400,
        ]);
});
