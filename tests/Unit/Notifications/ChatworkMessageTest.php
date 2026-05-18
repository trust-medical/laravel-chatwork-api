<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkChannel;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage;

it('コンストラクタからボディを構築する', function () {
    $message = new ChatworkMessage('Hello');

    expect($message->toPayload())->toBe(['body' => 'Hello']);
});

it('make()->body() でボディを構築する', function () {
    $message = ChatworkMessage::make()->body('Hello');

    expect($message->toPayload())->toBe(['body' => 'Hello']);
});

it('複数の body() 呼び出しを改行区切りで連結する', function () {
    $message = ChatworkMessage::make()->body('A')->body('B');

    expect($message->toPayload()['body'])->toBe("A\nB");
});

it('to(int) で [To:account_id] をレンダリングする', function () {
    $message = ChatworkMessage::make()->to(123)->body('Hi');

    expect($message->toPayload()['body'])->toBe("[To:123]\nHi");
});

it('info(title, body) で [info][title]...[/title]...[/info] をレンダリングする', function () {
    $message = ChatworkMessage::make()->info('タイトル', '内容');

    expect($message->toPayload()['body'])->toBe('[info][title]タイトル[/title]内容[/info]');
});

it('[title]...[/title] をレンダリングする', function () {
    $message = ChatworkMessage::make()->title('見出し');

    expect($message->toPayload()['body'])->toBe('[title]見出し[/title]');
});

it('[code]...[/code] をレンダリングする', function () {
    $message = ChatworkMessage::make()->code('echo 1');

    expect($message->toPayload()['body'])->toBe('[code]echo 1[/code]');
});

it('hr() で [hr] をレンダリングする', function () {
    $message = ChatworkMessage::make()->hr();

    expect($message->toPayload()['body'])->toBe('[hr]');
});

it('plain() でブラケットをエスケープする', function () {
    $message = ChatworkMessage::make()->plain('[info]raw[/info]');

    expect($message->toPayload()['body'])->toBe('［info］raw［/info］');
});

it('escape() (plain のエイリアス) でブラケットをエスケープする', function () {
    $message = ChatworkMessage::make()->escape('[title]X[/title]');

    expect($message->toPayload()['body'])->toBe('［title］X［/title］');
});

it('selfUnread() をペイロードへ切り替える', function () {
    $on = ChatworkMessage::make()->body('Hi')->selfUnread();
    $off = ChatworkMessage::make()->body('Hi')->selfUnread(false);

    expect($on->toPayload())->toBe(['body' => 'Hi', 'self_unread' => 1])
        ->and($off->toPayload())->toBe(['body' => 'Hi', 'self_unread' => 0]);
});

it('toRoom() の送信先を保持する', function () {
    $message = ChatworkMessage::make()->toRoom(789);

    expect($message->targetRoomId())->toBe(789);
});

it('via() が [ChatworkChannel::class] を返す', function () {
    $notifiable = new stdClass();
    $message = new ChatworkMessage('Hi');

    expect($message->via($notifiable))->toBe([ChatworkChannel::class]);
});

it('toChatwork() が self を返す', function () {
    $notifiable = new stdClass();
    $message = new ChatworkMessage('Hi');

    expect($message->toChatwork($notifiable))->toBe($message);
});

it('toPayload() 時に空ボディを拒否する', function () {
    ChatworkMessage::make()->toPayload();
})->throws(ChatworkValidationException::class);

it('toPayload() 時に 65535 文字を超えるボディを拒否する', function () {
    ChatworkMessage::make()->body(str_repeat('a', 65536))->toPayload();
})->throws(ChatworkValidationException::class);

it('宣言順に複合ボディを構築する', function () {
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
