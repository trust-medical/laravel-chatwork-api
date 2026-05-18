<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\IncomingRequestData;

it('is a readonly class', function () {
    expect(IncomingRequestData::class)->toBeReadonly();
});

it('hydrates IncomingRequestData via fromArray', function () {
    $data = fixtureJson('incoming-requests/list-incoming-requests-200.json');

    $request = IncomingRequestData::fromArray($data[0]);

    expect($request->requestId)->toBe(123);
    expect($request->accountId)->toBe(363);
    expect($request->message)->toBe('Please add me to your contacts.');
    expect($request->name)->toBe('Bob Bob');
    expect($request->chatworkId)->toBe('bobbob');
    expect($request->organizationId)->toBe(101);
    expect($request->organizationName)->toBe('Example Corp');
    expect($request->department)->toBe('Sales');
    expect($request->avatarImageUrl)->toBe('https://example.com/avatar/363.png');
});

it('casts numeric id strings to int', function () {
    $request = IncomingRequestData::fromArray([
        'request_id' => '42',
        'account_id' => '99',
        'organization_id' => '7',
    ]);

    expect($request->requestId)->toBe(42);
    expect($request->accountId)->toBe(99);
    expect($request->organizationId)->toBe(7);
});

it('falls back to defaults for missing fields without throwing', function () {
    $request = IncomingRequestData::fromArray([]);

    expect($request->requestId)->toBe(0);
    expect($request->accountId)->toBe(0);
    expect($request->message)->toBe('');
    expect($request->name)->toBe('');
    expect($request->chatworkId)->toBe('');
    expect($request->organizationId)->toBe(0);
    expect($request->organizationName)->toBe('');
    expect($request->department)->toBe('');
    expect($request->avatarImageUrl)->toBe('');
});
