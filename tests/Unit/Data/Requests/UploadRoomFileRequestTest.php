<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\UploadRoomFileRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

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
    $path = tempUploadFile('');

    $caught = null;
    try {
        new UploadRoomFileRequest(path: $path);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('accepts a file of exactly 5 MiB (upper bound is inclusive)', function () {
    $path = tempUploadFile(truncateTo: 5_242_880);

    $request = new UploadRoomFileRequest(path: $path);

    expect($request->filename())->toBe(basename($path));
});

it('rejects a file larger than 5 MiB', function () {
    $path = tempUploadFile(truncateTo: 5_242_880 + 1);

    $caught = null;
    try {
        new UploadRoomFileRequest(path: $path);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects an empty message string', function () {
    $path = tempUploadFile();

    $caught = null;
    try {
        new UploadRoomFileRequest(path: $path, message: '');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects a message longer than 65535 characters', function () {
    $path = tempUploadFile();

    $caught = null;
    try {
        new UploadRoomFileRequest(path: $path, message: str_repeat('a', 65536));
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('defaults filename to the basename of the path', function () {
    $path = tempUploadFile('data');

    $request = new UploadRoomFileRequest(path: $path);

    expect($request->filename())->toBe(basename($path));
});

it('uses the explicit filename override when given', function () {
    $path = tempUploadFile('data');

    $request = new UploadRoomFileRequest(path: $path, filename: 'monthly.pdf');

    expect($request->filename())->toBe('monthly.pdf');
});

it('reads the file contents lazily via contents()', function () {
    $path = tempUploadFile('binary-bytes');

    $request = new UploadRoomFileRequest(path: $path);

    expect($request->contents())->toBe('binary-bytes');
});

it('includes message in toFields only when provided', function () {
    $path = tempUploadFile();

    expect((new UploadRoomFileRequest(path: $path))->toFields())->toBe([]);
    expect((new UploadRoomFileRequest(path: $path, message: 'hi'))->toFields())
        ->toBe(['message' => 'hi']);
});
