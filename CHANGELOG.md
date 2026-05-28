# Changelog

All notable changes to `trust-medical/laravel-chatwork-api` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

> リリースタグを打つ際に、この `[Unreleased]` 見出しを `[1.0.0] - YYYY-MM-DD` へ
> rename し、新しい空の `[Unreleased]` を上に追加すること。

## [Unreleased]

## [1.2.0] - 2026-05-28

### Added

- **`Chatwork::forOAuthKey(string $key, ?string $base = null)`**: ランタイム指定キー
  （例: User ID）で `TokenRepository` を引いて OAuth コネクションをその場で組み立てる新 API。
  これまで OAuth サポートは `config('chatwork.connections.<name>.connection_name')` で
  固定された TokenRepository キーしか引けず、ユーザー単位 OAuth（per-tenant）を扱う
  consumer は TokenRepository と `OAuthClient::refresh()` を直接組み合わせる自前ロジック
  に頼り、結果として `OAuthTokenProvider::coalescedRefresh()` の `Cache::lock` 直列化を
  バイパスしてレース条件を抱えていた。新 API は既存の `OAuthTokenProvider` を経由する
  ため、並列ワーカーから呼んでも `Cache::lock('chatwork:oauth:refresh:<sha256($key)>', 30)`
  で refresh が直列化される。返される `Connection::$name` は `"oauth:{$key}"` 形式、
  `baseUri` / `timeout` は `$base`（省略時は `config('chatwork.default')`）の connection
  エントリから継承する。`ChatworkManager` の immutable clone パターンと既存の
  `OAuthTokenProvider` / `TokenRepository` / `Connection` シグネチャは無改変。

## [1.1.0] - 2026-05-28

### Added

- **`UploadedRoomFile` DTO の完全マッピング**: `POST /v2/rooms/{room_id}/files`
  のレスポンスから `message_id` / `filename` / `filesize` / `upload_time` /
  `account` (`SimpleAccount`) を読み取って公開プロパティに公開するようになった。
  これまで `file_id` 以外の情報を取得するには `asArray()` で raw レスポンスを取得する
  必要があったが、Dto モードでもファイル添付メッセージの Chatwork 直リンク
  (`#!rid<room_id>-<message_id>`) を組み立てられるようになる。新フィールドは
  すべてコンストラクタの末尾にデフォルト値付きで追加されているため、
  `new UploadedRoomFile(fileId: ...)` を直接呼ぶ既存テストや fixture との
  後方互換は保たれる。`account` は欠落耐性のため `?SimpleAccount` で公開する。

## [1.0.2] - 2026-05-25

### Fixed

- **`withBearerToken()` / `withApiToken()` の動的クレデンシャル**:
  `ChatworkManager::getEffectiveConnection()` が credentials override の存在を
  確認する前に base connection の static credentials を解決しに行っていたため、
  base connection が token 未設定（例: OAuth 専用プロジェクトで
  `CHATWORK_API_TOKEN` 未設定）の環境では override が使えず常に
  `ChatworkAuthenticationException` (`has no token configured`) になっていた。
  override 設定時は base credentials の解決をスキップし、base 接続からは
  name / base URI / timeout のメタデータのみを引き継ぐよう修正。OAuth callback
  内で `TokenRepository::save` 前に `Chatwork::withBearerToken($accessToken)->me()`
  でユーザー識別するフローなど、動的トークン経路が base API token 未設定環境で
  動作するようになる。公開 API シグネチャ（`withApiToken`, `withBearerToken`,
  `connection`, `getEffectiveConnection`）と既存の挙動（override 無し時は従来通り
  base credentials を要求、`connection('foo')` 明示時も従来通り credentials 必須）は不変。

## [1.0.1] - 2026-05-25

### Fixed

- **OAuth token endpoint authentication**: `OAuthClient::exchange()` /
  `refresh()` がクライアント認証情報を body に入れて送っていたため、Chatwork が
  Confidential Client に要求する HTTP Basic 認証
  (`Authorization: Basic Base64(client_id:client_secret)`) と整合せず常に 401 を
  返していた。`client_secret` 設定時は Basic 認証ヘッダで送るよう修正し、Public
  Client (`client_secret` 未設定/空文字) では従来どおり body に `client_id` のみを
  含める動作を維持する。公開 API シグネチャ (`exchange`, `refresh`,
  `buildAuthorizationUrl`) と `TokenSet` / `TokenRepository` 契約は不変。
  下流プロジェクトでの callback 401 を解消する破壊的でないバグ修正。

