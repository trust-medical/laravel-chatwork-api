# コミット規約

## Conventional Commits

```
<type>(<scope>): <subject>

<body>

<footer>
```

### type

| type | 用途 |
|---|---|
| `feat` | 新機能 |
| `fix` | バグ修正 |
| `refactor` | 機能変更を伴わないリファクタ |
| `test` | テスト追加・修正のみ |
| `docs` | ドキュメントのみ |
| `chore` | ビルド・ツール周り |
| `style` | フォーマットのみ（Pint 適用結果など） |
| `perf` | パフォーマンス改善 |

### scope の例

- `client`, `resource:rooms`, `resource:messages`, `notification`, `oauth`, `manager`, `http`, `dto`, `ci`, `docs`
- パッケージ内で意味のある粒度。曖昧なら省略可。

## TDD コミット粒度

1 つの機能を 3 コミットに分割するのが望ましい:

1. **Red**: 失敗するテストを追加（`test(resource:messages): add failing test for create`）
2. **Green**: テストを通す最小実装（`feat(resource:messages): implement create`）
3. **Refactor**: 機能を変えずに整理（`refactor(resource:messages): extract request builder`）

許容: Red と Green を 1 コミットにまとめる（小さい変更のとき）。
NG: Red をスキップして実装→テストの順で書く。

## メッセージ

- subject は 1 行 50 文字以内、命令形、末尾にピリオドなし。
- body は何をしたかではなく **なぜ** を書く。
- 破壊的変更は body に `BREAKING CHANGE:` 行を入れる。
- 関連 issue / PR は footer に `Refs: #123` で書く。

## Co-Authored-By

Claude Code セッション内で生成されたコミットには末尾に以下を含める:

```
Co-Authored-By: Claude Sonnet 4.6 (1M context) <noreply@anthropic.com>
```

## 禁止

- `git commit --no-verify` でフック skip（pre-commit に Pint/PHPStan が入る予定）。
- `git commit --amend` で公開済みコミットを書き換える。
- 秘密情報（API token, .env, auth.json）の commit。
