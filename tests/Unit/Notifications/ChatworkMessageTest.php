<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkChannel;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage;

it('builds body from constructor', function () {
    $message = new ChatworkMessage('Hello');

    expect($message->toPayload())->toBe(['body' => 'Hello']);
});

it('builds body via make()->body()', function () {
    $message = ChatworkMessage::make()->body('Hello');

    expect($message->toPayload())->toBe(['body' => 'Hello']);
});

it('appends multiple body() calls with newline separators', function () {
    $message = ChatworkMessage::make()->body('A')->body('B');

    expect($message->toPayload()['body'])->toBe("A\nB");
});

it('renders [To:account_id] via to(int)', function () {
    $message = ChatworkMessage::make()->to(123)->body('Hi');

    expect($message->toPayload()['body'])->toBe("[To:123]\nHi");
});

it('renders [info][title]...[/title]...[/info] via info(title, body)', function () {
    $message = ChatworkMessage::make()->info('タイトル', '内容');

    expect($message->toPayload()['body'])->toBe('[info][title]タイトル[/title]内容[/info]');
});

it('renders [title]...[/title]', function () {
    $message = ChatworkMessage::make()->title('見出し');

    expect($message->toPayload()['body'])->toBe('[title]見出し[/title]');
});

it('renders [code]...[/code]', function () {
    $message = ChatworkMessage::make()->code('echo 1');

    expect($message->toPayload()['body'])->toBe('[code]echo 1[/code]');
});

it('renders [hr] for hr()', function () {
    $message = ChatworkMessage::make()->hr();

    expect($message->toPayload()['body'])->toBe('[hr]');
});

it('escapes brackets via plain()', function () {
    $message = ChatworkMessage::make()->plain('[info]raw[/info]');

    expect($message->toPayload()['body'])->toBe('［info］raw［/info］');
});

it('escapes brackets via escape() (alias of plain)', function () {
    $message = ChatworkMessage::make()->escape('[title]X[/title]');

    expect($message->toPayload()['body'])->toBe('［title］X［/title］');
});

it('toggles selfUnread() into the payload', function () {
    $on = ChatworkMessage::make()->body('Hi')->selfUnread();
    $off = ChatworkMessage::make()->body('Hi')->selfUnread(false);

    expect($on->toPayload())->toBe(['body' => 'Hi', 'self_unread' => 1])
        ->and($off->toPayload())->toBe(['body' => 'Hi', 'self_unread' => 0]);
});

it('captures the toRoom() target', function () {
    $message = ChatworkMessage::make()->toRoom(789);

    expect($message->targetRoomId())->toBe(789);
});

it('via() returns [ChatworkChannel::class]', function () {
    $notifiable = new stdClass();
    $message = new ChatworkMessage('Hi');

    expect($message->via($notifiable))->toBe([ChatworkChannel::class]);
});

it('toChatwork() returns self', function () {
    $notifiable = new stdClass();
    $message = new ChatworkMessage('Hi');

    expect($message->toChatwork($notifiable))->toBe($message);
});

it('rejects empty body at toPayload() time', function () {
    ChatworkMessage::make()->toPayload();
})->throws(ChatworkValidationException::class);

it('rejects body over 65535 chars at toPayload() time', function () {
    ChatworkMessage::make()->body(str_repeat('a', 65536))->toPayload();
})->throws(ChatworkValidationException::class);

it('builds a compound body in declaration order', function () {
    $message = ChatworkMessage::make()
        ->to(123)
        ->body('本文')
        ->info('タイトル', '内容')
        ->title('見出し')
        ->code('ログ')
        ->hr();

    expect($message->toPayload()['body'])->toBe(
        "[To:123]\n本文\n[info][title]タイトル[/title]内容[/info]\n[title]見出し[/title]\n[code]ログ[/code]\n[hr]"
    );
});
