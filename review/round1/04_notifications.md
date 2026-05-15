# Round1 Review: Notification Channel 設計

**対象**: `docs/05-notifications/{notification-channel,chatwork-message-builder,routing}.md`
**依存方向**: `docs/03-package-architecture/package-structure.md`
**Resource境界**: `docs/04-api-client/resources-and-methods.md`

## Verdict: **GO with caveats**

3要素（Channel / Message / Route）の責務分割は明確で、Resource経由制約と routing 優先順位の衝突検知方針は良い。ただし、`toChatwork()` の戻り値型と失敗ハンドリングの仕様が曖昧で、実装フェーズ移行前に最低限以下を確定すべき。

---

## CRITICAL

1. **`toChatwork($notifiable)` の戻り値型が未定義**
   `notification-channel.md` L44 で「`ChatworkMessage` を得る」とあるが、Laravel標準の `toSlack` / `toMail` のように戻り値型（`ChatworkMessage` のみ／`string` 許可／`array` 許可）が明示されていない。
   `ChatworkMessage` 厳格 + `string` を糖衣として許容（内部で `new ChatworkMessage($s)` にラップ）を明文化すべき。

2. **`ChatworkNotification` の役割が曖昧**
   `chatwork-message-builder.md` で `ChatworkMessage` 自体を Notification として送れるとあるが、`ChatworkNotification` クラスが別に存在する理由・差分（`via()`, `toChatwork()` の自動実装？）が `notification-channel.md` から読み取れない。両者の関係を明記。

## HIGH

3. **送信失敗時の queue リトライポリシー欠落**
   「Laravel標準の `ShouldQueue` / `Queueable` に委譲」とあるが、`ChatworkRequestException` が `429`/`5xx` の場合に `release()` するか、独自 `shouldRetry()` を提供するかが未定義。最低限「4xxはfail、5xx/429は再送に乗せる」方針の明記が必要。本パッケージはretry実装しない方針（CLAUDE.md）なので、queue再送に委ねる旨を明示。

4. **戻り値モードの指針が `asDto()` 固定で硬すぎる**
   `notification-channel.md` L72 で「channel は `asDto()` 相当で送信」とあるが、`NotificationSent` event の `response` に何が入るかは利用者にとって重要。`asResult()` をデフォルトにして、成功時は DTO、失敗時は `ChatworkResult::failed()` を event payload に渡す方がqueue環境では扱いやすい。少なくとも選択理由を記載。

5. **Route 配列の部分失敗集約が未設計**
   `routing.md` L71-72 で「一部失敗時の集約結果は後続設計で詳細化」と先送りしているが、配列route が初期に許可されると `ChatworkRequestException` の中断挙動（残room送信中止 vs 続行）が未定義のままになる。MVPでは「配列の途中失敗で即throw・残room送信しない」と明記、もしくは初期は配列禁止に倒すべき。

## MEDIUM / LOW

6. **MEDIUM: メンション/装飾helper の網羅性**
   `chatwork-message-builder.md` で `to()` / `info()` / `title()` / `code()` / `hr()` は定義済だが、`toAll()`（`[toall]`）、`picon`/`piconname`、`[hr]` の具体表現が未定。観点3で挙がった `[rp]`/引用/絵文字は明示的に「今後対応」へ送られているので方針自体はOK。`toAll()` だけは追加検討推奨。

7. **MEDIUM: Route 解決優先順位の競合判定タイミング**
   `routing.md` L80 「同時に複数指定された場合は例外」は良い方針だが、`ChatworkMessage::toRoom()` と `routeNotificationForChatwork()` が両方ある場合の検出はChannel送信時に行う必要がある。検出ロジックの所在（`ChatworkChannel::resolveRoute()`）を明記。

8. **MEDIUM: テスト時の差し替え戦略が不在**
   観点5の「Channel は Resource を経由する」根拠（HTTP詳細の二重実装防止・`Http::fake()` の単一テスト点）と、テストで `ChatworkManager` を fake する例が docs に無い。`Notification::fake()` だけでなく `Http::fake()` 経由テストの推奨をtesting規約と整合させて記載。

9. **LOW: `selfUnread()` のbool版**
   `selfUnread(bool $on = true)` で off にできるシグネチャか、トグルなしか不明。再利用される builder なので off も用意。

10. **LOW: `ChatworkRoute::room()` の token 直渡し糖衣**
    `routing.md` の動的connectionは `Credentials::bearerToken()` を経由する形だが、`->withToken($apiToken)` の糖衣があると on-demand 通知が簡潔。任意。

## 強み

- Resource経由制約（`Notification → ChatworkChannel → ChatworkManager → RoomMessagesResource`）が `package-structure.md` の依存方向と完全一致し、HTTP詳細の二重実装を構造的に防げている。
- `ChatworkRoute` による connection 解決と on-demand notification 両対応で、マルチワークスペース要件をMVPで吸収できている。
