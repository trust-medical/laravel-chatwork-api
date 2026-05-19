# プロジェクト目的

## ゴール

`trust-medical/laravel-chatwork-api` は、Chatwork API v2をLaravelアプリケーションから安全かつ自然に利用するためのパッケージである。
自社Laravelアプリ専用、社内複数プロジェクト共通、OSS公開、Packagist公開のすべてを想定する。

## 提供する利用体験

Facade経由、DI経由、Laravel Notification経由のすべてを正式な利用方法として扱う。

```php
Chatwork::rooms()->messages()->create($roomId, '本文');
```

```php
app(ChatworkClient::class)->rooms()->messages()->create($roomId, '本文');
```

```php
$user->notify(new ChatworkNotification('本文'));
```

## 開発方針

- 実装前に設計書を整備する。
- 実装はTDDで進める。
- 最初にNotification送信で必要な `POST /rooms/{room_id}/messages` を完成させる。
- その後、Chatwork API v2の全エンドポイントを段階的に実装する。
- 実APIを叩くテストは行わず、`Http::fake()` によるテストに限定する。

## 対象バージョン

- PHP: `^8.3`
- Laravel: `^11.0 || ^12.0 || ^13.0`
- Chatwork API: v2

## 設計上の優先順位

1. Laravelアプリで自然に使えること。
2. 複数ワークスペース、動的トークン、OAuth2更新に耐えること。
3. Chatwork公式API仕様との差分を追跡できること。
4. 戻り値と例外の挙動が利用側から明示できること。
5. 破壊的APIは誤用しにくい名前にすること。

## 既存OSSとの差別化

Chatwork関連のLaravel/PHPパッケージは既存にも存在する。本パッケージは以下を独自の価値とする。

| 観点 | 本パッケージ | 既存OSSの典型 |
| --- | --- | --- |
| API v2カバレッジ | 全エンドポイント（補完済みOpenAPI駆動） | 一部メッセージ系のみ |
| 戻り値モード | 6モード切替（array / DTO / Collection / Laravel Response / PSR-7 / Result） | array固定または独自Response |
| 認証 | API Token + OAuth2 Bearer（認可URL、callback、refresh） | API Tokenのみが多い |
| 複数ワークスペース | config connection + 動的Connection + `TokenProvider` | 単一token前提 |
| Notification | 公式サポート（Channel / Message / Route / On-Demand） | サードパーティchannelに依存 |
| エラーモデル | `ChatworkValidationException` / `ChatworkRequestException` を分離、秘密情報を除外 | 汎用HTTP例外をそのまま伝播 |
| 命名規則 | 破壊的操作を明示名（`leaveRoom` / `deleteRoom` / `replaceMembers` / `decline`） | `delete()` 等の曖昧名 |
| 仕様トレーサビリティ | 補完済みOpenAPI + 正規化YAMLで公式仕様との差分を追跡 | 仕様ソース非公開 |
| 対応Laravel | 11.x / 12.x / 13.x | 古いバージョンに固定されがち |

「Chatwork API v2を、Laravelの作法のまま、安全に、全方位で扱える」点を独自ポジションとする。

