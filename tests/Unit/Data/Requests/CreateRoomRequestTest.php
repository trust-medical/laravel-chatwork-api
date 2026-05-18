<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\CreateRoomRequest;
use TrustMedical\LaravelChatworkApi\Enums\IconPreset;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('空の name を拒否する', function () {
    $caught = null;
    try {
        new CreateRoomRequest(name: '', membersAdminIds: [1]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('255 文字を超える name を拒否する', function () {
    $caught = null;
    try {
        new CreateRoomRequest(name: str_repeat('a', 256), membersAdminIds: [1]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('membersAdminIds に 0 以下のアカウント ID が含まれる場合に拒否する', function () {
    $caught = null;
    try {
        new CreateRoomRequest(name: 'Team', membersAdminIds: [1, 0, 3]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('空の membersAdminIds を拒否する', function () {
    $caught = null;
    try {
        new CreateRoomRequest(name: 'Team', membersAdminIds: []);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('membersAdminIds を CSV 文字列にシリアライズする', function () {
    $request = new CreateRoomRequest(name: 'Team', membersAdminIds: [1, 2, 3]);

    expect($request->toArray()['members_admin_ids'])->toBe('1,2,3');
});

it('IconPreset enum をその文字列値にシリアライズする', function () {
    $request = new CreateRoomRequest(
        name: 'Team',
        membersAdminIds: [1],
        iconPreset: IconPreset::Business,
    );

    expect($request->toArray()['icon_preset'])->toBe('business');
});

it('link と link_need_acceptance の bool 値を 0/1 に変換する', function () {
    $request = new CreateRoomRequest(
        name: 'Team',
        membersAdminIds: [1],
        link: true,
        linkNeedAcceptance: false,
    );

    $payload = $request->toArray();

    expect($payload['link'])->toBe(1);
    expect($payload['link_need_acceptance'])->toBe(0);
});

it('null のオプションフィールドを省略する', function () {
    $request = new CreateRoomRequest(name: 'Team', membersAdminIds: [1]);

    $payload = $request->toArray();

    expect($payload)->toHaveKeys(['name', 'members_admin_ids']);
    expect($payload)->not->toHaveKey('description');
    expect($payload)->not->toHaveKey('link');
    expect($payload)->not->toHaveKey('icon_preset');
    expect($payload)->not->toHaveKey('members_member_ids');
    expect($payload)->not->toHaveKey('members_readonly_ids');
});

it('members_member_ids と members_readonly_ids を CSV にシリアライズする', function () {
    $request = new CreateRoomRequest(
        name: 'Team',
        membersAdminIds: [1, 2],
        membersMemberIds: [3, 4],
        membersReadonlyIds: [5],
    );

    $payload = $request->toArray();

    expect($payload['members_member_ids'])->toBe('3,4');
    expect($payload['members_readonly_ids'])->toBe('5');
});