## [1.0.0] - 2026-05-25

初の一般公開リリース。Chatwork API v2 を Laravel から安全に利用するための Composer パッケージ。

### Added

- Chatwork API v2 の全エンドポイント群（rooms / messages / members / tasks / files /
  links / me / my / contacts / incoming requests）を `Resources/*` として実装。
- 3 つの利用経路: Facade（`Chatwork::`）、DI（`ChatworkClient` / `ChatworkManager`）、
  Laravel Notification チャンネル（`ChatworkChannel` / `ChatworkMessage` / `ChatworkRoute`）。
- 戻り値モードのチェーン切り替え: `asArray()` / `asDto()` / `asCollection()` /
  `asResponse()` / `asPsrResponse()` / `asResult()`。
- OAuth2 認可コードフロー（`OAuthClient` / `TokenRepository` / `StateStore`、
  state 検証付き callback、`Cache::lock` による refresh 多重発行防止）。
- 複数 connection・動的トークン（`TokenProvider`）・API Token / Bearer の排他的認証。
- `readonly` Response DTO、immutable Request DTO による型安全な API。
- Pest + Orchestra Testbench による全エンドポイントのテスト（`Http::fake()` 固定）。
- `ChatworkException` マーカー interface（本パッケージ由来の全例外を
  `catch (ChatworkException)` で一括捕捉可能）。設定・配線起因の誤りを表す
  `ChatworkConfigurationException` を追加。

### Changed

- `config('chatwork.response.mode')` を `ChatworkManager` の既定戻り値モードへ実際に
  配線。無効値は黙ってフォールバックせず `ChatworkConfigurationException` を投げる
  （fail-fast。送信前入力検証ではなく設定/配線エラーであるため `ChatworkValidationException`
  ではない）。それまで当該設定は無効だった。
- `illuminate/cache` / `illuminate/contracts` / `illuminate/routing` を `require` に
  明示宣言（従来は推移的依存）。`extra.branch-alias`（`dev-main` → `1.0.x-dev`）を追加。
- `composer-runtime-api`（`^2.0`）と `psr/http-message`（`^1.1 || ^2.0`）を `require`
  に明示宣言。`Composer\InstalledVersions`（User-Agent 構築）と PSR-7
  `ResponseInterface`（`asPsrResponse()` 戻り型）は公開コードパスで使用するが、
  従来は `illuminate/*` → guzzle 経由の推移的依存に暗黙依存していた。
- **BREAKING CHANGE:** 全 Response DTO に `MapsFromArray` 契約を導入し、
  `fromArray()` の戻り型を `self` → `static` に統一（`ResponseMapper` の
  `class-string<MapsFromArray>` 化により誤渡しを静的検出）。
- **BREAKING CHANGE:** `RoomData::type` / `role` を `RoomType` / `RoomRole` enum 化
  （未知値は防御的に既定値へフォールバック）。
- **BREAKING CHANGE:** `ChatworkRequestException` /
  `ChatworkAuthenticationException` / `ChatworkRoutingException` を `final` 化。
- **BREAKING CHANGE:** `CacheTokenRepository` のコンストラクタ第2引数に
  `Encrypter` が必須（OAuth トークンを暗号化保存）。既存の平文キャッシュ
  エントリは復号できず再認証が必要。
- **BREAKING CHANGE:** `Connection` のコンストラクタを `private` 化し
  `Connection::make()` を唯一の生成口に統一。`baseUri` スキーム不正時の
  例外を `InvalidArgumentException` → `ChatworkConfigurationException` に変更。
- **BREAKING CHANGE:** `TokenRepository::save()` のシグネチャを
  `save(TokenSet $tokenSet, array $context = [])` →
  `save(string $connectionName, TokenSet $tokenSet)` に変更。弱い連想配列契約と
  実装ごとに重複していた `$context['connection']` ガードを排除（`find()` と
  語順統一）。カスタム `TokenRepository` 実装・直接呼出は要更新
  （pre-1.0 のため公開リリース済み利用者への影響なし）。
- `chatwork.response.mode` を `CHATWORK_RESPONSE_MODE` 環境変数で上書き
  可能に（他設定キーと一貫）。`composer.json` の author に組織 homepage を追加。

### Fixed

