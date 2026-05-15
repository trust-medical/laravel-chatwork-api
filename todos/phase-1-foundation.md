# Phase 1: Foundation（パッケージ土台）

## 目的

ServiceProvider 起動・Facade 解決・Connection 解決・認証ヘッダー付与までの土台を TDD で完成させる。Phase 2 以降のすべての Resource 実装が依存する基盤。

## 前提

- Phase 0 完了。
- `src/` skeleton が `LogicException` を投げるだけの状態で配置済み。
- fixture と `tests/TestCase.php` が揃っている。

## DoD（完了条件）

- Test ID `P1-T01` 〜 `P1-T11` のすべてが緑（`docs/06-testing/tdd-roadmap.md` 参照）。
- `Pint` / `PHPStan level 6` / `Pest` がすべて緑。
- `tests/Feature/` に Phase 1 関連テストが配置されている。
- カバレッジ目安: src/ のうち Phase 1 対象クラスは 80% 以上。

## TODO

### 1-1. ServiceProvider 起動（P1-T01, P1-T02）

- [ ] `tests/Feature/ServiceProviderTest.php` 作成
  - [Red] `it('boots without exception')` — ServiceProvider が例外なく register/boot される
  - [Red] `it('merges config from chatwork.php')` — `config('chatwork.base_uri')` が default URI を返す
  - [Red] `it('publishes config when requested')` — `php artisan vendor:publish --tag=chatwork-config` 相当
- [ ] [Green] `ChatworkServiceProvider::register()` — `mergeConfigFrom()`、Manager / Client / PendingRequestFactory / ResponseMapper の binding 登録
- [ ] [Green] `ChatworkServiceProvider::boot()` — `publishes()`、Facade alias、Notification channel 登録（Phase 3 で本実装）
- [ ] [Refactor] register() / boot() のメソッド分割

参照: `docs/03-package-architecture/service-container.md`

### 1-2. Facade 解決（P1-T03）

- [ ] `tests/Feature/FacadeTest.php` 作成
  - [Red] `it('resolves Chatwork facade to ChatworkManager')` — `Chatwork::getFacadeRoot() instanceof ChatworkManager`
- [ ] [Green] `Facades/Chatwork::getFacadeAccessor()` = `'chatwork'`
- [ ] [Green] container binding `$this->app->singleton('chatwork', fn() => new ChatworkManager(...))`

### 1-3. Connection 解決（P1-T04, P1-T05, P1-T06）

- [ ] `tests/Feature/ConnectionTest.php` 作成
  - [Red] `it('resolves default connection from config')` — `Chatwork::connection()` 例外なし
  - [Red] `it('resolves named connection sales')` — `Chatwork::connection('sales')` の Connection の `name` が `'sales'`
  - [Red] `it('accepts Connection value object via forConnection()')` — `Connection::make(...)` を渡せる
  - [Red] `it('throws when connection not configured')` — 存在しない connection 名で `ChatworkAuthenticationException` または `InvalidArgumentException`
- [ ] [Green] `ChatworkManager::connection(?string $name = null): self` — clone して内部の `Connection` を上書き
- [ ] [Green] `ChatworkManager::forConnection(Connection $c): self` — 同上
- [ ] [Green] `Connection::make(string $name, Credentials $credentials, ?string $baseUri = null, ?int $timeout = null): self` — ファクトリ
- [ ] [Refactor] config からの解決ロジックを `ConnectionResolver` private クラスに抽出（必要なら）

参照: `docs/04-api-client/authentication.md`、`docs/03-package-architecture/service-container.md` の境界表

### 1-4. 認証ヘッダー付与（P1-T07, P1-T08, P1-T09）

- [ ] `tests/Feature/AuthHeaderTest.php` 作成
  - [Red] `it('sends x-chatworktoken when withApiToken used')` — `Http::assertSent` で確認
  - [Red] `it('sends Authorization Bearer when withBearerToken used')` — 同上
  - [Red] `it('never sends both auth headers in one request')` — 両 method を呼んでも一方だけが残る
  - [Red] `it('falls back to default connection auth when no override')` — `withApiToken` 等を呼ばない場合
- [ ] [Green] `Auth/Credentials` interface (`applyTo(PendingRequest $req): PendingRequest`)
- [ ] [Green] `Auth/ApiTokenCredentials::applyTo()` → `$req->withHeaders(['x-chatworktoken' => $this->token])`
- [ ] [Green] `Auth/BearerTokenCredentials::applyTo()` → `$req->withToken($this->token)`
- [ ] [Green] `ChatworkManager::withApiToken(string $token): self` / `withBearerToken(string $token): self` — clone + Credentials 差し替え
- [ ] [Refactor] `applyTo()` の戻り値型を厳格化、token 値を `$this->token` から取り出すアクセサ整理

> **不変条件**: `applyTo()` は1メソッドにつき1ヘッダーしか設定しない。両方付与禁止。

### 1-5. PendingRequestFactory（P1-T10）

