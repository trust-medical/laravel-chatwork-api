<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\UploadRoomFileRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('存在しないパスを拒否する', function () {
    $caught = null;
    try {
        new UploadRoomFileRequest(path: '/definitely/not/here.bin');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('URL パスを拒否する（SSRF 抑止）', function () {
    $caught = null;
    try {
        new UploadRoomFileRequest(path: 'http://169.254.169.254/latest/meta-data/');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class)
        ->and($caught?->getMessage())->toContain('URL or stream wrapper')
        ->and($caught?->violations())->toHaveKey('file');
});

it('ストリームラッパーパスを拒否する', function (string $wrapper) {
    $caught = null;
    try {
        new UploadRoomFileRequest(path: $wrapper);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class)
        ->and($caught?->getMessage())->toContain('URL or stream wrapper');
})->with(['php://temp', 'php://input', 'phar:///tmp/x.phar/payload', 'data://text/plain;base64,QQ==']);

it('空（0 バイト）のファイルを拒否する', function () {
    $path = tempUploadFile('');

    $caught = null;
    try {
        new UploadRoomFileRequest(path: $path);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('ちょうど 5 MiB のファイルを受け入れる（上限は以下）', function () {
    $path = tempUploadFile(truncateTo: 5_242_880);

    $request = new UploadRoomFileRequest(path: $path);

    expect($request->filename())->toBe(basename($path));
});

it('5 MiB を超えるファイルを拒否する', function () {
    $path = tempUploadFile(truncateTo: 5_242_880 + 1);

    $caught = null;
    try {
        new UploadRoomFileRequest(path: $path);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('空の message 文字列を拒否する', function () {
    $path = tempUploadFile();

    $caught = null;
    try {
        new UploadRoomFileRequest(path: $path, message: '');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('65535 文字を超える message を拒否する', function () {
    $path = tempUploadFile();

    $caught = null;
    try {
        new UploadRoomFileRequest(path: $path, message: str_repeat('a', 65536));
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('filename をデフォルトでパスの basename にする', function () {
    $path = tempUploadFile('data');

    $request = new UploadRoomFileRequest(path: $path);

    expect($request->filename())->toBe(basename($path));
});

it('指定された場合に明示的な filename 上書き値を使用する', function () {
    $path = tempUploadFile('data');

    $request = new UploadRoomFileRequest(path: $path, filename: 'monthly.pdf');

    expect($request->filename())->toBe('monthly.pdf');
});

it('contents() を通じてファイル内容を遅延読み込みする', function () {
    $path = tempUploadFile('binary-bytes');

    $request = new UploadRoomFileRequest(path: $path);

    expect($request->contents())->toBe('binary-bytes');
});

it('指定された場合のみ message を toFields に含める', function () {
    $path = tempUploadFile();

    expect((new UploadRoomFileRequest(path: $path))->toFields())->toBe([]);
    expect((new UploadRoomFileRequest(path: $path, message: 'hi'))->toFields())
        ->toBe(['message' => 'hi']);
});
