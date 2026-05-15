# Phase 4: OAuth2

## 目的

OAuth2 認可コードフロー（authorization URL 生成 → callback で `code` 受領 → `/token` で access_token 取得 → refresh token による更新 → `Cache::lock` による多重発行抑止）を完成させる。秘密情報の漏洩防止と state 検証を必須にする。

## 前提

- Phase 2 完了（HTTP 基盤が動作する）。

## DoD

- 次のテストが緑:
  - authorization URL に `client_id` / `redirect_uri` / `state` / `response_type=code` / `scope` が含まれる
  - state が `StateStore` に保存される（TTL 付き）
  - callback で `state` 検証されない場合は token endpoint へ送信されない
  - authorization code が `POST /token` に送信される（fixture: `oauth/token-200.json`）
  - refresh token で access token が更新される
  - 同時 refresh は `Cache::lock` で 1 回だけ実行される
  - `TokenRepository` に保存処理が委譲される
  - `OAuthTokenProvider::credentials()` が現時点で有効な `Credentials` を返す（事前 refresh 含む）
  - 秘密情報（client_secret / refresh_token / access_token）が例外メッセージ・ログに出ない
  - callback URL の `error` パラメータが 400 を返す
- security-reviewer agent CRITICAL/HIGH を解消（Phase 4 完了時必須）

## TODO

### 4-1. TokenSet readonly value object

- [ ] `tests/Unit/Auth/OAuth/TokenSetTest.php`
  - [Red] `it('is readonly')`
  - [Red] `it('exposes accessToken, refreshToken, expiresAt, tokenType')`
  - [Red] `it('isExpired() returns true after expiresAt')`
  - [Red] `it('isExpired(leeway: 60) returns true when within leeway')`
  - [Red] `it('fromArray()/toArray() round-trip')`
- [ ] [Green] `src/Auth/OAuth/TokenSet.php`
  - `readonly class` with `accessToken: string`, `refreshToken: string`, `expiresAt: \DateTimeImmutable`, `tokenType: string`
  - `isExpired(int $leewaySeconds = 0): bool`
  - `static fromArray(array $data): self`
  - `toArray(): array`

参照: `docs/02-openapi/chatwork-api-v2-complemented.openapi.json` の `issueOAuthToken` レスポンス

### 4-2. TokenRepository / StateStore interfaces + default impl

- [ ] `tests/Unit/Auth/OAuth/InMemoryTokenRepositoryTest.php`
  - [Red] `it('save and find round-trip')`
  - [Red] `it('returns null when not found')`
- [ ] [Green] `src/Auth/OAuth/TokenRepository.php`（interface）
- [ ] [Green] `src/Auth/OAuth/InMemoryTokenRepository.php`（テスト用、static array）
- [ ] [Green] `src/Auth/OAuth/CacheTokenRepository.php`（参考実装、Laravel cache を使う、optional）
- [ ] `tests/Unit/Auth/OAuth/CacheStateStoreTest.php`
  - [Red] `it('put then pull within TTL')`
  - [Red] `it('returns null after TTL expires')`
  - [Red] `it('pull is one-shot (consumes the state)')`
- [ ] [Green] `src/Auth/OAuth/StateStore.php`（interface）
- [ ] [Green] `src/Auth/OAuth/CacheStateStore.php`（Laravel cache driver を使う）

参照: `docs/04-api-client/authentication.md` の TokenRepository / StateStore セクション

### 4-3. OAuthClient: authorization URL

- [ ] `tests/Feature/Auth/OAuth/AuthorizationUrlTest.php`
  - [Red] `it('builds URL with client_id, redirect_uri, state, response_type=code')`
  - [Red] `it('includes scope when provided')`
  - [Red] `it('persists state to StateStore with TTL')`
  - [Red] `it('generates cryptographically random state (>= 32 chars)')`
- [ ] [Green] `src/Auth/OAuth/OAuthClient::buildAuthorizationUrl(array $context = [], ?array $scopes = null): string`
  - `random_bytes(24)` + `bin2hex` で state 生成
  - `StateStore::put($state, $context, ttl=600)`
  - クエリ組み立て: `http_build_query([...])`
- [ ] [Refactor] state 生成を `private function generateState(): string` に

### 4-4. OAuthClient: exchange (authorization code → token)

- [ ] `tests/Feature/Auth/OAuth/ExchangeTest.php`
  - [Red] `it('POSTs to /token with grant_type=authorization_code')`
  - [Red] `it('includes code, client_id, client_secret, redirect_uri')`
  - [Red] `it('parses TokenSet from response')`
  - [Red] `it('throws ChatworkRequestException for 400 invalid_grant')`
  - [Red] `it('redacts client_secret in exception body')`