- [ ] `tests/Feature/PendingRequestFactoryTest.php` 作成
  - [Red] `it('applies base_uri from connection')` — `Http::assertSent` で URL prefix 確認
  - [Red] `it('applies timeout from connection')` — `Http::fake` の timeout assertion
  - [Red] `it('sends Accept application/json')` — header 確認
  - [Red] `it('sends User-Agent including package name and version')`
- [ ] [Green] `Http/ChatworkPendingRequestFactory::create(Connection $c): PendingRequest`
  - `Http::baseUrl($c->baseUri)` + `withHeaders(['Accept' => 'application/json'])` + `timeout($c->timeout)` + `withUserAgent(...)` + `$c->credentials->applyTo($req)`
- [ ] [Green] User-Agent 文字列の組み立て（package version は `Composer\InstalledVersions::getPrettyVersion()` 等で取得、dev 環境では `dev` 固定可）
- [ ] [Refactor] 各設定を private method に分離

参照: `docs/04-api-client/request-response.md` の Timeout / User-Agent セクション

### 1-6. 戻り値モードの immutable clone（P1-T11）

- [ ] `tests/Feature/ResponseModeStateTest.php` 作成
  - [Red] `it('returns a cloned manager from asResult')` — `$a = Chatwork::asResult(); $a !== Chatwork::getFacadeRoot()`
  - [Red] `it('does not mutate global manager state')` — `Chatwork::asResult(); Chatwork::getResponseMode() === default`
  - [Red] `it('chains modes with last-wins semantics')` — `asResult()->asArray()` の最終 mode が array
- [ ] [Green] `ChatworkManager::asArray()`, `asDto()`, `asCollection()`, `asResponse()`, `asPsrResponse()`, `asResult()` をすべて `clone $this` で実装
- [ ] [Green] `protected ResponseMode $mode` の保持と参照
- [ ] [Green] `Enums/ResponseMode` enum 定義（`Array`, `Dto`, `Collection`, `Response`, `PsrResponse`, `Result`）
- [ ] [Refactor] mode 切替を `withMode(ResponseMode)` private に集約

参照: `docs/03-package-architecture/response-strategy.md`

### 1-7. Exception 基盤

- [ ] `tests/Unit/Exceptions/ChatworkRequestExceptionTest.php`
  - [Red] `it('exposes status, method, path, operationId')`
  - [Red] `it('parses Chatwork errors[] from body')`
  - [Red] `it('parses OAuth error/error_description from body')`
  - [Red] `it('exposes rateLimit array when x-ratelimit headers present')`
  - [Red] `it('redacts access_token / refresh_token / client_secret from body')`
- [ ] [Green] `Exceptions/ChatworkRequestException` — getters + redaction
- [ ] [Green] `Exceptions/ChatworkValidationException` — message + violations: array
- [ ] [Green] `Exceptions/ChatworkAuthenticationException` — message のみ
- [ ] [Green] `Exceptions/ChatworkRoutingException extends ChatworkValidationException`
- [ ] [Refactor] redaction を `private static function redactBody(string $body): string` に抽出

参照: `docs/04-api-client/request-response.md` の エラーBody / Rate Limit セクション、`docs/04-api-client/authentication.md` の秘密情報の漏洩防止

### 1-8. ResponseMapper（Phase 2 への橋渡し）

- [ ] `tests/Unit/Http/ResponseMapperTest.php` の skeleton 作成
  - [Red] `it('routes asArray mode to json array')` — `Http::fake` の Response から `[]` 取得
  - [Red] `it('routes asResponse mode to Illuminate Response unchanged')`
  - [Red] `it('routes asPsrResponse mode to Psr ResponseInterface')`（Guzzle 経由）
  - [Red] `it('throws ChatworkRequestException for asDto on 4xx')`
  - [Red] `it('returns failed Result for asResult on 4xx')`
- [ ] [Green] `Http/ResponseMapper::map(Response $res, ResponseMode $mode, string $dtoClass = null, ...): mixed`
  - DTO マッピングは `$dtoClass::fromArray($res->json())` で実装（Phase 2 で DTO 側を実装）
- [ ] [Green] `Http/Result` 実装（`succeeded()/failed()/status()/data()/errors()/rateLimit()/toException()`）

参照: `docs/03-package-architecture/response-strategy.md`

### 1-9. 検証

- [ ] `P1-T01` 〜 `P1-T11` がすべて緑
- [ ] `composer test` 緑
- [ ] `composer analyse`（PHPStan level 6）緑
- [ ] `composer lint`（Pint --test）緑
- [ ] カバレッジ 80% 以上（src/Auth、src/Http、src/、src/Facades が対象）
- [ ] `code-reviewer` agent で CRITICAL/HIGH を解消
- [ ] commit: 各 test ID ごとに Red/Green/Refactor 分割（許容: 関連 test を 1 コミットにまとめる）

## 完了後

→ Phase 2 へ進む（POST /rooms/{room_id}/messages 実装）。
