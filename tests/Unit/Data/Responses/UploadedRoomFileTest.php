<?php

declare(strict_types=1);

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
