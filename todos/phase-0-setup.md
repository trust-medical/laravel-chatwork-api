# Phase 0: Setup & Fixture

## 目的

実装着手前に、開発環境・依存・テストインフラ・fixture を一括で揃える。Phase 1 の Red を即書ける状態にする。

## 前提

- なし（プロジェクト初期段階）。

## DoD（完了条件）

- `composer install` が成功する。
- `./vendor/bin/pest` がスケルトンテスト 1 件で緑（`tests/Unit/SkeletonTest.php`）。
- `./vendor/bin/phpstan analyse` がエラーなし（level 6、src/ 対象）。
- `./vendor/bin/pint --test` で違反なし。
- Phase 2 で使う fixture が `tests/Fixtures/chatwork/messages/` に揃っている。
- `src/` のクラス skeleton（メソッド本体は `throw new \LogicException('not implemented')`）が `tdd-roadmap.md` の Phase 1 DoD どおりに配置されている。

## TODO

### 0-1. Composer 依存

- [ ] `composer.json` を整備
  - `name: trust-medical/laravel-chatwork-api`
  - `require`: `php ^8.3`, `illuminate/support ^11.0||^12.0||^13.0`, `illuminate/http`, `illuminate/notifications`
  - `require-dev`: `pestphp/pest ^3.0`, `pestphp/pest-plugin-laravel`, `orchestra/testbench ^9.0||^10.0`, `phpstan/phpstan`, `larastan/larastan`, `laravel/pint`
  - `autoload.psr-4`: `TrustMedical\\LaravelChatworkApi\\: src/`
  - `autoload-dev.psr-4`: `TrustMedical\\LaravelChatworkApi\\Tests\\: tests/`
  - `extra.laravel.providers`: `[TrustMedical\\LaravelChatworkApi\\ChatworkServiceProvider::class]`
  - `scripts`: `test`, `lint`, `analyse`
  - 参照: `docs/03-package-architecture/package-structure.md`
- [ ] `composer install` 実行確認

### 0-2. 静的解析・整形・テスト設定

- [ ] `phpstan.neon` 確認（既存あり）。level 6、`paths: [src]`、`bootstrapFiles: [vendor/larastan/larastan/bootstrap.php]`
- [ ] `pint.json` 確認（既存あり）。preset = laravel
- [ ] `phpunit.xml` 確認（既存あり）。`<testsuites>` に Unit / Feature、bootstrap = `vendor/autoload.php`
- [ ] `testbench.yaml` 確認（既存あり）。providers に `ChatworkServiceProvider`

### 0-3. テスト基盤

- [ ] `tests/TestCase.php` 作成
  - `Orchestra\Testbench\TestCase` 継承
  - `setUp()` で `Http::preventStrayRequests()`
  - `getPackageProviders()` で `ChatworkServiceProvider::class` を返す
  - 参照: `docs/06-testing/http-fake-strategy.md`
- [ ] `tests/Pest.php` 作成
  - `uses(TestCase::class)->in('Feature', 'Unit')`
  - `function fixture(string $relativePath): string` 定義
  - `expect()->extend('toBeReadonly', ...)`（参照: `.claude/rules/testing.md`）
- [ ] `tests/Unit/SkeletonTest.php` 作成（true を assert する 1 件のみ。pipeline 確認用）

### 0-4. src/ クラス skeleton 配置

すべて `throw new \LogicException('not implemented in Phase 0')` でメソッド本体を埋める。

