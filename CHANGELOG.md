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

### Changed

- `config('chatwork.response.mode')` を `ChatworkManager` の既定戻り値モードへ実際に
  配線。無効値は黙ってフォールバックせず `ChatworkValidationException` を投げる
  （fail-fast）。それまで当該設定は無効だった。
- `illuminate/cache` / `illuminate/contracts` / `illuminate/routing` を `require` に
  明示宣言（従来は推移的依存）。`extra.branch-alias`（`dev-main` → `1.0.x-dev`）を追加。

### Fixed

- `ChatworkMessage` を `final` 化し、`toPayload()` 契約をサブクラス上書きから保護。
- OAuth トークン要求に設定可能な HTTP タイムアウト（`chatwork.oauth.timeout` /
  `CHATWORK_OAUTH_TIMEOUT`、既定 10 秒）を適用。無応答時のワーカー無制限ブロックを防止。
- `ChatworkChannel::send()` の docblock を実装（4xx/5xx/429 すべて
  `ChatworkRequestException` に変換して throw＝queue retry トリガー）と整合。
- `ChatworkRoute` の connection フィールドを `readonly` 化（値オブジェクトの不変性徹底）。

### Security

- `ApiTokenCredentials` / `BearerTokenCredentials` のトークンを `private` 化し、
  `__debugInfo()` でマスク。`var_dump` / `json_encode` / デバッグページからの
  認証情報の偶発的漏洩を防止。
- OAuth2 `state` のリプレイ防止には read-and-delete のアトミック性が必要なため、
  本番では `redis` / `database` キャッシュドライバを推奨する旨をドキュメント化。

[Unreleased]: https://github.com/trust-medical/laravel-chatwork-api/commits/main
