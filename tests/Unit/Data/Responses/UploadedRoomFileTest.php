<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleAccount;
use TrustMedical\LaravelChatworkApi\Data\Responses\UploadedRoomFile;

it('readonly クラスである', function () {
    expect(UploadedRoomFile::class)->toBeReadonly();
});

it('fromArray でファイル id をハイドレートする', function () {
    $data = fixtureJson('files/upload-room-file-200.json');

    $uploaded = UploadedRoomFile::fromArray($data);

    expect($uploaded->fileId)->toBe(12345);
});

it('数値の file_id を int にキャストする', function () {
    $uploaded = UploadedRoomFile::fromArray(['file_id' => '7']);

    expect($uploaded->fileId)->toBe(7);
});

it('file_id が存在しない場合は 0 をデフォルトにする', function () {
    $uploaded = UploadedRoomFile::fromArray([]);

    expect($uploaded->fileId)->toBe(0);
});

it('fromArray で全フィールドを SimpleAccount ネストでハイドレートする', function () {
    $data = fixtureJson('files/upload-room-file-200.json');

    $uploaded = UploadedRoomFile::fromArray($data);

    expect($uploaded->fileId)->toBe(12345)
        ->and($uploaded->messageId)->toBe('1482558473966190592')
        ->and($uploaded->filename)->toBe('CompanyLogo.png')
        ->and($uploaded->filesize)->toBe(32829)
        ->and($uploaded->uploadTime)->toBe(1629943082)
        ->and($uploaded->account)->toBeInstanceOf(SimpleAccount::class)
        ->and($uploaded->account->accountId)->toBe(6196123)
        ->and($uploaded->account->name)->toBe('User Name')
        ->and($uploaded->account->avatarImageUrl)
        ->toBe('https://appdata.chatwork.com/avatar/ico_default_green.png');
});

it('account フィールドが存在しない場合に null になる', function () {
    $uploaded = UploadedRoomFile::fromArray(['file_id' => 123]);

    expect($uploaded->account)->toBeNull()
        ->and($uploaded->messageId)->toBe('')
        ->and($uploaded->filename)->toBe('')
        ->and($uploaded->filesize)->toBe(0)
        ->and($uploaded->uploadTime)->toBe(0);
});

it('account フィールドが配列でない場合に null になる', function () {
    $uploaded = UploadedRoomFile::fromArray([
        'file_id' => 1,
        'account' => 'invalid',
    ]);

    expect($uploaded->account)->toBeNull();
});

it('message_id が int で来た場合に string に正規化される', function () {
    $uploaded = UploadedRoomFile::fromArray([
        'file_id' => 1,
        'message_id' => 1482558473966190592,
    ]);

    expect($uploaded->messageId)->toBe('1482558473966190592');
});

it('数値の filesize / upload_time を int にキャストする', function () {
    $uploaded = UploadedRoomFile::fromArray([
        'file_id' => 1,
        'filesize' => '1024',
        'upload_time' => '1629943082',
    ]);

    expect($uploaded->filesize)->toBe(1024)
        ->and($uploaded->uploadTime)->toBe(1629943082);
});

it('positional 1 引数 (fileId のみ) で構築できる (後方互換)', function () {
    $uploaded = new UploadedRoomFile(123);

    expect($uploaded->fileId)->toBe(123)
        ->and($uploaded->messageId)->toBe('')
        ->and($uploaded->filename)->toBe('')
        ->and($uploaded->filesize)->toBe(0)
        ->and($uploaded->uploadTime)->toBe(0)
        ->and($uploaded->account)->toBeNull();
});
