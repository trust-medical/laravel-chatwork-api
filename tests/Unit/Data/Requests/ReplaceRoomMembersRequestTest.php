<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\ReplaceRoomMembersRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('rejects empty membersAdminIds', function () {
    $caught = null;
    try {
        new ReplaceRoomMembersRequest(membersAdminIds: []);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects zero or negative account ids in membersAdminIds', function () {
    $caught = null;
    try {
        new ReplaceRoomMembersRequest(membersAdminIds: [1, 0, 3]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects non-positive ids in optional lists', function () {
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

it('serializes membersAdminIds as a CSV string', function () {
    $request = new ReplaceRoomMembersRequest(membersAdminIds: [1, 2, 3]);

    expect($request->toArray()['members_admin_ids'])->toBe('1,2,3');
});

it('omits optional id lists when null', function () {
    $request = new ReplaceRoomMembersRequest(membersAdminIds: [1]);

    $payload = $request->toArray();

    expect($payload)->toHaveKey('members_admin_ids');
    expect($payload)->not->toHaveKey('members_member_ids');
    expect($payload)->not->toHaveKey('members_readonly_ids');
});

it('serializes optional id lists as CSV when provided', function () {
    $request = new ReplaceRoomMembersRequest(
        membersAdminIds: [1, 2],
        membersMemberIds: [3, 4],
        membersReadonlyIds: [5],
    );

    $payload = $request->toArray();

    expect($payload['members_member_ids'])->toBe('3,4');
    expect($payload['members_readonly_ids'])->toBe('5');
});

it('accepts empty optional lists and serializes them as empty CSV', function () {
    $request = new ReplaceRoomMembersRequest(
        membersAdminIds: [1],
        membersMemberIds: [],
    );

    $payload = $request->toArray();

    expect($payload['members_member_ids'])->toBe('');
    expect($payload)->not->toHaveKey('members_readonly_ids');
});
