<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage;

it('コンストラクタからボディを構築する', function () {
    $message = new ChatworkMessage('Hello');

    expect($message->toPayload())->toBe(['body' => 'Hello']);
});

it('本文未設定のまま toPayload() を呼ぶと ChatworkValidationException', function () {
    ChatworkMessage::make()->toPayload();
})->throws(ChatworkValidationException::class);

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

it('info() のタイトルを無害化する', function () {
    $message = ChatworkMessage::make()->info('[info]タイトル[/info]', '内容');

    expect($message->toPayload()['body'])->toBe('[info][title]［info］タイトル［/info］[/title]内容[/info]');
});

it('info() の本文を無害化してタグインジェクションを防ぐ', function () {
    $message = ChatworkMessage::make()->info('タイトル', '[/info][To:999]乗っ取り');

    expect($message->toPayload()['body'])->toBe('[info][title]タイトル[/title]［/info］［To:999］乗っ取り[/info]');
});

it('info() の本文に行配列を渡すと改行で連結する', function () {
    $message = ChatworkMessage::make()->info('タイトル', ['1行目', '2行目']);

    expect($message->toPayload()['body'])->toBe("[info][title]タイトル[/title]1行目\n2行目[/info]");
});

it('info() の行配列は各行を無害化する', function () {
    $message = ChatworkMessage::make()->info('タイトル', ['[hr]', '[code]x[/code]']);

    expect($message->toPayload()['body'])->toBe("[info][title]タイトル[/title]［hr］\n［code］x［/code］[/info]");
});

it('info() の行配列の空文字要素を空行として残す', function () {
    $message = ChatworkMessage::make()->info('タイトル', ['1行目', '', '3行目']);

    expect($message->toPayload()['body'])->toBe("[info][title]タイトル[/title]1行目\n\n3行目[/info]");
});

it('info() は空タイトル・空本文でも枠を生成し toPayload() を通す', function () {
    $message = ChatworkMessage::make()->info('', '');

    expect($message->toPayload())->toBe(['body' => '[info][title][/title][/info]']);
});

it('info() は空配列の本文でも枠を生成する', function () {
    $message = ChatworkMessage::make()->info('タイトル', []);

    expect($message->toPayload()['body'])->toBe('[info][title]タイトル[/title][/info]');
});

it('body() は Chatwork 記法を生のまま送信する (無害化のオプトアウト経路)', function () {
    $message = ChatworkMessage::make()->body('[info]生記法[/info]');

    expect($message->toPayload()['body'])->toBe('[info]生記法[/info]');
});

// method_exists() は静的解析で常に true/false へ畳まれ phpstan の impossibleType に触れるため、
// 実行時解決の Reflection で存在を検査する。
it('title() ビルダーメソッドを提供しない (単独の [title] は装飾されない)', function () {
    expect((new ReflectionClass(ChatworkMessage::class))->hasMethod('title'))->toBeFalse();
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

it('toPayload() 時に空ボディを拒否する', function () {
    ChatworkMessage::make()->toPayload();
})->throws(ChatworkValidationException::class);

it('toPayload() 時に 65535 文字を超えるボディを拒否する', function () {
    ChatworkMessage::make()->body(str_repeat('a', 65536))->toPayload();
})->throws(ChatworkValidationException::class);

it('クラスが final で宣言されている (toPayload 契約を固定)', function () {
    expect((new ReflectionClass(ChatworkMessage::class))->isFinal())->toBeTrue();
});

it('宣言順に複合ボディを構築する', function () {
    $message = ChatworkMessage::make()
        ->to(123)
        ->body('本文')
        ->info('タイトル', '内容')
        ->code('ログ')
        ->hr();

    expect($message->toPayload()['body'])->toBe(
        "[To:123]\n本文\n[info][title]タイトル[/title]内容[/info]\n[code]ログ[/code]\n[hr]"
    );
});
