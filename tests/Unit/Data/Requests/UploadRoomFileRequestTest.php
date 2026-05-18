<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\UploadRoomFileRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

function tmpReqFile(string $contents = 'hello'): string
{
    $path = (string) tempnam(sys_get_temp_dir(), 'cwreq');
    file_put_contents($path, $contents);
    register_shutdown_function(static fn () => @unlink($path));

    return $path;
}

it('rejects a non-existent path', function () {
    $caught = null;
    try {
        new UploadRoomFileRequest(path: '/definitely/not/here.bin');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects an empty (zero byte) file', function () {
    $path = tmpReqFile('');

    $caught = null;
    try {
        new UploadRoomFileRequest(path: $path);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects a file larger than 5 MiB', function () {
    $path = (string) tempnam(sys_get_temp_dir(), 'cwbig');
    register_shutdown_function(static fn () => @unlink($path));
    $fp = fopen($path, 'w');
    ftruncate($fp, 5 * 1024 * 1024 + 1); // sparse file, no real IO
    fclose($fp);

    $caught = null;
    try {
        new UploadRoomFileRequest(path: $path);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects an empty message string', function () {
    $path = tmpReqFile();

    $caught = null;
    try {
        new UploadRoomFileRequest(path: $path, message: '');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects a message longer than 65535 characters', function () {
    $path = tmpReqFile();

    $caught = null;
    try {
        new UploadRoomFileRequest(path: $path, message: str_repeat('a', 65536));
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('defaults filename to the basename of the path', function () {
    $path = tmpReqFile('data');

    $request = new UploadRoomFileRequest(path: $path);

    expect($request->filename())->toBe(basename($path));
});

it('uses the explicit filename override when given', function () {
    $path = tmpReqFile('data');

    $request = new UploadRoomFileRequest(path: $path, filename: 'monthly.pdf');

    expect($request->filename())->toBe('monthly.pdf');
});

it('reads the file contents lazily via contents()', function () {
    $path = tmpReqFile('binary-bytes');

    $request = new UploadRoomFileRequest(path: $path);

    expect($request->contents())->toBe('binary-bytes');
});

it('includes message in toFields only when provided', function () {
    $path = tmpReqFile();

    expect((new UploadRoomFileRequest(path: $path))->toFields())->toBe([]);
    expect((new UploadRoomFileRequest(path: $path, message: 'hi'))->toFields())
        ->toBe(['message' => 'hi']);
});
