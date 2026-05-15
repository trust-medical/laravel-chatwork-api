# 設計書インデックス

このディレクトリは `trust-medical/laravel-chatwork-api` の設計判断を管理する。
実装はこの設計書を参照し、TDDで小さく進める。

## 構成

- `00-overview/`: 目的、スコープ、開発方針
- `01-requirements/`: 機能要件、非機能要件
- `02-openapi/`: Chatwork API仕様の扱い、エンドポイントカタログ、正規化仕様
- `03-package-architecture/`: Laravelパッケージ構造、DI、戻り値戦略
- `04-api-client/`: 認証、HTTP request / response、resource API
- `05-notifications/`: Laravel Notification channel、message builder、routing
- `06-testing/`: TDDロードマップ、`Http::fake()` 方針

## 仕様ソース

- 補完済みOpenAPI: `docs/02-openapi/chatwork-api-v2-complemented.openapi.json`
- Chatwork公式Reference: https://developer.chatwork.com/reference
- Chatworkエンドポイント説明: https://developer.chatwork.com/docs/endpoints
- Chatwork OAuth: https://developer.chatwork.com/docs/oauth
- Chatwork API Documentation PDF: https://download.chatwork.com/ChatWork_API_Documentation.pdf
- Chatwork APIリクエスト仕様変更: https://developer.chatwork.com/changelog/202501-notice
- Laravel Notifications 13.x: https://laravel.com/docs/13.x/notifications

## 現時点の重要判断

- 対応Laravelは `11.x / 12.x / 13.x`。
- 対応PHPは `8.3` 以上。
- API Token と OAuth2 Bearer Token の両方を扱う。
- OAuth2は認可URL生成、callback処理、refresh token更新までパッケージに含める。
- 複数ワークスペースは `Connection` と `TokenProvider` を軸に設計する。
- 戻り値は利用側が `array / DTO / Collection / Laravel Response / PSR-7 Response / Result` から選べる。
- retry / rate limit制御は行わず、API結果として返す。
- 初期TDD対象は `POST /rooms/{room_id}/messages`。
