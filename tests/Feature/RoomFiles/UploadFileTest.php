<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Requests\UploadRoomFileRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\UploadedRoomFile;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('multipart ボディで POST /rooms/{room_id}/files を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-200.json'),
            200,
        ),
    ]);

    $path = tempUploadFile('PDF-CONTENT');

    Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(
        path: $path,
        filename: 'report.pdf',
    ));

    Http::assertSent(function (Request $r) {
        $ct = $r->header('Content-Type')[0] ?? '';

        return $r->method() === 'POST'
            && $r->url() === 'https://api.chatwork.com/v2/rooms/123/files'
            && $r->isMultipart()
            && str_contains($ct, 'multipart/form-data')
            && $r->hasFile('file', 'PDF-CONTENT', 'report.pdf');
    });
});

it('ファイル名が指定されない場合はパスの basename を使用する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-200.json'),
            200,
        ),
    ]);

    $path = tempUploadFile();

    Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(path: $path));

    Http::assertSent(fn (Request $r) => $r->hasFile('file', null, basename($path)));
});

it('message フィールドが指定された場合は送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-200.json'),
            200,
        ),
    ]);

    $path = tempUploadFile();

    Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(
        path: $path,
        message: 'See attached',
    ));

    // Laravel は multipart の全パート（ファイル・非ファイル問わず）を
    // hasFile() で公開する。非ファイルパートはファイル名を持たない。
    Http::assertSent(fn (Request $r) => $r->hasFile('message', 'See attached'));
});

it('message フィールドが指定されない場合は送信しない', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-200.json'),
            200,
        ),
    ]);

    $path = tempUploadFile();

    Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(path: $path));

    Http::assertSent(fn (Request $r) => ! $r->hasFile('message'));
});

it('api_token 接続時に x-chatworktoken ヘッダーを送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-200.json'),
            200,
        ),
    ]);

    $path = tempUploadFile();

    Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(path: $path));

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('asDto モードで UploadedRoomFile DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-200.json'),
            200,
        ),
    ]);

    $path = tempUploadFile();

    $result = Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(path: $path));

    expect($result)->toBeInstanceOf(UploadedRoomFile::class)
        ->and($result->fileId)->toBe(12345);
});

it('asArray モードで生の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-200.json'),
            200,
        ),
    ]);

    $path = tempUploadFile();

    $result = Chatwork::asArray()->rooms()->files()->upload(123, new UploadRoomFileRequest(path: $path));

    expect($result)->toBe(['file_id' => 12345]);
});

it('asResult モードで 400 時にスローせず失敗した Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-400.json'),
            400,
        ),
    ]);

    $path = tempUploadFile();

    $result = Chatwork::asResult()->rooms()->files()->upload(123, new UploadRoomFileRequest(path: $path));

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeTrue()
        ->and($result->status())->toBe(400)
        ->and($result->errors())->toBe(['file is required']);
});

it('空の message に対して HTTP を送信せず ChatworkValidationException をスローする', function () {
    Http::fake();

    $path = tempUploadFile();
    $caught = null;
    try {
        Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(
            path: $path,
            message: '',
        ));
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
    Http::assertNothingSent();
});

it('存在しないパスに対して ChatworkValidationException をスローする', function () {
    $caught = null;
    try {
        new UploadRoomFileRequest(path: '/no/such/file/at/all.bin');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('400 時に errors() を持つ ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-400.json'),
            400,
        ),
    ]);

    $path = tempUploadFile();
    $caught = null;
    try {
        Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(path: $path));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['file is required']);
});

it('429 時に rateLimit() を公開する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-429.json'),
            429,
            [
                'x-ratelimit-limit' => '200',
                'x-ratelimit-remaining' => '0',
                'x-ratelimit-reset' => '1735718400',
            ],
        ),
    ]);

    $path = tempUploadFile();
    $caught = null;
    try {
        Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(path: $path));
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
