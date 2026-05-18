<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\UploadedRoomFile;

it('is a readonly class', function () {
    expect(UploadedRoomFile::class)->toBeReadonly();
});

it('hydrates the file id via fromArray', function () {
    $data = fixtureJson('files/upload-room-file-200.json');

    $uploaded = UploadedRoomFile::fromArray($data);

    expect($uploaded->fileId)->toBe(12345);
});

it('casts a numeric file_id to int', function () {
    $uploaded = UploadedRoomFile::fromArray(['file_id' => '7']);

    expect($uploaded->fileId)->toBe(7);
});

it('defaults to 0 when file_id is missing', function () {
    $uploaded = UploadedRoomFile::fromArray([]);

    expect($uploaded->fileId)->toBe(0);
});
