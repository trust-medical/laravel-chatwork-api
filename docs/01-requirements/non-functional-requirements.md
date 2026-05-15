# 非機能要件

## 互換性

- PHP `^8.3`
- Laravel `^11.0 || ^12.0 || ^13.0`
- Laravel package auto-discovery に対応する。

## 安全性

- API token、client secret、refresh token をログに出さない。
- request exception、result object、debug情報に秘密情報を含めない。
- callback routeを提供する場合、state検証を必須にする。
- 破壊的APIは `delete()` のような曖昧な短名を避ける。

## HTTP挙動

- retry / rate limit制御は行わない。
- 429や5xxは戻り値モードに応じて、例外または結果として扱う。
- timeoutはconfigで変更可能にする。
- base URIはconfigで変更可能にするが、デフォルトは `https://api.chatwork.com/v2` とする。

## テスト容易性

- Laravel HTTP Client を利用し、`Http::fake()` で全HTTP通信を検証できる設計にする。
- OAuth2 token exchange / refresh も `Http::fake()` で検証する。
- 実API通信に依存するテストは作らない。

## 型安全性

- レスポンスDTOは `readonly class` を基本にする。
- request objectも可能な限りimmutableにする。
- enum化できる値はPHP enumを使う。

## ドキュメント

- 設計書は日本語で管理する。
- Chatwork公式仕様から補完した内容は、参照元URLを明記する。
- ローカルOpenAPI JSONとの差分を追跡できるようにする。

