<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Requests\ReplaceRoomMembersRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\ReplacedRoomMembers;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('フォームエンコードボディで PUT /rooms/{room_id}/members を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/replace-room-members-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->members()->replaceMembers(123, new ReplaceRoomMembersRequest(
        membersAdminIds: [1, 2],
    ));

    Http::assertSent(function (Request $r) {
        $ct = $r->header('Content-Type')[0] ?? '';

        return $r->method() === 'PUT'
            && $r->url() === 'https://api.chatwork.com/v2/rooms/123/members'
            && str_contains($ct, 'application/x-www-form-urlencoded')
            && $r['members_admin_ids'] === '1,2';
    });
});

it('省略可能な ID リストが指定されない場合は送信しない', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/replace-room-members-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->members()->replaceMembers(123, new ReplaceRoomMembersRequest(
        membersAdminIds: [1],
    ));

    Http::assertSent(fn (Request $r) => ! isset($r->data()['members_member_ids'])
        && ! isset($r->data()['members_readonly_ids']));
});

it('メンバー ID と読み取り専用 ID リストが指定された場合に CSV でシリアライズする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/replace-room-members-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->members()->replaceMembers(123, new ReplaceRoomMembersRequest(
        membersAdminIds: [1, 2],
        membersMemberIds: [3, 4],
        membersReadonlyIds: [5],
    ));

    Http::assertSent(fn (Request $r) => $r['members_admin_ids'] === '1,2'
        && $r['members_member_ids'] === '3,4'
        && $r['members_readonly_ids'] === '5');
});

it('api_token 接続で x-chatworktoken ヘッダーを送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/replace-room-members-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->members()->replaceMembers(123, new ReplaceRoomMembersRequest(
        membersAdminIds: [1],
    ));

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('asDto モードで ReplacedRoomMembers DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/replace-room-members-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->members()->replaceMembers(123, new ReplaceRoomMembersRequest(
        membersAdminIds: [123, 456],
        membersMemberIds: [789],
        membersReadonlyIds: [1011],
    ));

    expect($result)->toBeInstanceOf(ReplacedRoomMembers::class)
        ->and($result->admin)->toBe([123, 456])
        ->and($result->member)->toBe([789])
        ->and($result->readonly)->toBe([1011]);
});

it('membersAdminIds が空の場合は HTTP を送信せず ChatworkValidationException をスローする', function () {
    Http::fake();

    $caught = null;
    try {
        Chatwork::rooms()->members()->replaceMembers(123, new ReplaceRoomMembersRequest(
            membersAdminIds: [],
        ));
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
    Http::assertNothingSent();
});

it('正でないアカウント ID に対して ChatworkValidationException をスローする', function () {
    $caught = null;
    try {
        new ReplaceRoomMembersRequest(membersAdminIds: [1, 0, 3]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('400 時に errors() を持つ ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/replace-room-members-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->members()->replaceMembers(123, new ReplaceRoomMembersRequest(
            membersAdminIds: [1],
        ));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['members_admin_ids is required']);
});

it('429 時に rateLimit() を公開する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/replace-room-members-429.json'),
            429,
            [
                'x-ratelimit-limit' => '200',
                'x-ratelimit-remaining' => '0',
                'x-ratelimit-reset' => '1735718400',
            ],
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->members()->replaceMembers(123, new ReplaceRoomMembersRequest(
            membersAdminIds: [1],
        ));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(429)
        ->and($caught?->rateLimit())->toBe([
            'limit' => 200,
            'remaining' => 0,
            'reset' => 1735718400,
        ]);
});
