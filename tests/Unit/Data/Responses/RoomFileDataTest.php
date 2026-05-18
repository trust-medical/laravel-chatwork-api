<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\RoomFileData;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleAccount;

it('readonly クラスである', function () {
    expect(RoomFileData::class)->toBeReadonly();
});

it('fromArray で RoomFileData をネストした SimpleAccount とダウンロード URL 付きでハイドレートする', function () {
    $data = fixtureJson('files/get-room-file-200.json');

    $file = RoomFileData::fromArray($data);

    expect($file->fileId)->toBe(99);
    expect($file->account)->toBeInstanceOf(SimpleAccount::class);
    expect($file->account->accountId)->toBe(123);
    expect($file->messageId)->toBe('22');
    expect($file->filename)->toBe('report.pdf');
    expect($file->filesize)->toBe(2048);
    expect($file->uploadTime)->toBe(1735707600);
    expect($file->downloadUrl)->toBe('https://download.chatwork.com/files/99?token=abc');
});

it('download_url が存在しない場合に downloadUrl を null で返す', function () {
    $file = RoomFileData::fromArray([
        'file_id' => 5,
        'account' => ['account_id' => 1, 'name' => 'X', 'avatar_image_url' => ''],
        'message_id' => '7',
        'filename' => 'a.txt',
        'filesize' => 10,
        'upload_time' => 1,
    ]);

    expect($file->downloadUrl)->toBeNull();
});

it('数値の file_id と filesize を int にキャストする', function () {
    $file = RoomFileData::fromArray([
        'file_id' => '42',
        'filesize' => '1024',
    ]);

    expect($file->fileId)->toBe(42);
    expect($file->filesize)->toBe(1024);
});