- `RoomTasksResource::updateStatus()` を `UpdateRoomTaskStatusRequest` へ委譲し、
  Resource 層でのペイロード直組みを解消（他 Resource とパターン統一）。
- 重複していた整数 ID リストヘルパ（`idsToCsv` / `assertIntegerList` /
  `toIntList`）を `NormalizesIntegerList` / `ConvertsToIntList` トレイトへ集約。
- 共通 User-Agent ビルダーを `Http\UserAgent` に一元化（OAuth トークン
  エンドポイント要求にも付与）。
- `ChatworkMessage::toPayload()` / `ChatworkChannel::send()` の
  `@throws ChatworkValidationException` を docblock 化。

- `ChatworkMessage` を `final` 化し、`toPayload()` 契約をサブクラス上書きから保護。
- OAuth トークン要求に設定可能な HTTP タイムアウト（`chatwork.oauth.timeout` /
  `CHATWORK_OAUTH_TIMEOUT`、既定 10 秒）を適用。無応答時のワーカー無制限ブロックを防止。
- `ChatworkChannel::send()` の docblock を実装（4xx/5xx/429 すべて
  `ChatworkRequestException` に変換して throw＝queue retry トリガー）と整合。
- `ChatworkRoute` の connection フィールドを `readonly` 化（値オブジェクトの不変性徹底）。
- `CreatedMessage::fromArray()` が `message_id` 欠損時に TypeError を起こして
  いたのを、他 Response DTO と同じ `?? ''` フォールバックへ統一（空/壊れた
  レスポンスで throw しない不変条件を回復）。
- 8 リソースに重複していた Dto モードのリストアンラップを
  `ChatworkClient::sendList()` へ集約（挙動不変、将来のモード追加点を一元化）。
- `ChatworkClient::send()` / `upload()` の `$dtoClass` を
  `class-string<MapsFromArray>|null` へ厳格化（`ResponseMapper` と整合、
  DTO 誤渡しを静的検出）。
- `InMemoryTokenRepository` を `@internal` 化し、testing / local 以外の環境で
  生成された場合に `E_USER_NOTICE` で警告（本番誤用を能動検出）。
- 拡張点契約を凍結: `TokenRepository` / `MapsFromArray` に `@api`（v1 で恒久固定
  する後方互換契約）、`ChatworkClient::send()` / `sendList()` / `upload()` に
  `@internal`（低レベル配管、公開 API は Resource 層を使う）。
- 公開 Resource クラスに `@api` を付与し、list 系メソッドの `@return list<X>`
  文言を「`asDto()` 契約」に統一。単体取得系は `: mixed` のまま `@return` を
  宣言しない（PHPStan level 6 が具体型を honor し、全モードを検証するテスト・
  利用側コードと構造的に衝突するため）。`asDto()` モードのメソッド別戻り値型は
  README および `docs/03-package-architecture/response-strategy.md` の型対応表で明文化。
- `oauth.state_store` / `oauth.token_repository` に不存在クラスや契約 interface
  未実装クラスを設定しても無言で既定へフォールバックしていたのを、
  `ChatworkConfigurationException` で fail-loud に（誤設定の隠蔽を解消）。
- `oauth.route_throttle` を形式検証（`max,minutes` または limiter 名）。不正値は
  `ChatworkConfigurationException`。null/空は従来どおり throttle 無効。
- `RoomMessagesResource::find/update/deleteMessage` が `message_id` を URL パスへ
  無検証で埋め込んでいたのを、送信前に正の整数文字列を必須化
  （`ChatworkValidationException`、64bit 超 ID 保持のため文字列のまま）。
- `ChatworkChannel` の通知ルート `room_id` が非数値で `(int)` キャスト時に 0 へ
  黙殺され誤ルーム送信しうたのを、`ChatworkRoute::room()` で正の整数を必須化
  （`ChatworkRoutingException`）。
- 3 つの Request DTO に重複していた本文長検証を `ValidatesBodyLength`
  トレイトへ集約（挙動・文言不変）。

### Security

- `ApiTokenCredentials` / `BearerTokenCredentials` のトークンを `private` 化し、
  `__debugInfo()` でマスク。`var_dump` / `json_encode` / デバッグページからの
  認証情報の偶発的漏洩を防止。
- OAuth2 `state` のリプレイ防止には read-and-delete のアトミック性が必要なため、
  本番では `redis` / `database` キャッシュドライバを推奨する旨をドキュメント化。