- [ ] [Green] `OAuthClient::exchange(string $code): TokenSet`
  - HTTP POST `https://oauth.chatwork.com/token` with form-urlencoded
  - 失敗時 `ChatworkRequestException`、body は `redactBody` で client_secret を `***` に
- [ ] [Refactor] `OAuthClient` の HTTP 送信を `private function token(array $params): TokenSet` に集約

### 4-5. OAuthClient: refresh

- [ ] `tests/Feature/Auth/OAuth/RefreshTest.php`
  - [Red] `it('POSTs to /token with grant_type=refresh_token')`
  - [Red] `it('includes refresh_token, client_id, client_secret')`
  - [Red] `it('returns new TokenSet')`
  - [Red] `it('throws ChatworkAuthenticationException for invalid_grant')`
  - [Red] `it('redacts refresh_token in exception body')`
- [ ] [Green] `OAuthClient::refresh(string $refreshToken): TokenSet`

### 4-6. OAuthCallbackController

- [ ] `tests/Feature/Auth/OAuth/CallbackControllerTest.php`
  - [Red] `it('returns 302 to redirect_after_callback on success')`
  - [Red] `it('saves TokenSet to TokenRepository')`
  - [Red] `it('returns 400 when state is missing or invalid')`
  - [Red] `it('returns 400 when error parameter is present')` — token endpoint には送信しない
  - [Red] `it('returns 500 when TokenRepository is not configured')`
  - [Red] `it('does NOT log refresh_token / access_token')`
- [ ] [Green] `src/Auth/OAuth/Controllers/OAuthCallbackController.php`
  - `__invoke(Request $request): Response` — query params 取得、state pull、exchange、save、redirect
- [ ] [Green] `ChatworkServiceProvider::boot()` で `'oauth.routes_enabled' === true` のときだけ route を登録
- [ ] [Refactor] レスポンス組み立て（200/400/500）を private に分離

参照: `docs/01-requirements/functional-requirements.md` の OAuth2 callback HTTP 仕様、`docs/04-api-client/authentication.md` の OAuth2 callback ハンドリング分岐

### 4-7. OAuthTokenProvider（事前 refresh）

- [ ] `tests/Unit/Auth/OAuth/OAuthTokenProviderTest.php`
  - [Red] `it('returns bearerToken credentials from latest TokenSet')`
  - [Red] `it('refreshes when token is expired')` — `Http::fake` で `/token` 呼び出しを確認
  - [Red] `it('refreshes when within leeway')`
  - [Red] `it('saves refreshed TokenSet via TokenRepository')`
  - [Red] `it('throws ChatworkAuthenticationException when refresh fails')`
  - [Red] `it('uses Cache::lock to prevent concurrent refresh')` — `Cache::lock` を mock し double-call で1回しか refresh されないことを確認
  - [Red] `it('falls back to re-read from TokenRepository on lock contention')`
- [ ] [Green] `src/Auth/OAuth/OAuthTokenProvider.php` implements `TokenProvider`
  - `__construct(string $connectionName, TokenRepository $repo, OAuthClient $oauth, int $leewaySeconds = 60)`
  - `credentials(): Credentials` 内で:
    1. `$tokenSet = $repo->find($connectionName)`
    2. `$tokenSet->isExpired(leeway: $leewaySeconds)` なら refresh
    3. refresh は `Cache::lock("chatwork:oauth:refresh:{name}", 10)->block(1)` の中で実行
    4. lock 取れない場合は `sleep(0.5)` 後に `$repo->find()` を再読み込みして expired なら例外

参照: `docs/04-api-client/authentication.md` の Refresh戦略

### 4-8. config / publish

- [ ] `config/chatwork.php` の `oauth` セクションが実装と一致していることを確認
- [ ] `redirect_after_callback` キーを追加（未設定なら `/`）

### 4-9. セキュリティレビュー（必須）

- [ ] `security-reviewer` agent を起動
- [ ] チェック項目:
  - client_secret が例外メッセージ・ログ・debug 出力に出ていないか
  - state 検証が必須でバイパス経路が無いか
  - state TTL が短い（10 分以内）
  - `Cache::lock` のキーが連結文字列で injection 可能でないか
  - redirect 先が user-controlled でないか（open redirect）
  - SSRF 経路がないか（token endpoint URL は config 値だが、CHATWORK_OAUTH_TOKEN_URL を env で受ける場合のリスク）

### 4-10. 検証

- [ ] 全テスト緑
- [ ] `code-reviewer` + `security-reviewer` CRITICAL/HIGH 解消
- [ ] commit 粒度: 4-1 〜 4-7 を 1 commit/section 程度

## 完了後

→ Phase 5（Messages 残）または Phase 6 以降へ。
