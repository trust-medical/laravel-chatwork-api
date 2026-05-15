---
paths:
  - "tests/**/*.php"
  - "src/**/*.php"
---

# テスト規約

このルールは `tests/**/*.php` の編集時、および `src/**` 編集時に対応テストを書く際に適用する。

## フレームワーク

- **Pest** + **Orchestra Testbench** + **Laravel HTTP Client** (`Http::fake()`)。
- テスト基底は `TrustMedical\LaravelChatworkApi\Tests\TestCase`（`tests/TestCase.php`）。
- `tests/Pest.php` の `uses(TestCase::class)->in('Feature', 'Unit')` で自動適用される。

## HTTP テストの絶対ルール

1. **実 API は絶対に叩かない**。
2. `setUp()` で `Http::preventStrayRequests()` を必ず呼ぶ（既に基底クラスで実施済み）。
3. すべての外部通信は `Http::fake([...])` でモックする。
4. fixture は `tests/Fixtures/chatwork/<resource>/<operation>-<status>.json` に置く。
5. `$this->fixture($relativePath)` / `$this->fixtureJson($relativePath)` ヘルパで読み込む。

## テスト構造

- ファイル名: `tests/Feature/<相対パス>Test.php` または `tests/Unit/<相対パス>Test.php`
  - 例: `src/Resources/RoomMessagesResource.php` → `tests/Feature/Resources/RoomMessagesResourceTest.php`
- 1 エンドポイントに対して以下の観点でテストを書く:
  - 正しい URL / HTTP method
  - 認証ヘッダー（`x-chatworktoken` または `Authorization: Bearer`）
  - リクエスト形式（form / multipart / query）と payload
  - 成功時の戻り値（DTO / array / Collection など）
  - 送信前バリデーション失敗 → `ChatworkValidationException`
  - 4xx/5xx → 戻り値モードに応じた `ChatworkRequestException` / `Result`
  - 204 No Content の扱い

## Pest 慣行

- `describe()` / `it()` でグループ化する。
- アサーションは `expect()` を中心にする。`Http::assertSent` も併用可。
- データプロバイダは `with()` を使う。`@dataProvider` PHPDoc は不可。

## 例

```php
it('posts a message with form body and api token header', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            $this->fixtureJson('messages/create-message-200.json'),
            201,
        ),
    ]);

    Chatwork::withApiToken('token')
        ->rooms()
        ->messages()
        ->create(123, '本文');

    Http::assertSent(fn (Request $r) =>
        $r->method() === 'POST'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123/messages'
        && $r->hasHeader('x-chatworktoken', 'token')
        && $r['body'] === '本文'
    );
});
```

## カバレッジ

- `composer run test:coverage` で 80%以上を維持する目標。
- ただし Facade は対象外（`phpunit.xml` で除外）。

## 禁止事項

- ネットワーク通信を伴うテスト。
- sleep / 実時間に依存するテスト（時刻は `Carbon::setTestNow()` で固定）。
- 1 テスト内で複数 endpoint をまたぐシナリオテスト（責務分離）。