- `TokenSet` / `Connection` に `__debugInfo()` を追加し、`var_dump` / `dd` /
  エラートラッカーからの access/refresh トークン・credentials の平文露出を防止。
- `ChatworkRequestException::redactBody()` を JSON 再帰マスク＋form-urlencoded
  対応に強化し、`token` / `authorization` / `code` / `client_id` / `password`
  も除去対象に追加（`errors()` / `error()` は生ボディからのパースで非干渉）。
- 既定 `CacheTokenRepository` が OAuth トークンを Laravel `Encrypter` で
  暗号化保存。復号不能・平文・不完全エントリは「未保存」として安全に扱う。
- `OAuthCallbackController` がコード交換・保存・設定起因の例外を捕捉し、
  固定理由コードのみ返却（`client_secret` / `code` を漏らさない、502 / 500）。
- OAuth callback ルートに設定可能な throttle（`chatwork.oauth.route_throttle`、
  既定 `10,1`）を適用し、`state` / `code` のブルートフォースを抑制。
- OAuth refresh ロック TTL を 10→30 秒に拡大し、トークンエンドポイント遅延時の
  二重 refresh（`invalid_grant`）を防止。
- `Connection::make()` / `OAuthClient` の base URI・token/authorization URL を
  http(s) スキームに限定（設定経由の SSRF 抑止）。
- `.github/SECURITY.md` と `composer.json` の `support.security` を追加。
- 設定差し替えポイント（CSRF `state` ストア / トークン保存先）の誤設定を
  `ChatworkConfigurationException` で fail-loud にし、非機能的な既定への
  無言フォールバックによるセキュリティ機能の喪失を防止。
- URL パス・通知ルートへ渡る ID（`message_id` / `room_id`）を送信前に厳格
  検証し、パスインジェクション・誤ルーム送信の余地を排除。

### Documentation

- OAuth callback が `web` グループ配下でも GET ルートのため Laravel の CSRF
  検証対象外であり、防御の実体は単回使用 `state` である旨を
  `docs/04-api-client/authentication.md` と `registerOAuthRoutes()` に明文化
  （利用者が不要な独自 CSRF except / `web` 除外を設定するのを防ぐ）。
- `ChatworkMessage::escape()` に `@see self::plain()` を付与（IDE 参照補強）。
- `RoomData::fromArray()` の未知 `type`/`role` フォールバックに、`MyTaskData` /
  `RoomTaskData` と整合するコメントを付与（フォールバック地点に置く既存規約に追従）。
- `withMode` の層非対称性（`ChatworkManager` は可変ゆえ clone+mutate で
  `private`、`ChatworkClient` は不変コラボレータ再構築ゆえ `public`）を docblock 化。
- レビュー指摘を精査し、以下は **評価のうえ意図的にコード変更しない**ことを記録:
  - `Route::getRoutes()->refreshNameLookups()` は冗長ではなく load-bearing
    （`->name()` が `RouteCollection::add()` の後に走り `addLookups()` が
    nameList へ登録しないため明示再構築が必須）。削除せず理由をコメント化。
  - `TokenSet::toArray()` に `@internal` は付与しない（`TokenRepository` 公開
    拡張点の正規シリアライズであり、静的解析が正当な拡張を誤検出するため）。
    使用契約 docblock を強化し平文露出は `__debugInfo()` マスキングで防止。
  - `OAuthTokenProvider` 例外文の connection 名は秘匿/ハッシュ化しない
    （非秘匿の config 識別子であり、どの connection で失敗したかの可観測性を
    優先。storage キーは別脅威面のため既に SHA-256 化済み）。
  - `UpdateRoomTaskStatusRequest::toArray()` の `body` がタスクステータス
    （メッセージ本文ではない）である旨はクラス docblock で充足済みのため
    重複コメントを追加しない（説明コメント禁止の規約に従う）。

[Unreleased]: https://github.com/trust-medical/laravel-chatwork-api/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/trust-medical/laravel-chatwork-api/releases/tag/v1.2.0
[1.1.0]: https://github.com/trust-medical/laravel-chatwork-api/releases/tag/v1.1.0
[1.0.2]: https://github.com/trust-medical/laravel-chatwork-api/releases/tag/v1.0.2
[1.0.1]: https://github.com/trust-medical/laravel-chatwork-api/releases/tag/v1.0.1
[1.0.0]: https://github.com/trust-medical/laravel-chatwork-api/releases/tag/v1.0.0