- [ ] `src/ChatworkServiceProvider.php`
- [ ] `src/ChatworkManager.php`
- [ ] `src/ChatworkClient.php`
- [ ] `src/Connection.php`
- [ ] `src/Facades/Chatwork.php`
- [ ] `src/Auth/Credentials.php`（interface）
- [ ] `src/Auth/ApiTokenCredentials.php`
- [ ] `src/Auth/BearerTokenCredentials.php`
- [ ] `src/Auth/TokenProvider.php`（interface）
- [ ] `src/Auth/OAuth/OAuthClient.php`
- [ ] `src/Auth/OAuth/TokenSet.php`（readonly class）
- [ ] `src/Auth/OAuth/TokenRepository.php`（interface）
- [ ] `src/Auth/OAuth/StateStore.php`（interface）
- [ ] `src/Auth/OAuth/Controllers/OAuthCallbackController.php`
- [ ] `src/Data/Requests/` ディレクトリ（`.gitkeep` のみ）
- [ ] `src/Data/Responses/NoContentData.php`（readonly class）
- [ ] `src/Data/Responses/` の他ファイルは Phase ごとに追加
- [ ] `src/Enums/` ディレクトリ（`.gitkeep` のみ）
- [ ] `src/Exceptions/ChatworkRequestException.php`
- [ ] `src/Exceptions/ChatworkValidationException.php`
- [ ] `src/Exceptions/ChatworkAuthenticationException.php`
- [ ] `src/Exceptions/ChatworkRoutingException.php`（`ChatworkValidationException` 継承）
- [ ] `src/Http/ChatworkPendingRequestFactory.php`
- [ ] `src/Http/ResponseMapper.php`
- [ ] `src/Http/Result.php`（= `ChatworkResult`、`asResult()` 用）
- [ ] `src/Notifications/ChatworkChannel.php`
- [ ] `src/Notifications/ChatworkMessage.php`
- [ ] `src/Notifications/ChatworkNotification.php`（abstract）
- [ ] `src/Notifications/ChatworkRoute.php`
- [ ] `src/Resources/RoomsResource.php`
- [ ] `src/Resources/RoomMessagesResource.php`
- [ ] `src/Resources/RoomMembersResource.php`
- [ ] `src/Resources/RoomTasksResource.php`
- [ ] `src/Resources/RoomFilesResource.php`
- [ ] `src/Resources/RoomLinksResource.php`
- [ ] `src/Resources/ContactsResource.php`
- [ ] `src/Resources/IncomingRequestsResource.php`
- [ ] `src/Resources/MeResource.php`
- [ ] `src/Resources/MyResource.php`

参照: `docs/03-package-architecture/package-structure.md`、`docs/06-testing/tdd-roadmap.md` の Phase 1 DoD セクション。

### 0-5. config / publish

- [ ] `config/chatwork.php` の実ファイル配置（docs `service-container.md` の例を採用）
- [ ] `ChatworkServiceProvider` で `mergeConfigFrom()` + `publishes()` の準備（実装は Phase 1）

### 0-6. fixture 生成

`docs/06-testing/http-fake-strategy.md` の命名規則に従い、OpenAPI example を起点に作成する。

- [ ] `tests/Fixtures/chatwork/messages/create-message-201.json`（Phase 2 必須）
- [ ] `tests/Fixtures/chatwork/messages/create-message-400.json`（Phase 2 必須）
- [ ] `tests/Fixtures/chatwork/messages/create-message-401.json`（Phase 2 必須）
- [ ] `tests/Fixtures/chatwork/messages/create-message-429.json`（Phase 2 必須）
- [ ] `tests/Fixtures/chatwork/messages/list-messages-200.json`（Phase 5）
- [ ] `tests/Fixtures/chatwork/messages/list-messages-204.json`（Phase 5、空配列または `[]`）
- [ ] `tests/Fixtures/chatwork/messages/get-message-200.json`（Phase 5）
- [ ] `tests/Fixtures/chatwork/messages/update-message-200.json`（Phase 5）
- [ ] `tests/Fixtures/chatwork/messages/delete-message-200.json`（Phase 5）
- [ ] `tests/Fixtures/chatwork/messages/mark-read-200.json`（Phase 5）
- [ ] `tests/Fixtures/chatwork/messages/mark-unread-200.json`（Phase 5）
- [ ] `tests/Fixtures/chatwork/oauth/token-200.json`（Phase 4）
- [ ] `tests/Fixtures/chatwork/oauth/token-400.json`（Phase 4、`invalid_grant`）

> Phase 6 以降の fixture は各 Phase 着手時に追加する（先送りしてもよい）。Phase 2-5 用のものは Phase 0 で揃える。

### 0-7. CI 雛形（最低限）

- [ ] `.github/workflows/ci.yml` を雛形だけ作成（matrix: PHP 8.3 / 8.4、Laravel 11/12/13）
  - 内容: `composer install`, `pint --test`, `phpstan analyse`, `pest`
  - 完成版は Phase 14 で詰める。Phase 0 では PR ごとに緑になる最低限を入れる。

### 0-8. 検証

- [ ] `composer install` 緑
- [ ] `./vendor/bin/pest` 緑（skeleton test 1 件）
- [ ] `./vendor/bin/phpstan analyse` エラーなし
- [ ] `./vendor/bin/pint --test` 違反なし
- [ ] git status で意図しないファイルが untracked になっていないこと
- [ ] commit: `chore: bootstrap package skeleton and fixtures`

## 完了後

→ Phase 1 へ進む。
