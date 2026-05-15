# Chatwork Message Builder設計

## 目的

`ChatworkMessage` は、Chatworkに送信する本文と送信オプションを表現する。
本文文字列の組み立てと、送信先room、`self_unread` を扱う。

## 基本API

```php
ChatworkMessage::make()
    ->body('本文')
    ->toRoom($roomId)
    ->selfUnread();
```

簡易形:

```php
new ChatworkMessage('本文');
```

## 送信payload

`POST /rooms/{room_id}/messages` には次を送る。

```php
[
    'body' => '本文',
    'self_unread' => 1,
]
```

`self_unread()` が未指定の場合、APIデフォルトに合わせて `0` として扱う。

## Chatwork記法

初期対応:

```php
ChatworkMessage::make()
    ->to(123)
    ->body('本文')
    ->info('タイトル', '内容')
    ->title('見出し')
    ->code('ログ')
    ->hr();
```

想定出力:

```text
[To:123]
本文
[info][title]タイトル[/title]内容[/info]
[title]見出し[/title]
[code]ログ[/code]
hr相当の罫線
```

罫線の具体表現は実装時にChatwork表示を考慮して決める。

## エスケープ

デフォルトでは本文をそのまま送信する。
これはChatwork記法をアプリケーション側で直接書けるようにするため。

```php
ChatworkMessage::make()->body('[info]本文[/info]');
```

`plain()` または `escape()` を明示した場合だけ、Chatwork記法を無効化する。

```php
ChatworkMessage::make()
    ->plain('[info]本文[/info]');
```

## バリデーション

message bodyは1から65535文字。
最終的に送信する本文に対して検証する。

検証タイミング:

- request object生成時
- Notification channel送信直前

## 今後対応

以下は初期実装から外す。

- `[rp]` 返信
- 引用
- 絵文字
- 追加の装飾記法

