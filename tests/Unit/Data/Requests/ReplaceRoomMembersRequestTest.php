<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\ReplaceRoomMembersRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('空の membersAdminIds を拒否する', function () {
    $caught = null;
    try {
        new ReplaceRoomMembersRequest(membersAdminIds: []);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('membersAdminIds に 0 以下のアカウント ID が含まれる場合に拒否する', function () {
    $caught = null;
    try {
        new ReplaceRoomMembersRequest(membersAdminIds: [1, 0, 3]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('オプションリストに 0 以下の ID が含まれる場合に拒否する', function () {
    $caught = null;
    try {
        new ReplaceRoomMembersRequest(
            membersAdminIds: [1],
            membersMemberIds: [-2],
        );
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('membersAdminIds を CSV 文字列にシリアライズする', function () {
    $request = new ReplaceRoomMembersRequest(membersAdminIds: [1, 2, 3]);

    expect($request->toArray()['members_admin_ids'])->toBe('1,2,3');
});

it('null のオプション ID リストを省略する', function () {
    $request = new ReplaceRoomMembersRequest(membersAdminIds: [1]);

    $payload = $request->toArray();

    expect($payload)->toHaveKey('members_admin_ids');
    expect($payload)->not->toHaveKey('members_member_ids');
    expect($payload)->not->toHaveKey('members_readonly_ids');
});

it('指定されたオプション ID リストを CSV にシリアライズする', function () {
    $request = new ReplaceRoomMembersRequest(
        membersAdminIds: [1, 2],
        membersMemberIds: [3, 4],
        membersReadonlyIds: [5],
    );

    $payload = $request->toArray();

    expect($payload['members_member_ids'])->toBe('3,4');
    expect($payload['members_readonly_ids'])->toBe('5');
});

it('空のオプションリストを受け入れ、空の CSV としてシリアライズする', function () {
    $request = new ReplaceRoomMembersRequest(
        membersAdminIds: [1],
        membersMemberIds: [],
    );

    $payload = $request->toArray();

    expect($payload['members_member_ids'])->toBe('');
    expect($payload)->not->toHaveKey('members_readonly_ids');
});
