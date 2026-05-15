# Architecture Review: trust-medical/laravel-chatwork-api (Round 1)

## Verdict: **GO with caveats**

設計の骨格は健全で実装着手可能。ただし下記の HIGH 項目（依存方向の表記揺れ・`MyResource` の欠落・`TokenProvider` interface 仕様未定義・`asPsrResponse` の実現手段）を Phase 1 着手前に詰めることを推奨。

---

## CRITICAL
なし。設計が破綻するレベルの矛盾は検出されず。

---

## HIGH

1. **依存方向の表記揺れ（package-structure.md vs CLAUDE.md）**
   - `docs/03-package-architecture/package-structure.md:87-94` は `ChatworkManager -> ConnectionFactory -> ChatworkClient` という3層構造を示すが、`CLAUDE.md` の依存方向セクションでは `ChatworkManager -> Connection (値オブジェクト) / ChatworkClient` の2分岐になっている。`ConnectionFactory` の存在・責務（Manager 内 private か独立クラスか）が未確定。Manager 実装時に拡張ポイントが揺れる原因になるため、`ConnectionFactory` を採用するか `Connection::make()` ファクトリメソッドで済ますか、決定が必要。

2. **`MyResource` が package-structure.md から欠落**
   - `docs/03-package-architecture/package-structure.md:62-72` の `Resources/` 列挙に `MyResource.php` がない（`MeResource.php` のみ）。CLAUDE.md の src/ 構成および機能要件（`/my/*` エンドポイント群）では存在前提。Phase 6 で着手前に補記必須。

3. **`TokenProvider` interface 仕様が未定義**
   - `service-container.md:80-87` は `Credentials::fromProvider($provider)` で `TokenProvider` を渡せると述べるが、interface 定義（`credentials(): Credentials` か `token(): string` か、refresh 責務の所在、例外型）が docs に存在しない。CLAUDE.md の src/ 構成に `Auth/TokenProvider.php` は記載されているが signature が未確定。OAuth2 refresh フロー（Phase 4）と密接に絡むため、Phase 1 で interface だけは確定させるべき。

4. **`asPsrResponse()` の実装経路が抽象的**
   - `response-strategy.md:96-99` は「Laravel HTTP Client の内部実装に密結合しない adapter を明示する」と述べるが、Laravel HTTP Client は内部で Guzzle を使うため `toPsrResponse()` 取り出しは `Response::toPsrResponse()` 相当が必要。Guzzle 以外の transport（Mock など）使用時の挙動が未定義。`asPsrResponse` を Guzzle 必須とするか、ResponseMapper で `Nyholm/psr7` ラップするかの方針決定が必要。

5. **`Connection` 値オブジェクトと `connection()` 名前解決の境界**
   - `service-container.md:18-23` で `Chatwork::connection('sales')`, `Chatwork::forConnection($connection)` を併設しているが、`connection()` が config 名前解決専用、`forConnection()` が値オブジェクト直接渡し、という二系統の使い分けが docs 本文に明記されていない。Manager のメソッド命名表で確定させるべき。

---

## MEDIUM

- **戻り値モードの状態管理の所在**: `Chatwork::asResult()->rooms()->messages()->create(...)` というチェーンで `asResult()` の状態がどこで保持されるか（Manager 単位 / Client 単位 / Resource 単位）未明示。Manager クローンか immutable copy かを response-strategy.md に追記推奨。
- **`NoContentData` DTO**: `response-strategy.md:86-93` で参照されるが、`Data/Responses/` 配下の存在が package-structure.md に明示されていない。
- **`ChatworkRoute` の責務**: `notification-channel.md:42` で `ChatworkRoute を解決する` とあるが、誰が（Channel か Notifiable か）どう生成するかが未記載。
- **`ChatworkPendingRequestFactory` の責務範囲**: 認証ヘッダー付与までか、`asForm()` 設定までか、base_uri / timeout 適用までかが docs に未記載。

---

## LOW

- **`Auth/TokenProvider.php` のディレクトリ位置**: CLAUDE.md は `Auth/TokenProvider.php`、package-structure.md は `Auth/TokenProvider.php`（一致）。一方で `Credentials.php` は CLAUDE.md と package-structure.md 双方にあるが、interface か abstract か明示なし。
- **`extra.laravel.providers` の具体例なし**: package-structure.md:24 で言及するが composer.json サンプルに含まれていない。
- **config キー命名**: `oauth.token_repository`, `oauth.state_store` を class 名文字列で受ける想定だが、container binding key と config キーの優先順位が未記載。

---

## 強み

- 依存方向が単方向（Facade → Manager → Client → Resources）で循環なし。Notification 側も Resource 経由で HTTP 詳細を隠蔽しており、テスタビリティが高い。
- 戻り値 6 モード + バリデーション例外の責任分離（送信前は常に throw、HTTP は mode 依存）が明快で、利用者・テストともに予測可能。
- OAuth2 を `Auth/OAuth/` に隔離し callback route をデフォルト無効にする設計は OSS パッケージとして堅実（セキュリティ・最小権限の原則に合致）。

---

## 参照ファイル

- `docs/03-package-architecture/package-structure.md`
- `docs/03-package-architecture/service-container.md`
- `docs/03-package-architecture/response-strategy.md`
- `docs/01-requirements/functional-requirements.md`
- `docs/05-notifications/notification-channel.md`
- `CLAUDE.md`
