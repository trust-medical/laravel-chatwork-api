<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Requests\UploadRoomFileRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\UploadedRoomFile;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

function tmpUploadFile(string $contents = 'hello world'): string
{
    $path = (string) tempnam(sys_get_temp_dir(), 'cwfile');
    file_put_contents($path, $contents);
    register_shutdown_function(static fn () => @unlink($path));

    return $path;
}

it('POSTs /rooms/{room_id}/files with a multipart body', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-200.json'),
            200,
        ),
    ]);

    $path = tmpUploadFile('PDF-CONTENT');

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

    $path = tmpUploadFile();

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

    $path = tmpUploadFile();

    Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(
        path: $path,
        message: 'See attached',
    ));

    Http::assertSent(fn (Request $r) => $r['message'] === 'See attached');
});

it('omits the message field when not provided', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-200.json'),
            200,
        ),
    ]);

    $path = tmpUploadFile();

    Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(path: $path));

    Http::assertSent(fn (Request $r) => ! isset($r->data()['message']));
});

it('sends x-chatworktoken header for api_token connection', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/upload-room-file-200.json'),
            200,
        ),
    ]);

    $path = tmpUploadFile();

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

    $path = tmpUploadFile();

    $result = Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(path: $path));

    expect($result)->toBeInstanceOf(UploadedRoomFile::class)
        ->and($result->fileId)->toBe(12345);
});

it('throws ChatworkValidationException for an empty message without sending HTTP', function () {
    Http::fake();

    $path = tmpUploadFile();
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

    $path = tmpUploadFile();
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

    $path = tmpUploadFile();
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
