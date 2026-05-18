<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\ContactData;

it('is a readonly class', function () {
    expect(ContactData::class)->toBeReadonly();
});

it('hydrates ContactData via fromArray', function () {
    $data = fixtureJson('contacts/list-contacts-200.json');

    $contact = ContactData::fromArray($data[0]);

    expect($contact->accountId)->toBe(123);
    expect($contact->roomId)->toBe(322);
    expect($contact->name)->toBe('Alice Contact');
    expect($contact->chatworkId)->toBe('alice');
    expect($contact->organizationId)->toBe(101);
    expect($contact->organizationName)->toBe('Example Corp');
    expect($contact->department)->toBe('Engineering');
    expect($contact->avatarImageUrl)->toBe('https://example.com/avatar/123.png');
});

it('casts numeric id strings to int', function () {
    $contact = ContactData::fromArray([
        'account_id' => '42',
        'room_id' => '99',
        'organization_id' => '7',
    ]);

    expect($contact->accountId)->toBe(42);
    expect($contact->roomId)->toBe(99);
    expect($contact->organizationId)->toBe(7);
});

it('falls back to defaults for missing fields without throwing', function () {
    $contact = ContactData::fromArray([]);

    expect($contact->accountId)->toBe(0);
    expect($contact->roomId)->toBe(0);
    expect($contact->name)->toBe('');
    expect($contact->chatworkId)->toBe('');
    expect($contact->organizationId)->toBe(0);
    expect($contact->organizationName)->toBe('');
    expect($contact->department)->toBe('');
    expect($contact->avatarImageUrl)->toBe('');
});
