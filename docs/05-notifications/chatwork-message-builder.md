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
    ->code('ログ')
    ->hr();
```

想定出力:

```text
[To:123]
本文
[info][title]タイトル[/title]内容[/info]
[code]ログ[/code]
hr相当の罫線
```

罫線の具体表現は実装時にChatwork表示を考慮して決める。

単独の `[title]...[/title]` はChatwork側で装飾されないため、ビルダーからは提供しない。
見出しは `info($title, $body)` が `[info]` の内側の `[title]` として描画する。

## エスケープ

テキストを受け取るビルダーはエスケープを既定とし、生のChatwork記法は明示的にオプトインする
（Blade の `{{ }}` と `{!! !!}` の関係）。

| 呼出 | 本文の扱い |
| --- | --- |
| `info($title, $body)` | タイトル・本文とも角括弧を全角へ無害化する |
| `plain($text)` / `escape($text)` | 角括弧を全角へ無害化する |
| `body($text)` | そのまま送信する（記法が有効） |

`info()` は利用側が内容を統制できない値（API レスポンス、例外メッセージ）を
そのまま渡せる。本文中の `[/info]` で囲み枠が途中で閉じたり、`[To:]` が
注入されたりしない。

```php
ChatworkMessage::make()
    ->info('同期失敗', '[/info][To:999] 応答: [code]500[/code]');
// → [info][title]同期失敗[/title]［/info］［To:999］ 応答: ［code］500［/code］[/info]
```

本文には行配列も渡せる。`"\n"` で連結してから無害化するため、
利用側で `implode()` する必要はない（空文字要素は空行として残る）。

```php
ChatworkMessage::make()
    ->info('デプロイ完了', ['環境: production', '', 'コミット: abc123']);
```

Chatwork記法を意図して描画したい場合だけ `body()` を使う。

```php
ChatworkMessage::make()->body('[info]本文[/info]');
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

