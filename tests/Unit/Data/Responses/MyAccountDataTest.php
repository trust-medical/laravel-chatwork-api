<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\MyAccountData;

it('readonly クラスである', function () {
    expect(MyAccountData::class)->toBeReadonly();
});

it('fromArray で MyAccountData をハイドレートする', function () {
    $me = MyAccountData::fromArray(fixtureJson('me/get-me-200.json'));

    expect($me->accountId)->toBe(123);
    expect($me->roomId)->toBe(322);
    expect($me->name)->toBe('John Doe');
    expect($me->chatworkId)->toBe('tarochatworkid');
    expect($me->organizationId)->toBe(101);
    expect($me->organizationName)->toBe('Hello Company');
    expect($me->department)->toBe('Marketing');
    expect($me->title)->toBe('CMO');
    expect($me->url)->toBe('https://example.com');
    expect($me->introduction)->toBe('Self introduction text');
    expect($me->mail)->toBe('taro@example.com');
    expect($me->telOrganization)->toBe('XXX-XXXX-XXXX');
    expect($me->telExtension)->toBe('YYY-YYYY-YYYY');
    expect($me->telMobile)->toBe('ZZZ-ZZZZ-ZZZZ');
    expect($me->skype)->toBe('myskype_id');
    expect($me->facebook)->toBe('myfacebook_id');
    expect($me->twitter)->toBe('mytwitter_id');
    expect($me->avatarImageUrl)->toBe('https://example.com/avatar/123.png');
    expect($me->loginMail)->toBe('login@example.com');
});

it('数値文字列の id を int にキャストする', function () {
    $me = MyAccountData::fromArray([
        'account_id' => '42',
        'room_id' => '7',
        'organization_id' => '9',
    ]);

    expect($me->accountId)->toBe(42);
    expect($me->roomId)->toBe(7);
    expect($me->organizationId)->toBe(9);
});

it('login_mail が欠けていても例外を投げずに空文字をデフォルトにする', function () {
    $me = MyAccountData::fromArray([
        'account_id' => 1,
        'name' => 'No Login Mail',
    ]);

    expect($me->loginMail)->toBe('');
    expect($me->name)->toBe('No Login Mail');
    expect($me->title)->toBe('');
    expect($me->avatarImageUrl)->toBe('');
});
