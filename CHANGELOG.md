# Changelog

All notable changes to `trust-medical/laravel-chatwork-api` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

> リリースタグを打つ際に、この `[Unreleased]` 見出しを `[1.0.0] - YYYY-MM-DD` へ
> rename し、新しい空の `[Unreleased]` を上に追加すること。

## [Unreleased]

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
  配線。無効値は黙ってフォールバックせず `ChatworkValidationException` を投げる
  （fail-fast）。それまで当該設定は無効だった。
- `illuminate/cache` / `illuminate/contracts` / `illuminate/routing` を `require` に
  明示宣言（従来は推移的依存）。`extra.branch-alias`（`dev-main` → `1.0.x-dev`）を追加。
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

[Unreleased]: https://github.com/trust-medical/laravel-chatwork-api/commits/main
