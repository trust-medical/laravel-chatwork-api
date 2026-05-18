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

it('POSTs /rooms/{room_id}/files with a multipart body', function () {
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

it('uses the basename of the path when no filename override is given', function () {
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

it('sends the message field when provided', function () {
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

    // Laravel exposes every multipart part (file and non-file) through
    // hasFile(); non-file parts simply have no filename.
    Http::assertSent(fn (Request $r) => $r->hasFile('message', 'See attached'));
});

it('omits the message field when not provided', function () {
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

it('sends x-chatworktoken header for api_token connection', function () {
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

it('returns UploadedRoomFile DTO in asDto mode', function () {
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

it('returns the raw array in asArray mode', function () {
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

it('returns a failed Result on 400 in asResult mode without throwing', function () {
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

it('throws ChatworkValidationException for an empty message without sending HTTP', function () {
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

it('throws ChatworkValidationException for a non-existent path', function () {
    $caught = null;
    try {
        new UploadRoomFileRequest(path: '/no/such/file/at/all.bin');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('throws ChatworkRequestException with errors() on 400', function () {
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

it('exposes rateLimit() on 429', function () {
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
